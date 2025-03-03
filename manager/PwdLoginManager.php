<?php
declare(strict_types=1);

namespace nova\plugin\login\manager;

use app\db\Dao\LogDao;
use nova\framework\cache\Cache;
use nova\framework\core\Context;
use nova\framework\core\Logger;
use nova\framework\event\EventManager;
use nova\framework\exception\AppExitException;
use nova\framework\http\Response;
use nova\plugin\login\db\Dao\UserDao;
use nova\plugin\login\db\Model\UserModel;
use nova\plugin\login\LoginManager;
use nova\plugin\tpl\ViewResponse;
use function nova\framework\config;

class PwdLoginManager extends BaseLoginManager
{


    public static function register(): void
    {
        EventManager::addListener("route.before", function ($event, &$uri) {
            if (!str_starts_with("/login", $uri)) return;
            $pwd = new PwdLoginManager();
            if ($uri === "/login") {

                //渲染登录页面
                $viewResponse = new ViewResponse();
                $viewResponse->init(
                    null,
                    [
                        "name" => config('name') ?? "AnkioのLogin"
                    ],
                    false,
                    "{", "}",
                    ROOT_PATH . DS . "nova" . DS . "plugin" . DS . "login" . DS . "tpl" . DS
                );
                $viewResponse->asTpl("index");
                throw new AppExitException($viewResponse, "Exit by Login");
            } elseif ($uri === "/login/pwd") {
                //使用账号密码进行登录
                $user = $pwd->authenticate($_POST);
                if ($user === false) {
                    throw new AppExitException(Response::asJson(['code' => 403, "msg" => "登录失败"]), "Exit by Login");
                }
                LoginManager::getInstance()->login($user);
                throw new AppExitException(Response::asJson(['code' => 200, "msg" => "登录成功"]), "Exit by Login");
            } elseif ($uri === "/login/reset") {
                $user = LoginManager::getInstance()->checkLogin();
                if ($pwd->reset($_POST, $user)) {
                    LoginManager::getInstance()->logout($user->id);
                    throw new AppExitException(Response::asJson(['code' => 301, "msg" => "操作成功", "data" => "/login"]), "Exit by Login");
                }
                throw new AppExitException(Response::asJson(['code' => 403, "msg" => "重置失败"]), "Exit by Login");
            }
        });
    }

    protected Cache $cache;


    public function __construct()
    {
        $this->cache = new Cache();
    }

    /**
     * Authenticate a user with username and password
     *
     * @param array $credentials Should contain 'username' and 'password' keys
     * @return bool|UserModel Whether authentication was successful
     */
    public function authenticate(array $credentials): bool|UserModel
    {
        // Validate required credentials
        if (!isset($credentials['email']) || !isset($credentials['password'])) {
            return false;
        }

        // Check if IP is blocked
        $ip = Context::instance()->request()->getClientIP();
        $blockInfo = $this->checkIpBlocked($ip);
        if ($blockInfo['blocked']) {
            Logger::warning("IP $ip 因为多次尝试登录失败而被封禁", $credentials);
            // IP is currently blocked
            return false;
        }

        $user = UserDao::getInstance()->login($credentials['email'], $credentials['password']);

        if ($user) {
            // Login successful, reset failed attempts
            $this->resetFailedAttempts($ip);
            LogDao::getInstance()->logAction($user->id, "登录成功");
            return $user;
        } else {
            // Login failed, record the attempt
            $this->recordFailedAttempt($ip);
            Logger::warning("IP $ip 登录失败", $credentials);
            return false;
        }
    }

    /**
     * Check if an IP is currently blocked
     *
     * @param string $ip The IP address to check
     * @return array Block status and information
     */
    protected function checkIpBlocked(string $ip): array
    {
        $blockKey = "login:blocked:{$ip}";
        $blockData = $this->cache->get($blockKey);

        if ($blockData) {
            $blockDuration = 300 * $blockData['attempts']; // 5 minutes * number of attempts

            // Check if block has expired
            if (time() < $blockData['time'] + $blockDuration) {
                return [
                    'blocked' => true,
                    'remaining' => ($blockData['time'] + $blockDuration) - time()
                ];
            } else {
                // Block expired, remove it
                $this->cache->delete($blockKey);
            }
        }

        return ['blocked' => false];
    }

    /**
     * Record a failed login attempt
     *
     * @param string $ip The IP address to record
     * @return void
     */
    protected function recordFailedAttempt(string $ip): void
    {
        $attemptsKey = "login:attempts:{$ip}";
        $attempts = (int)$this->cache->get($attemptsKey, 0);

        $attempts++;
        $this->cache->set($attemptsKey, $attempts, 3600); // Store for 1 hour

        // Block IP after 3 failed attempts
        if ($attempts >= 3) {
            $blockKey = "login:blocked:{$ip}";
            $blockCount = ceil($attempts / 3);

            $this->cache->set($blockKey, [
                'time' => time(),
                'attempts' => $blockCount
            ], 86400); // Store for 24 hours (max block time)

            // Reset failed attempts counter
            $this->cache->set($attemptsKey, 0, 3600);
        }
    }

    /**
     * Reset failed attempts for an IP after successful login
     *
     * @param string $ip The IP address to reset
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
     * Reset user password
     *
     * @param array $data Should contain 'current_password' and 'new_password' keys
     * @return bool Whether password reset was successful
     * @throws AppExitException
     */
    private function reset(array $data, UserModel $user): bool
    {
        // Validate required fields
        if (!isset($data['current_password']) || !isset($data['new_password'])) {
            return false;
        }

        // Verify current password
        $userDao = UserDao::getInstance();
        $isCurrentPasswordValid = $userDao->login($user->email, $data['current_password']) !== null;

        if (!$isCurrentPasswordValid) {
            Logger::warning("Failed password reset attempt - invalid current password", [
                'user_id' => $user->id,
                'ip' => Context::instance()->request()->getClientIP()
            ]);
            return false;
        }

        // Validate new password requirements
        if (strlen($data['new_password']) < 8) {
            throw new AppExitException(Response::asJson(['code' => 403, "msg" => "密码过短"]), "Exit by Change Password");
        }
        $user->password = password_hash($data['new_password'], PASSWORD_DEFAULT);

        $userDao->updateModel($user);

        LogDao::getInstance()->logAction($user->id, "密码重置成功");
        Logger::info("reset password - success");
        return true;
    }

}