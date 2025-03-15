<?php

declare(strict_types=1);

namespace nova\plugin\login\manager;

use function nova\framework\config;

use nova\framework\core\Context;
use nova\framework\core\Logger;
use nova\framework\event\EventManager;
use nova\plugin\cookie\Session;
use nova\plugin\http\HttpClient;
use nova\plugin\login\db\Dao\UserDao;
use nova\plugin\login\db\Model\UserModel;
use Random\RandomException;

class SSOLoginManager extends BaseLoginManager
{
    public static function register(): void
    {
        EventManager::addListener("route.before", function ($event, &$uri) {

        });
    }

    /**
     * @var string SSO provider URL
     */
    protected string $ssoProviderUrl;

    /**
     * @var string Client ID for SSO
     */
    protected string $clientId;

    /**
     * @var string Client secret for SSO
     */
    protected string $clientSecret;

    /**
     * @var string Token endpoint
     */
    protected string $tokenEndpoint;

    /**
     * @var string Userinfo endpoint
     */
    protected string $userinfoEndpoint;

    /**
     * @var string JWKS endpoint for token validation
     */
    protected string $jwksEndpoint;

    public function __construct()
    {
        $this->ssoProviderUrl = config('sso.provider_url');
        $this->clientId = config('sso.client_id');
        $this->clientSecret = config('sso.client_secret');
        $this->tokenEndpoint = config('sso.token_endpoint', $this->ssoProviderUrl . '/token');
        $this->userinfoEndpoint = config('sso.userinfo_endpoint', $this->ssoProviderUrl . '/userinfo');
        $this->jwksEndpoint = config('sso.jwks_endpoint', $this->ssoProviderUrl . '/.well-known/jwks.json');
    }

    /**
     * Authenticate a user with SSO token
     *
     * @param  array          $credentials Should contain 'token' key
     * @return bool|UserModel Whether authentication was successful
     */
    public function authenticate(array $credentials): bool|UserModel
    {
        // Validate required credentials
        if (!isset($credentials['token'])) {
            return false;
        }

        try {
            // Validate the token
            if (!$this->validateSSOToken($credentials['token'])) {
                return false;
            }

            // Get user info from token
            $userInfo = $this->getUserInfoFromToken($credentials['token']);
            if (!$userInfo) {
                return false;
            }

            // Find or create user based on SSO identity
            $user = $this->findOrCreateUser($userInfo);
            if (!$user) {
                return false;
            }

            return $user;
        } catch (\Exception $e) {
            Logger::error('SSO authentication error: ' . $e->getMessage(), $e->getTrace());
            return false;
        }
    }

    /**
     * Get SSO login URL
     *
     * @param  string $redirectUrl URL to redirect after successful login
     * @return string SSO login URL
     */
    public function getSSOLoginUrl(string $redirectUrl): string
    {
        // Generate a state parameter to prevent CSRF
        $state = bin2hex(random_bytes(16));
        $nonce = bin2hex(random_bytes(16));

        // Store state in session for validation when the user returns
        Session::getInstance()->set('sso_state', $state, 3600); // 1 hour expiry
        Session::getInstance()->set('sso_nonce', $nonce, 3600);
        Session::getInstance()->set('sso_redirect', $redirectUrl, 3600);

        // Build the authorization URL
        return $this->ssoProviderUrl . '/authorize?' . http_build_query([
            'client_id' => $this->clientId,
            'redirect_uri' => $redirectUrl,
            'response_type' => 'code',
            'scope' => 'openid profile email',
            'state' => $state,
            'nonce' => $nonce
        ]);
    }

