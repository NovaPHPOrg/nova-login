<?php

declare(strict_types=1);

namespace nova\plugin\login\manager;

use nova\framework\cache\Cache;

use function nova\framework\config;

use nova\framework\core\Context;
use nova\framework\core\Logger;
use nova\framework\event\EventManager;
use nova\framework\exception\AppExitException;
use nova\framework\http\Response;
use nova\plugin\cookie\Session;
use nova\plugin\login\captcha\CaptchaManager;
use nova\plugin\login\db\Dao\LogDao;
use nova\plugin\login\db\Dao\UserDao;
use nova\plugin\login\db\Model\UserModel;
use nova\plugin\login\LoginManager;
use nova\plugin\tpl\ViewResponse;

class PwdLoginManager extends BaseLoginManager
{
    public static function register(): void
    {
        EventManager::addListener("route.before", function ($event, &$uri) {

            if (!str_starts_with($uri, "/login")) {
                return;
            }
            Session::getInstance()->start();
            UserDao::getInstance()->initTable();
            $redirect = config("login_callback") ?? "/";
            $pwd = new PwdLoginManager();

            if ($uri === "/login") {
                if (LoginManager::getInstance()->checkLogin()) {
                    throw new AppExitException(Response::asRedirect($redirect));
                }
                //渲染登录页面
                $viewResponse = new ViewResponse();
                $viewResponse->init(
                    "",
                    [
                        "title" => config('system.name') ?? "登录",
                    ],
                    false,
                    "{",
                    "}",
                    ROOT_PATH . DS . "nova" . DS . "plugin" . DS . "login" . DS . "tpl" . DS
                );

                throw new AppExitException($viewResponse->asTpl("index"), "Exit by Login");
            } elseif ($uri === "/login/pwd") {
                //使用账号密码进行登录
                $user = $pwd->authenticate($_POST);
                if ($user === false) {
                    throw new AppExitException(Response::asJson(['code' => 403, "msg" => "登录失败", "need_captcha" => $pwd->needCaptcha()]), "Exit by Login");
                }
                LoginManager::getInstance()->login($user);
                throw new AppExitException(Response::asJson(['code' => 200, "msg" => "登录成功", "data" => $redirect]), "Exit by Login");
            } elseif ($uri === "/login/need_captcha") {
                throw new AppExitException(Response::asJson(['code' => 200, "data" => $pwd->needCaptcha()]), "Exit by Login");
            } elseif ($uri === "/login/captcha") {
                $pwd->generateCaptcha();
                exit();
            } elseif ($uri === "/login/reset") {
                $user = LoginManager::getInstance()->checkLogin();
                if ($user && $pwd->reset($_POST, $user)) {
                    LoginManager::getInstance()->logout($user->id);
                    throw new AppExitException(Response::asJson(['code' => 200, "msg" => "操作成功", "data" => "/login"]), "Exit by Login");
                }
                throw new AppExitException(Response::asJson(['code' => 403, "msg" => "重置失败"]), "Exit by Login");
            }
        });
    }

    protected Cache $cache;
    protected const CAPTCHA_THRESHOLD = 2; // 登录失败2次后需要验证码

    public function __construct()
    {
        $this->cache = Context::instance()->cache;
    }

    /**
     * 生成验证码图片
     */
    protected function generateCaptcha(): void
    {
        $captcha = new CaptchaManager();
        $captcha->generate();
    }

    /**
     * 验证验证码
     */
    protected function validateCaptcha(?string $code): bool
    {
        if ($code === null) {
            return false;
        }
        $captcha = new CaptchaManager();
        return $captcha->validate($code);
    }

    /**
     * 检查是否需要验证码
     */
    public function needCaptcha(): bool
    {
        $ip = Context::instance()->request()->getClientIP();
        $attemptsKey = "login:attempts:{$ip}";
        $attempts = (int)$this->cache->get($attemptsKey, 0);
        return $attempts >= self::CAPTCHA_THRESHOLD;
    }

    /**
     * 使用用户名和密码验证用户
     *
     * @param  array          $credentials 应包含 'username' 和 'password' 键
     * @return bool|UserModel 验证是否成功
     */
    public function authenticate(array $credentials): bool|UserModel
    {
        // 验证必需的凭据
        if (!isset($credentials['email']) || !isset($credentials['password'])) {
            return false;
        }
        $ip = Context::instance()->request()->getClientIP();
        // 在调试模式下跳过IP封禁
        if (!Context::instance()->isDebug()) {
            // 检查IP是否被封禁
            $blockInfo = $this->checkIpBlocked($ip);
            if ($blockInfo['blocked']) {
                Logger::warning("IP $ip 因为多次尝试登录失败而被封禁", $credentials);
                return false;
            }

            // 检查是否需要验证码
            if ($this->needCaptcha()) {
                if (!isset($credentials['captcha']) || !$this->validateCaptcha($credentials['captcha'])) {
                    Logger::warning("IP $ip 验证码验证失败", $credentials);
                    return false;
                }
            }
        }

        $user = UserDao::getInstance()->login($credentials['email'], $credentials['password']);

        if ($user) {
            // 登录成功，如果不是调试模式则重置失败尝试次数
            if (!Context::instance()->isDebug()) {
                $this->resetFailedAttempts($ip);
            }
            LogDao::getInstance()->logAction($user->id, "login", "登录成功");
            return $user;
        } else {
            $this->recordFailedAttempt($ip);
            Logger::warning("IP $ip 登录失败", $credentials);
            // 登录失败，如果不是调试模式则记录尝试

            return false;
        }
    }

