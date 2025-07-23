<?php

declare(strict_types=1);

namespace nova\plugin\login\manager;

use nova\framework\core\Context;
use nova\framework\event\EventManager;
use nova\framework\exception\AppExitException;
use nova\framework\http\Response;

use function nova\framework\uuid;

use nova\plugin\avatar\Avatar;
use nova\plugin\cookie\Session;
use nova\plugin\http\HttpClient;
use nova\plugin\http\HttpException;
use nova\plugin\login\db\Dao\UserDao;
use nova\plugin\login\db\Model\UserModel;
use nova\plugin\login\LoginManager;

/**
 * SSO单点登录管理器
 * 用于处理与SSO认证服务器的交互，包括登录、回调处理等功能
 */
class SSOLoginManager extends BaseLoginManager
{
    /** @var string SSO客户端ID */
    protected string $clientId;
    /** @var string SSO客户端密钥 */
    protected string $clientSecret;
    /** @var string SSO服务提供者基础URL */
    protected string $providerUrl;
    /** @var string 授权URL */
    protected string $authorizeUrl;
    /** @var string 令牌URL */
    protected string $tokenUrl;
    /** @var string 用户信息URL */
    protected string $userinfoUrl;
    /** @var bool 是否必须拥有账户才能登录 */
    protected bool $mustHasAccount = true;

    /**
     * 构造函数
     * 初始化SSO配置信息
     */
    public function __construct()
    {
        parent::__construct();
        $this->providerUrl  = $this->loginConfig->ssoProviderUrl;
        $this->clientId     = $this->loginConfig->ssoClientId;
        $this->clientSecret = $this->loginConfig->ssoClientSecret;
        $this->mustHasAccount = $this->loginConfig->ssoMustHasAccount;
        $this->authorizeUrl = $this->providerUrl . '/authorize';
        $this->tokenUrl     = $this->providerUrl . '/token';
        $this->userinfoUrl  = $this->providerUrl . '/userinfo';
    }

    /**
     * 获取SSO登录URL
     * @param  string $redirectUri 登录成功后的回调地址
     * @return string 完整的SSO登录URL
     */
    public function getLoginUrl(string $redirectUri): string
    {
        $state = uuid();
        Session::getInstance()->set('sso_state', $state);

        return $this->authorizeUrl . '?' . http_build_query([
            'client_id' => $this->clientId,
            'redirect_uri' => $redirectUri,
            'response_type' => 'code',
            'scope' => 'openid email profile',
            'state' => $state,
        ]);
    }

    /**
     * 处理SSO回调
     * @param  string                         $code  授权码
     * @param  string                         $state 状态码
     * @return UserModel|null                 用户模型，如果登录失败则返回null
     * @throws HttpException|AppExitException
     */
    public function handleCallback(string $code, string $state): ?UserModel
    {

        $storedState = Session::getInstance()->get('sso_state');

        if ($state !== $storedState) {
            return null;
        }

        $response = HttpClient::init($this->tokenUrl)
            ->post([
                'grant_type' => 'authorization_code',
                'code' => $code,
                'client_id' => $this->clientId,
                'client_secret' => $this->clientSecret,
            ], 'form')
            ->send();

        $data = json_decode($response->getBody(), true);
        if (!isset($data['access_token'])) {
            return null;
        }

        $userInfo = $this->fetchUserInfo($data['access_token']);
        if (!$userInfo || !isset($userInfo['username'])) {
            return null;
        }
        return $this->findOrCreateUser($userInfo);
    }

    /**
     * 获取用户信息
     * @param  string        $token 访问令牌
     * @return array|null    用户信息数组，如果获取失败则返回null
     * @throws HttpException
     */
    protected function fetchUserInfo(string $token): ?array
    {
        $res = HttpClient::init($this->userinfoUrl)
            ->get()
            ->setHeader('Authorization', 'Bearer ' . $token)
            ->send();

        return $res->getHttpCode() === 200 ? json_decode($res->getBody(), true) : null;
    }

