<?php

declare(strict_types=1);

namespace nova\plugin\login\manager;

use nova\framework\core\Context;
use nova\framework\core\Logger;
use nova\framework\exception\AppExitException;

use function nova\framework\uuid;

use nova\plugin\avatar\Avatar;
use nova\plugin\cookie\Session;
use nova\plugin\http\HttpClient;
use nova\plugin\http\HttpException;
use nova\plugin\login\db\Dao\UserDao;
use nova\plugin\login\db\Model\UserModel;
use nova\plugin\login\LoginConfig;

/**
 * SSO单点登录管理器
 *
 * 处理与SSO认证服务器的交互，包括登录、回调处理等功能
 *
 * @package nova\plugin\login\manager
 * @since 1.0.0
 */
class SSOLoginManager extends BaseLoginManager
{
    private string $callback = "/login/callback";

    /**
     * 获取SSO登录URL
     *
     * @param  string $redirectUri 登录成功后的回调地址
     * @return string 完整的SSO登录URL
     */
    public function getLoginUrl(string $redirectUri): string
    {
        $state = uuid();
        Session::getInstance()->set('sso_state', $state);

        return LoginConfig::getInstance()->ssoProviderUrl . '/authorize?' . http_build_query([
            'client_id' => LoginConfig::getInstance()->ssoClientId,
            'redirect_uri' => $redirectUri,
            'response_type' => 'code',
            'scope' => 'openid email profile',
            'state' => $state,
        ]);
    }

    /**
     * 处理SSO回调
     *
     * @param  string                         $code  授权码
     * @param  string                         $state 状态码
     * @return UserModel|null                 用户模型，如果登录失败则返回null
     * @throws HttpException|AppExitException
     */
    public function handleCallback(string $code, string $state): ?UserModel
    {
        Logger::debug('SSO callback handler called', ['code' => !empty($code), 'state' => $state]);

        $storedState = Session::getInstance()->get('sso_state');
        if ($state !== $storedState) {
            Logger::warning('SSO callback failed: invalid state');
            return null;
        }

        Logger::debug('SSO state verified, fetching token');

        $response = HttpClient::init(LoginConfig::getInstance()->ssoProviderUrl)
            ->post([
                'grant_type' => 'authorization_code',
                'code' => $code,
                'client_id' => LoginConfig::getInstance()->ssoClientId,
                'client_secret' => LoginConfig::getInstance()->ssoClientSecret,
                'redirect_uri' => $this->callback(),
            ], 'form')
            ->send('/token');

        Logger::debug('SSO token response received', ['httpCode' => $response->getHttpCode()]);

        $data = json_decode($response->getBody(), true);
        if (!isset($data['access_token'])) {
            Logger::warning('SSO callback failed: access_token not found', ['response' => $data]);
            return null;
        }

        Logger::debug('SSO access token received, fetching user info');
        $userInfo = $this->fetchUserInfo($data['access_token']);
        if (!$userInfo || !isset($userInfo[LoginConfig::getInstance()->ssoUserField])) {
            Logger::warning('SSO callback failed: user info not found');
            return null;
        }

        Logger::debug('SSO user info received', ['username' => $userInfo[LoginConfig::getInstance()->ssoUserField]]);
        return $this->findOrCreateUser($userInfo);
    }

    /**
     * 获取用户信息
     *
     * @param  string        $token 访问令牌
     * @return array|null    用户信息数组，如果获取失败则返回null
     * @throws HttpException
     */
    protected function fetchUserInfo(string $token): ?array
    {

        $url = LoginConfig::getInstance()->ssoUserInfoUrl;
        if (!str_starts_with($url, 'http')) {
            $url = LoginConfig::getInstance()->ssoProviderUrl.$url;
        }

        $res = HttpClient::init()
            ->get()
            ->setHeader('Authorization', 'Bearer ' . $token)
            ->send($url);

        Logger::debug('SSO user info response', [
            'httpCode' => $res->getHttpCode(),
            'url' => $url,
        ]);

        return $res->getHttpCode() === 200 ? json_decode($res->getBody(), true) : null;
    }

    /**
     * 查找或创建用户
     *
     * @param  array          $info 用户信息
     * @return UserModel|null 用户模型，如果创建失败则返回null
     */
    protected function findOrCreateUser(array $info): ?UserModel
    {
        $dao = UserDao::getInstance();
        $username = $info[LoginConfig::getInstance()->ssoUserField];
        Logger::debug('SSO find or create user', ['username' => $username]);

        $user = $dao->username($username);

        if ($user) {
            Logger::info('SSO user found', [
                'userId' => $user->id,
                'username' => $username,
                'displayName' => $info[LoginConfig::getInstance()->ssoDisplayNameField] ?? $username,
            ]);
            $user->display_name = $info[LoginConfig::getInstance()->ssoDisplayNameField] ?? $username;
            $user->avatar = $info[LoginConfig::getInstance()->ssoAvatarField] ?? $user->avatar;
            UserDao::getInstance()->updateModel($user);
            return $user;
        }

        Logger::info('SSO user not found, checking creation policy', [
            'username' => $username,
            'mustHasAccount' => LoginConfig::getInstance()->ssoMustHasAccount,
        ]);

        if (LoginConfig::getInstance()->ssoMustHasAccount) {
            Logger::warning('SSO user creation denied: must have account', ['username' => $username]);
            return null;
        }

        $user = new UserModel();
        $user->display_name = $info[LoginConfig::getInstance()->ssoDisplayNameField] ?? $username;
        $user->password = password_hash(uuid(), PASSWORD_DEFAULT);
        $user->username = $username;
        $user->avatar = $info[LoginConfig::getInstance()->ssoAvatarField] ?? Avatar::toBase64(Avatar::svg($username));
        $dao->insertModel($user);

        Logger::info('SSO user created', [
            'userId' => $user->id,
            'username' => $username,
            'displayName' => $user->display_name,
        ]);

        return $user;
    }

    /**
     * 获取回调地址
     *
     * @return string 回调URL
     */
    private function callback(): string
    {
        return Context::instance()->request()->getBasicAddress() . $this->callback;
    }

    /**
     * 重定向到SSO服务提供者
     *
     * @return string 重定向URL
     */
    public function redirectToProvider(): string
    {
        return $this->getLoginUrl($this->callback());
    }
}
