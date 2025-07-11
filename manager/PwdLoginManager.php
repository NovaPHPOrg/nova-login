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
use nova\plugin\cookie\Session;
use nova\plugin\login\db\Dao\UserDao;
use nova\plugin\login\db\Model\UserModel;
use nova\plugin\login\LoginManager;
use nova\plugin\tpl\ViewResponse;

class PwdLoginManager extends BaseLoginManager
{
    protected Cache $cache;

    public function __construct()
    {
        parent::__construct();
        $this->cache = Context::instance()->cache;
    }

    /* -----------------------------------------------------------------
     |  注册路由监听（仍然保持 EventManager 方式，改用 match 分派）
     | ----------------------------------------------------------------- */
    public static function register(): void
    {
        EventManager::addListener('route.before', function ($event, &$uri) {

            if (!str_starts_with($uri, '/login')) {
                return;
            }

            $mgr      = new self();
            $redirect = $mgr->loginConfig->loginCallback;

            // 启动会话 & 初始化表
            Session::getInstance()->start();
            UserDao::getInstance()->initTable();

            /* 路由分派：5 条分支一目了然 */
            $response = match ($uri) {
                '/login'              => $mgr->showLogin($redirect),
                '/login/pwd'          => $mgr->handleLogin($_POST, $redirect),
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

    private function handleLogin(array $post, string $redirect): Response
    {
        $user = $this->authenticate($post);

        if ($user === false) {
            return $this->json(403, '登录失败');
        }

        LoginManager::getInstance()->login($user);
        return $this->json(200, '登录成功', ['data' => $redirect]);
    }
    private function outputCaptcha(): Response
    {
        return (new Captcha())->create("user_login");
    }

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
     * @param  array           $credentials
     * @return UserModel|false
     */
    public function authenticate(array $credentials): UserModel|false
    {
        if (!isset($credentials['username'], $credentials['password'], $credentials['captcha'])) {
            return false;
        }

        if (!Captcha::verify("user_login", (int)$credentials['captcha'])) {
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

    public function redirectToProvider(): string
    {
        return '/login';
    }

    /**
     * 重置密码 / 邮箱
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