    /**
     * 查找或创建用户
     * @param  array          $info 用户信息
     * @return UserModel|null 用户模型，如果创建失败则返回null
     */
    protected function findOrCreateUser(array $info): ?UserModel
    {
        $dao = UserDao::getInstance();
        $user = $dao->username($info['username']);
        if ($user) {
            $user->display_name = $info['nickname'] ?? $info['username'];
            $user->avatar = $info['avatar_url'] ?? $user->avatar;
            return $user;
        }

        if ($this->mustHasAccount) {
            return null;
        }

        $user = new UserModel();
        $user->display_name = $info['nickname'] ?? $info['username'];
        $user->password = password_hash(uuid(), PASSWORD_DEFAULT);
        $user->username = $info['username'];
        $user->avatar = $info['avatar_url'] ?? Avatar::toBase64(Avatar::svg($info['username']));
        $dao->insertModel($user);
        return $user;
    }

    /**
     * 重定向到SSO服务提供者
     * @return string 重定向URL
     */
    public function redirectToProvider(): string
    {
        return $this->getLoginUrl(Context::instance()->request()->getBasicAddress()."/sso/callback");
    }

    /**
     * 注册SSO路由处理器
     * 用于处理SSO回调请求和SSO配置
     */
    public static function register(): void
    {
        EventManager::addListener("route.before", function ($event, &$uri) {

            if (str_starts_with($uri, "/sso/callback")) {
                $sso = new SSOLoginManager();

                $user =  $sso->handleCallback($_GET['code'], $_GET['state']);
                if ($user) {
                    LoginManager::getInstance()->login($user);
                    $redirect = $sso->loginConfig->loginCallback;
                    throw new AppExitException(Response::asRedirect($redirect));
                }
                throw new AppExitException(Response::asText("login failed"));
            }

            if (str_starts_with($uri, "/sso/config")) {
                $sso = new SSOLoginManager();
                $response = $sso->handleSSO();
                throw new AppExitException($response, 'Exit by SSO');
            }
        });
    }

    const string PERMISSION_SSO = "SSO配置权限";
    const string TPL_SSO =  ROOT_PATH . DS . 'nova' . DS . 'plugin' . DS . 'login' . DS . 'tpl' . DS."oidc";

    /**
     * 处理SSO配置请求
     * GET请求：获取SSO配置
     * POST请求：更新SSO配置
     *
     * @return Response SSO配置响应
     */
    private function handleSSO(): Response
    {
        $user = LoginManager::getInstance()->checkLogin();
        if (!$user) {
            return Response::asJson([
                "code"  => 301,
                "msg"   => $this->redirectToProvider(),
            ]);
        }
        if (!$user->hasPermission(self::PERMISSION_SSO)) {
            return  Response::asJson([
                "code"  => 403,
                "msg"   => "无权访问",
            ]);
        }
        if ($_SERVER['REQUEST_METHOD'] == 'GET') {
            return Response::asJson([
                "code"  => 200,
                "data"  => [
                    'ssoEnable' => $this->loginConfig->ssoEnable,
                    'ssoProviderUrl' => $this->loginConfig->ssoProviderUrl,
                    'ssoClientId' => $this->loginConfig->ssoClientId,
                    'ssoClientSecret' => $this->loginConfig->ssoClientSecret,
                ]
            ]);
        } else {
            $this->loginConfig->ssoEnable = $_POST['ssoEnable'] ? boolval($_POST['ssoEnable']) : $this->loginConfig->ssoEnable;
            $this->loginConfig->ssoProviderUrl = $_POST['ssoProviderUrl'] ?? $this->loginConfig->ssoProviderUrl;
            $this->loginConfig->ssoClientId =  $_POST['ssoClientId'] ?? $this->loginConfig->ssoClientId;
            $this->loginConfig->ssoClientSecret = $_POST['ssoClientSecret'] ?? $this->loginConfig->ssoClientSecret;
            return Response::asJson([
                "code"  => 200,
                "msg"   => "操作成功",
            ]);
        }
    }
}
