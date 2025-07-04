<?php

declare(strict_types=1);

namespace nova\plugin\login\manager;

use nova\framework\cache\Cache;
use nova\plugin\captcha\Captcha;
use nova\framework\core\Context;
use nova\framework\core\Logger;
use nova\framework\event\EventManager;
use nova\framework\exception\AppExitException;
use nova\framework\http\Response;
use nova\plugin\cookie\Session;
use nova\plugin\login\db\Dao\LogDao;
use nova\plugin\login\db\Dao\UserDao;
use nova\plugin\login\db\Model\UserModel;
use nova\plugin\login\LoginManager;
use nova\plugin\tpl\ViewResponse;

class PwdLoginManager extends BaseLoginManager
{
    /** 连续 N 次失败后要求验证码 */
    private const int CAPTCHA_THRESHOLD = 2;

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
                '/login/need_captcha' => $mgr->jsonNeedCaptcha(),
                '/login/captcha'      => $mgr->outputCaptcha(),     // 内部直接 exit
                '/login/reset'        => $mgr->handleReset($_POST),
                default               => null,
            };

            // 统一抛出 Response，框架继续后续流程
            if ($response instanceof Response) {
                $mgr->exitWith($response);
            }
        });
    }

    /* -----------------------------------------------------------------
     |  路由处理方法
     | ----------------------------------------------------------------- */

    private function showLogin(string $redirect): Response
    {
        if (LoginManager::getInstance()->checkLogin()) {
            return $this->redirect($redirect);
        }
        return $this->view('index', [
            'title' => $this->loginConfig->systemName,
        ]);
    }

    private function handleLogin(array $post, string $redirect): Response
    {
        $user = $this->authenticate($post);

        if ($user === false) {
            return $this->json(403, '登录失败', [
                'need_captcha' => $this->needCaptcha(),
            ]);
        }

        LoginManager::getInstance()->login($user);
        return $this->json(200, '登录成功', ['data' => $redirect]);
    }

    private function jsonNeedCaptcha(): Response
    {
        return $this->json(200, 'ok', ['need_captcha' => $this->needCaptcha()]);
    }

    private function outputCaptcha(): never
    {
        $this->generateCaptcha();
        exit();   // 图片已输出，结束脚本
    }

    private function handleReset(array $post): Response
    {
        $user = LoginManager::getInstance()->checkLogin();

        if ($user && $this->reset($post, $user)) {
            LoginManager::getInstance()->logout($user->id);
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

    private function redirect(string $url): Response
    {
        return Response::asRedirect($url);
    }

    private function view(string $tpl, array $vars = []): Response
    {
        $view = new ViewResponse();
        $view->init(
            '',
            $vars,
            false,
            '{',
            '}',
            ROOT_PATH . DS . 'nova' . DS . 'plugin' . DS . 'login' . DS . 'tpl' . DS
        );
        return $view->asTpl($tpl);
    }

    private function exitWith(Response $resp): never
    {
        throw new AppExitException($resp, 'Exit by Login');
    }

    /* -----------------------------------------------------------------
     |  业务逻辑：以下内容与旧版保持一致（仅位置调整）
     | ----------------------------------------------------------------- */

    /** 生成验证码图片 */
    protected function generateCaptcha(): void
    {
        (new Captcha())->create("user_login");
    }

    /** 校验验证码 */
    protected function validateCaptcha(?string $code): bool
    {
        if ($code === null) {
            return false;
        }
        return Captcha::verify("user_login", (int)$code);
    }

    /** 判断当前 IP 是否需验证码 */
    public function needCaptcha(): bool
    {
        $ip          = Context::instance()->request()->getClientIP();
        $attemptsKey = "login:attempts:{$ip}";
        $attempts    = (int) $this->cache->get($attemptsKey, 0);

        return $attempts >= self::CAPTCHA_THRESHOLD;
    }

    /**
     * 用户名 + 密码认证
     *
     * @return UserModel|false
     */
    public function authenticate(array $credentials): UserModel|false
    {
        if (!isset($credentials['email'], $credentials['password'])) {
            return false;
        }

        $ip = Context::instance()->request()->getClientIP();

        // 非 debug 模式才做封禁 / 验证码
        if (!Context::instance()->isDebug()) {
            if ($this->checkIpBlocked($ip)['blocked']) {
                Logger::warning("IP $ip 已被封禁", $credentials);
                return false;
            }

            if ($this->needCaptcha() &&
                (!isset($credentials['captcha']) || !$this->validateCaptcha($credentials['captcha']))) {
                Logger::warning("IP $ip 验证码失败", $credentials);
                return false;
            }
        }

        $user = UserDao::getInstance()->login(
            $credentials['email'],
            $credentials['password']
        );

        if ($user) {
            if (!Context::instance()->isDebug()) {
                $this->resetFailedAttempts($ip);
            }
            LogDao::getInstance()->logAction($user->id, 'login', '登录成功');
            return $user;
        }

        $this->recordFailedAttempt($ip);
        Logger::warning("IP $ip 登录失败", $credentials);
        return false;
    }

    /** IP 封禁检查 */
    protected function checkIpBlocked(string $ip): array
    {
        $blockKey  = "login:blocked:{$ip}";
        $blockData = $this->cache->get($blockKey);

        if ($blockData) {
            $blockDuration = 300 * $blockData['attempts']; // 5 min × 尝试次数
            if (time() < $blockData['time'] + $blockDuration) {
                return [
                    'blocked'   => true,
                    'remaining' => ($blockData['time'] + $blockDuration) - time(),
                ];
            }
            $this->cache->delete($blockKey); // 封禁已过期
        }

        return ['blocked' => false];
    }

    /** 记录一次失败尝试 */
    protected function recordFailedAttempt(string $ip): void
    {
        $attemptsKey = "login:attempts:{$ip}";
        $attempts    = (int) $this->cache->get($attemptsKey, 0) + 1;

        $this->cache->set($attemptsKey, $attempts, 3600);

        // 每 3 次失败封禁，封禁时长指数递增
        if ($attempts >= 3 && !Context::instance()->isDebug()) {
            $blockKey = "login:blocked:{$ip}";
            $this->cache->set($blockKey, [
                'time'     => time(),
                'attempts' => ceil($attempts / 3),
            ], 86400);
            $this->cache->set($attemptsKey, 0, 3600); // 重置计数器
        }
    }

    /** 登录成功后重置尝试计数 */
    protected function resetFailedAttempts(string $ip): void
    {
        $this->cache->delete("login:attempts:{$ip}");
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
        if ($userDao->login($user->email, $data['current_password']) === null) {
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

        /* 更新邮箱 */
        if (isset($data['email'])) {
            if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                return false;
            }
            $existing = $userDao->findByEmail($data['email']);
            if ($existing && $existing->id !== $user->id) {
                return false;
            }
            $user->email = $data['email'];
        }

        $userDao->updateModel($user);
        LogDao::getInstance()->logAction($user->id, 'reset', '账户信息更新成功');
        Logger::info('账户信息更新成功');
        return true;
    }
}