    /**
     * Handle SSO callback
     *
     * @param  string         $code  Authorization code from SSO provider
     * @param  string         $state State parameter for CSRF protection
     * @return bool|UserModel Whether the callback was handled successfully
     */
    public function handleCallback(string $code, string $state): bool|UserModel
    {
        try {
            // Verify state parameter to prevent CSRF attacks
            $storedState = Session::getInstance()->get('sso_state');
            $storedNonce = Session::getInstance()->get('sso_nonce');
            $redirectUrl = Session::getInstance()->get('sso_redirect');

            if (empty($storedState) || $storedState !== $state) {
                Logger::warning('SSO callback: Invalid state parameter');
                return false;
            }

            // Exchange code for tokens
            $tokens = $this->exchangeCodeForTokens($code, $redirectUrl);
            if (!$tokens || !isset($tokens['access_token']) || !isset($tokens['id_token'])) {
                Logger::warning('SSO callback: Failed to exchange code for tokens');
                return false;
            }

            // Validate ID token
            if (!$this->validateIdToken($tokens['id_token'], $storedNonce)) {
                Logger::warning('SSO callback: Invalid ID token');
                return false;
            }

            // Get user info
            $userInfo = $this->getUserInfo($tokens['access_token']);
            if (!$userInfo) {
                Logger::warning('SSO callback: Failed to get user info');
                return false;
            }

            // Find or create user
            $user = $this->findOrCreateUser($userInfo);
            if (!$user) {
                Logger::warning('SSO callback: Failed to find or create user');
                return false;
            }

            // Store tokens in session
            Session::getInstance()->set('sso_access_token', $tokens['access_token']);
            Session::getInstance()->set('sso_id_token', $tokens['id_token']);
            if (isset($tokens['refresh_token'])) {
                Session::getInstance()->set('sso_refresh_token', $tokens['refresh_token']);
            }

            // Clean up state and nonce
            Session::getInstance()->delete('sso_state');
            Session::getInstance()->delete('sso_nonce');
            Session::getInstance()->delete('sso_redirect');

            return $user;
        } catch (\Exception $e) {
            Logger::error('SSO callback error: ' . $e->getMessage(), $e->getTrace());
            return false;
        }
    }

    /**
     * Exchange authorization code for tokens
     *
     * @param  string      $code        Authorization code
     * @param  string      $redirectUrl Redirect URL used in the initial request
     * @return array|false Tokens or false on failure
     */
    protected function exchangeCodeForTokens(string $code, string $redirectUrl): array|false
    {
        try {
            $response = HttpClient::init($this->tokenEndpoint)
                ->post([
                    'grant_type' => 'authorization_code',
                    'code' => $code,
                    'redirect_uri' => $redirectUrl,
                    'client_id' => $this->clientId,
                    'client_secret' => $this->clientSecret
                ], 'form')
                ->setHeader('Accept', 'application/json')
                ->send();

            if ($response->getHttpCode() !== 200) {
                Logger::error('SSO token exchange failed: ' . $response->getHttpCode() . ' ' . $response->getBody());
                return false;
            }

            $tokens = json_decode($response->getBody(), true);
            if (!is_array($tokens)) {
                Logger::error('SSO token exchange: Invalid response format');
                return false;
            }

            return $tokens;
        } catch (\Exception $e) {
            Logger::error('SSO token exchange error: ' . $e->getMessage(), $e->getTrace());
            return false;
        }
    }

    /**
     * Get user info from access token
     *
     * @param  string      $accessToken Access token
     * @return array|false User info or false on failure
     */
    protected function getUserInfo(string $accessToken): array|false
    {
        try {
            $response = HttpClient::init($this->userinfoEndpoint)
                ->get()
                ->setHeader('Authorization', 'Bearer ' . $accessToken)
                ->setHeader('Accept', 'application/json')
                ->send();

            if ($response->getHttpCode() !== 200) {
                Logger::error('SSO userinfo request failed: ' . $response->getHttpCode() . ' ' . $response->getBody());
                return false;
            }

            $userInfo = json_decode($response->getBody(), true);
            if (!is_array($userInfo)) {
                Logger::error('SSO userinfo: Invalid response format');
                return false;
            }

            return $userInfo;
        } catch (\Exception $e) {
            Logger::error('SSO userinfo error: ' . $e->getMessage(), $e->getTrace());
            return false;
        }
    }

    /**
     * Get user info from ID token
     *
     * @param  string      $token ID token
     * @return array|false User info or false on failure
     */
    protected function getUserInfoFromToken(string $token): array|false
    {
        // For ID tokens, we can decode the JWT payload without verification
        // since we're assuming it's already verified
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            return false;
        }

        $payload = json_decode(base64_decode(strtr($parts[1], '-_', '+/')), true);
        if (!is_array($payload)) {
            return false;
        }

