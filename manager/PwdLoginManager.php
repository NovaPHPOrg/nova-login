<?php

declare(strict_types=1);

namespace nova\plugin\login\manager;

use nova\framework\cache\Cache;
use nova\framework\core\Context;
use nova\framework\core\Logger;
use nova\framework\event\EventManager;
use nova\framework\exception\AppExitException;
use nova\framework\http\Response;
use nova\plugin\captcha\Captcha;
use nova\plugin\login\db\Dao\UserDao;
use nova\plugin\login\db\Model\UserModel;
use nova\plugin\login\LoginManager;
use nova\plugin\tpl\ViewResponse;

/**
 * 密码登录管理器
 *
 * 负责处理基于用户名和密码的登录认证流程，包括：
 * - 登录页面展示
 * - 用户认证
 * - 验证码处理
 * - 密码重置
 * - SSO配置管理
 *
 * @package nova\plugin\login\manager
 */
class PwdLoginManager extends BaseLoginManager
{
    /** @var Cache 缓存实例 */
    protected Cache $cache;

    /**
     * 构造函数
     *
     * 初始化缓存实例
     */
    public function __construct()
    {
        parent::__construct();
        $this->cache = Context::instance()->cache;
    }

    /* -----------------------------------------------------------------
     |  注册路由监听（仍然保持 EventManager 方式，改用 match 分派）
     | ----------------------------------------------------------------- */

    /**
     * 注册登录路由监听器
     *
     * 监听路由事件，处理所有以 /login 开头的请求
     * 使用 match 表达式进行路由分派，提高代码可读性
     */
    public static function register(): void
    {
        EventManager::addListener('route.before', function ($event, &$uri) {

            if (!str_starts_with($uri, '/login')) {
                return;
            }

            $mgr      = new self();
            $redirect = $mgr->loginConfig->loginCallback;

            // 启动会话 & 初始化表
            UserDao::getInstance()->initTable();

            /* 路由分派：5 条分支一目了然 */
            $response = match ($uri) {
                '/login'              => $mgr->showLogin($redirect),
                '/login/pwd'          => $mgr->handleLogin($_POST, $redirect),
                '/login/sso'          => $mgr->handleSSO(),
                '/login/captcha'      => $mgr->outputCaptcha(),     // 内部直接 exit
                '/login/reset'        => $mgr->handleReset($_POST),
                default               => null,
            };

            throw new AppExitException($response, 'Exit by Login');
        });
    }

    /* -----------------------------------------------------------------
     |  路由处理方法
     | ----------------------------------------------------------------- */

    /**
     * 显示登录页面
     *
     * @param  string   $redirect 登录成功后的重定向地址
     * @return Response 登录页面响应或重定向响应
     */
    private function showLogin(string $redirect): Response
    {
        if (LoginManager::getInstance()->checkLogin()) {
            return Response::asRedirect($redirect);
        }
        $view = new ViewResponse();
        $view->init(
            '',
            [
                'title' => $this->loginConfig->systemName,
            ],
            '{',
            '}',
            ROOT_PATH . DS . 'nova' . DS . 'plugin' . DS . 'login' . DS . 'tpl' . DS
        );
        return $view->asTpl('index');
    }

    /** @var string 用户中心模板路径常量 */
    const string CENTER_TPL =  ROOT_PATH . DS . 'nova' . DS . 'plugin' . DS . 'login' . DS . 'tpl' . DS."center";