    /**
     * 检查IP是否当前被封禁
     *
     * @param  string $ip 要检查的IP地址
     * @return array  封禁状态和信息
     */
    protected function checkIpBlocked(string $ip): array
    {
        $blockKey = "login:blocked:{$ip}";
        $blockData = $this->cache->get($blockKey);

        if ($blockData) {
            $blockDuration = 300 * $blockData['attempts']; // 5分钟 * 尝试次数

            // 检查封禁是否已过期
            if (time() < $blockData['time'] + $blockDuration) {
                return [
                    'blocked' => true,
                    'remaining' => ($blockData['time'] + $blockDuration) - time()
                ];
            } else {
                // 封禁已过期，移除它
                $this->cache->delete($blockKey);
            }
        }

        return ['blocked' => false];
    }

    /**
     * 记录失败的登录尝试
     *
     * @param  string $ip 要记录的IP地址
     * @return void
     */
    protected function recordFailedAttempt(string $ip): void
    {
        $attemptsKey = "login:attempts:{$ip}";
        $attempts = (int)$this->cache->get($attemptsKey, 0);

        $attempts++;
        $this->cache->set($attemptsKey, $attempts, 3600); // 存储1小时

        // 3次失败尝试后封禁IP
        if ($attempts >= 3 && !Context::instance()->isDebug()) {
            $blockKey = "login:blocked:{$ip}";
            $blockCount = ceil($attempts / 3);

            $this->cache->set($blockKey, [
                'time' => time(),
                'attempts' => $blockCount
            ], 86400); // 存储24小时（最大封禁时间）

            // 重置失败尝试计数器
            $this->cache->set($attemptsKey, 0, 3600);
        }
    }

    /**
     * 登录成功后重置IP的失败尝试次数
     *
     * @param  string $ip 要重置的IP地址
     * @return void
     */
    protected function resetFailedAttempts(string $ip): void
    {
        $attemptsKey = "login:attempts:{$ip}";
        $this->cache->delete($attemptsKey);
    }

    public function redirectToProvider(): string
    {
        return "/login";
    }

    /**
     * 重置用户密码
     *
     * @param  array            $data 应包含 'current_password' 和 'new_password' 键
     * @return bool             密码重置是否成功
     * @throws AppExitException
     */
    private function reset(array $data, UserModel $user): bool
    {

        // 验证当前密码
        if (!isset($data['current_password'])) {
            return false;
        }

        // 验证当前密码
        $userDao = UserDao::getInstance();
        $isCurrentPasswordValid = $userDao->login($user->email, $data['current_password']) !== null;

        if (!$isCurrentPasswordValid) {
            Logger::warning("密码重置失败 - 当前密码无效", [
                'user_id' => $user->id,
                'ip' => Context::instance()->request()->getClientIP()
            ]);
            return false;
        }

        // 如果提供了新密码，则更新密码
        if (!empty($data['new_password'])) {
            // 验证新密码要求
            if (strlen($data['new_password']) < 8) {
                throw new AppExitException(Response::asJson(['code' => 403, "msg" => "密码过短"]), "Exit by Change Mail");
            }
            $user->password = password_hash($data['new_password'], PASSWORD_DEFAULT);
        }

        // 如果提供了新邮箱，则更新邮箱
        if (isset($data['email'])) {
            // 验证邮箱格式
            if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                throw new AppExitException(Response::asJson(['code' => 403, "msg" => "邮箱格式不正确"]), "Exit by Change Email");
            }

            // 检查邮箱是否已被其他用户使用
            $existingUser = $userDao->findByEmail($data['email']);
            if ($existingUser && $existingUser->id !== $user->id) {
                throw new AppExitException(Response::asJson(['code' => 403, "msg" => "该邮箱已被使用"]), "Exit by Change Email");
            }

            $user->email = $data['email'];
        }

        $userDao->updateModel($user);

        LogDao::getInstance()->logAction($user->id, "reset", "账户信息更新成功");
        Logger::info("重置账户信息 - 成功");
        return true;
    }

}