        return $payload;
    }

    /**
     * Find or create user based on SSO identity
     *
     * @param  array           $userInfo User info from SSO provider
     * @return UserModel|false User model or false on failure
     * @throws RandomException
     */
    protected function findOrCreateUser(array $userInfo): UserModel|false
    {
        // Extract required user information
        $email = $userInfo['email'] ?? null;
        $name = $userInfo['name'] ?? ($userInfo['preferred_username'] ?? null);

        if (!$email) {
            Logger::error('SSO: Missing email in user info');
            return false;
        }

        // Try to find user by email
        $userDao = UserDao::getInstance();
        $user = $userDao->findByEmail($email);

        if (!$user) {
            // User doesn't exist, create a new one
            if (!$name) {
                // Use email as name if name is not provided
                $name = $email;
            }

            // Create new user
            $user = new UserModel();
            $user->email = $email; // Email is the primary identifier
            $user->display_name = $name;
            $user->password = bin2hex(random_bytes(16)); // Random password, not used for SSO
            $user->roles = ['user'];
            // Save user
            $userDao->insertModel($user);
        } else {
            // Update existing user info if needed
            if ($name && $user->display_name !== $name) {
                $user->display_name = $name;
                $userDao->updateModel($user);
            }
        }

        return $user;
    }

    /**
     * Validate ID token
     *
     * @param  string $idToken ID token to validate
     * @param  string $nonce   Expected nonce value
     * @return bool   Whether the token is valid
     */
    protected function validateIdToken(string $idToken, string $nonce): bool
    {
        // Basic JWT structure validation
        $parts = explode('.', $idToken);
        if (count($parts) !== 3) {
            return false;
        }

        // Decode header and payload
        $header = json_decode(base64_decode(strtr($parts[0], '-_', '+/')), true);
        $payload = json_decode(base64_decode(strtr($parts[1], '-_', '+/')), true);

        if (!$header || !$payload) {
            return false;
        }

        // Verify token hasn't expired
        if (!isset($payload['exp']) || $payload['exp'] < time()) {
            return false;
        }

        // Verify audience
        if (!isset($payload['aud']) || $payload['aud'] !== $this->clientId) {
            return false;
        }

        // Verify issuer
        if (!isset($payload['iss']) || $payload['iss'] !== $this->ssoProviderUrl) {
            return false;
        }

        // Verify nonce if provided
        if ($nonce && (!isset($payload['nonce']) || $payload['nonce'] !== $nonce)) {
            return false;
        }

        // For a production system, you should verify the signature using the JWKS endpoint
        // This would require fetching the public keys and validating the signature

        return true;
    }

    /**
     * Validate SSO token
     *
     * @param  string $token SSO token
     * @return bool   Whether the token is valid
     */
    protected function validateSSOToken(string $token): bool
    {
        try {
            $response = HttpClient::init($this->userinfoEndpoint)
                ->get()
                ->setHeader('Authorization', 'Bearer ' . $token)
                ->setHeader('Accept', 'application/json')
                ->send();

            return $response->getHttpCode() === 200;
        } catch (\Exception $e) {
            Logger::error('SSO token validation error: ' . $e->getMessage(), $e->getTrace());
            return false;
        }
    }

    /**
     * Logout user from SSO
     *
     * @return bool Whether logout was successful
     */
    public function logout(): bool
    {
        try {
            // Get ID token from session
            $idToken = Session::getInstance()->get('sso_id_token');

            // Clear SSO-related session data
            Session::getInstance()->delete('sso_access_token');
            Session::getInstance()->delete('sso_id_token');
            Session::getInstance()->delete('sso_refresh_token');

            // If the SSO provider supports logout, redirect the user
            if ($idToken) {
                $logoutUrl = $this->ssoProviderUrl . '/logout?' . http_build_query([
                    'id_token_hint' => $idToken,
                    'post_logout_redirect_uri' => Context::instance()->request()->getBasicAddress()
                ]);

                // In a real implementation, you would redirect to this URL
                // For now, we'll just return success
            }

            return true;
        } catch (\Exception $e) {
            Logger::error('SSO logout error: ' . $e->getMessage(), $e->getTrace());
            return false;
        }
    }

    public function redirectToProvider(): string
    {
        $redirectUrl = Context::instance()->request()->getBasicAddress() . "/callback";
        return $this->getSSOLoginUrl($redirectUrl);
    }
}