    /**
     * 处理SSO相关请求
     *
     * GET请求：返回SSO配置信息
     * POST请求：更新SSO配置
     *
     * @return Response SSO配置响应
     */
    private function handleSSO(): Response
    {
        if (!LoginManager::getInstance()->checkLogin()) {
            return Response::asRedirect($this->redirectToProvider());
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

    /**
     * 处理登录请求
     *
     * @param  array    $post     POST请求数据
     * @param  string   $redirect 登录成功后的重定向地址
     * @return Response 登录结果响应
     */
    private function handleLogin(array $post, string $redirect): Response
    {
        $user = $this->authenticate($post);

        if ($user === false) {
            return $this->json(403, '登录失败');
        }

        LoginManager::getInstance()->login($user);
        return $this->json(200, '登录成功', ['data' => $redirect]);
    }

    /**
     * 输出验证码
     *
     * @return Response 验证码图片响应
     */
    private function outputCaptcha(): Response
    {
        return (new Captcha())->create();
    }

    /**
     * 处理密码重置请求
     *
     * @param  array    $post POST请求数据
     * @return Response 重置结果响应
     */
    private function handleReset(array $post): Response
    {
        $user = LoginManager::getInstance()->checkLogin();

        if ($user && $this->reset($post, $user)) {
            LoginManager::getInstance()->logout();
            return $this->json(200, '操作成功', ['data' => '/login']);
        }

        return $this->json(403, '重置失败');
    }

    /* -----------------------------------------------------------------
     |  工具方法：统一 Response / 视图 / 跳转 / 抛异常
     | ----------------------------------------------------------------- */

    /**
     * 生成JSON响应
     *
     * @param  int      $code  响应状态码
     * @param  string   $msg   响应消息
     * @param  array    $extra 额外数据
     * @return Response JSON响应对象
     */
    private function json(int $code, string $msg, array $extra = []): Response
    {
        return Response::asJson(array_merge(['code' => $code, 'msg' => $msg], $extra));
    }

    /* -----------------------------------------------------------------
     |  业务逻辑：以下内容与旧版保持一致（仅位置调整）
     | ----------------------------------------------------------------- */

    /**
     * 用户名 + 密码认证
     *
     * 验证用户提供的登录凭据，包括：
     * - 验证码验证
     * - 用户名密码验证
     * - 记录登录失败日志
     *
     * @param  array           $credentials 登录凭据数组，包含 username、password、captcha
     * @return UserModel|false 认证成功返回用户模型，失败返回false
     */
    public function authenticate(array $credentials): UserModel|false
    {
        if (!isset($credentials['username'], $credentials['password'], $credentials['captcha'])) {
            return false;
        }

        if (!Captcha::verify((int)$credentials['captcha'])) {
            Logger::warning($credentials['username']." 登录失败，验证码错误", $credentials);
            return false;
        }

        $user = UserDao::getInstance()->login(
            $credentials['username'],
            $credentials['password']
        );

        if ($user) {
            return $user;
        }

        Logger::warning($credentials['username']." 登录失败，密码错误", $credentials);
        return false;
    }

    /**
     * 重定向到登录提供者
     *
     * @return string 登录页面URL
     */
    public function redirectToProvider(): string
    {
        return '/login';
    }

    /**
     * 重置密码 / 邮箱
     *
     * 允许用户更新密码和用户名，包括：
     * - 验证当前密码
     * - 新密码长度验证（最少8位）
     * - 用户名格式验证（5-10位字母数字）
     * - 用户名唯一性检查
     *
     * @param  array     $data 重置数据，包含 current_password、new_password、username
     * @param  UserModel $user 当前用户模型
     * @return bool      重置成功返回true，失败返回false
     */
    private function reset(array $data, UserModel $user): bool
    {
        if (!isset($data['current_password'])) {
            return false;
        }

        $userDao = UserDao::getInstance();
        if ($userDao->login($user->username, $data['current_password']) === null) {
            Logger::warning('密码重置失败 - 当前密码无效', [
                'user_id' => $user->id,
                'ip'      => Context::instance()->request()->getClientIP(),
            ]);
            return false;
        }

        /* 更新密码 */
        if (!empty($data['new_password'])) {
            if (strlen($data['new_password']) < 8) {
                return false;
            }
            $user->password = password_hash($data['new_password'], PASSWORD_DEFAULT);
        }

        /* 更新用户名 */
        if (isset($data['username'])) {
            if (preg_match('/^[a-z0-9]{5,10}$/', $data['username']) !== 1) {
                return false;
            }
            $existing = $userDao->username($data['username']);
            if ($existing && $existing->id !== $user->id) {
                return false;
            }
            $user->username = $data['username'];
        }
        $userDao->updateModel($user);
        Logger::info('账户信息更新成功');
        return true;
    }
}
