<?php

declare(strict_types=1);

namespace nova\plugin\login;

use nova\framework\cache\Cache;

use function nova\framework\config;

use nova\framework\core\Context;

use nova\framework\core\Logger;
use nova\framework\core\StaticRegister;
use nova\plugin\cookie\Session;
use nova\plugin\login\db\Dao\LogDao;
use nova\plugin\login\db\Model\UserModel;
use nova\plugin\device\UserAgent;
use nova\plugin\login\manager\PwdLoginManager;
use nova\plugin\login\manager\SSOLoginManager;

class LoginManager extends StaticRegister
{

    public static function registerInfo(): void
    {
        SSOLoginManager::register();
        PwdLoginManager::register();
    }

    public static function getInstance(): LoginManager
    {
        return Context::instance()->getOrCreateInstance("loginManager", function () {
            return new LoginManager();
        });
    }


    private LoginConfig $loginConfig;

    public function __construct()
    {
       $this->loginConfig = new LoginConfig();
    }

    /**
     * 用户登录时调用，记录登录token
     * 如果登录数量超过限制，会将最早的登录踢下线
     *
     * @param  ?UserModel $user 登录id
     * @return bool       登录是否成功
     */
    public function login(?UserModel $user): bool
    {
        try {
            $token = sha1(random_bytes(32));
            if (empty($user)) {
                return false;
            }

            // Get user's login records
            $loginRecords = $this->getCache()->get("user_logins:{$user->id}", []);

            // If login count exceeds limit, remove oldest login
            if (count($loginRecords) > $this->loginConfig->allowedLoginCount) {
                // Sort by timestamp
                usort($loginRecords, function ($a, $b) {
                    return $a['time'] <=> $b['time'];
                });

                // Remove oldest login record
                array_shift($loginRecords);

                // Log action
                LogDao::getInstance()->logAction($user->id, "login", "登录数量超过限制，最早的登录被踢下线");
            }
            $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
            [$OsName, $OsImg, $BrowserName, $BrowserImg] = UserAgent::parse($ua);
            $loginRecords[] = [
                'token' => $token,
                'time' => time(),
                'session_id' => Session::getInstance()->id(), // Store PHP's session ID instead
                'device' => "$OsImg $OsName $BrowserImg $BrowserName"
            ];

            // Save updated login records
            $this->getCache()->set("user_logins:{$user->id}", $loginRecords);

            // Set session
            Session::getInstance()->set('token', $token);
            Session::getInstance()->set('user', $user);

            return true;
        } catch (\Exception $e) {
            Logger::error($e->getMessage(), $e->getTrace());
            return false;
        }
    }

    /**
     * 判断用户是否登录
     * 通过比对 Session 中的 token 和缓存中的 token 来防止多地登录
     *
     * @return bool|UserModel 是否已登录
     */
    public function checkLogin(): ?UserModel
    {
        // Get token and user from session
        $token = Session::getInstance()->get('token', null);
        $user = Session::getInstance()->get('user', null);

        if (!is_string($token) || !is_object($user) || empty($user) || empty($token)) {
            return null;
        }

        // Get user's login records
        $loginRecords = $this->getCache()->get("user_logins:{$user->id}", []);
        // Get current session ID
        $currentSessionId = Session::getInstance()->id();

        // Check if current token is in valid login records and session ID matches
        $tokenValid = false;
        foreach ($loginRecords as $record) {
            if ($record['token'] === $token) {
                // If session_id exists in record, verify it matches current session
                if (!isset($record['session_id']) || $record['session_id'] !== $currentSessionId) {
                    // Session ID mismatch - possible session hijacking attempt
                    LogDao::getInstance()->logAction($user->id, "checkLogin", "会话ID不匹配，可能存在会话劫持。");
                    Session::getInstance()->destroy();
                    return false;
                }
                $tokenValid = true;
                break;
            }
        }

        if (!$tokenValid) {
            LogDao::getInstance()->logAction($user->id, "checkLogin", "登录已失效，当前账号退出。");
            Session::getInstance()->destroy();
            return null;
        }
        return $user;
    }

    /**
     * 获取缓存实例
     *
     * @return Cache
     */
    protected function getCache(): Cache
    {
        if ($this->cache === null) {
            $this->cache = Context::instance()->cache;
        }
        return $this->cache;
    }

    /**
     * @var Cache|null 缓存实例
     */
    protected ?Cache $cache = null;

    /**
     * 用户登出
     *
     * @param  int|null    $user_id 用户ID，如果为null则使用当前session中的用户
     * @param  string|null $token   登录token，如果为null且user_id不为null则退出所有会话，如果都为null则退出当前会话
     * @return bool        登出是否成功
     */
    public function logout(?int $user_id = null, ?string $token = null): bool
    {
        try {
            // 获取当前会话用户
            $currentUser = Session::getInstance()->get('user', null);
            $currentToken = Session::getInstance()->get('token', null);

            // 情况1: userid和token都为null - 退出当前会话
            if ($user_id === null && $token === null) {
                if (!is_object($currentUser) || empty($currentUser) || !is_string($currentToken) || empty($currentToken)) {
                    return false;
                }

                $user_id = $currentUser->id;
                $token = $currentToken;

                // 从缓存中移除当前token
                $this->removeTokenFromCache($user_id, $token);

                // 销毁当前会话
                Session::getInstance()->destroy();
                return true;
            }

            // 情况2: userid不为null但token为null - 退出所有会话
            if ($user_id !== null && $token === null) {
                // 清除该用户的所有登录记录
                $this->getCache()->delete("user_logins:$user_id");

                // 如果当前登录的用户就是要退出的用户，销毁当前会话
                if (is_object($currentUser) && $currentUser->id === $user_id) {
                    Session::getInstance()->destroy();
                }
                return true;
            }

            // 情况3: userid不为null且token不为null - 退出指定token会话
            if ($user_id !== null && is_string($token) && !empty($token)) {
                // 从缓存中移除指定token
                $this->removeTokenFromCache($user_id, $token);

                // 如果当前会话的token与要退出的token相同，销毁当前会话
                if (is_object($currentUser) && $currentUser->id === $user_id && $currentToken === $token) {
                    Session::getInstance()->destroy();
                }

                return true;
            }

            return false;
        } catch (\Exception $e) {
            Logger::error($e->getMessage(), $e->getTrace());
            return false;
        }
    }

    /**
     * 从缓存中移除指定用户的指定token
     *
     * @param  int    $user_id 用户ID
     * @param  string $token   要移除的token
     * @return void
     */
    private function removeTokenFromCache(int $user_id, string $token): void
    {
        // 获取用户的登录记录
        $loginRecords = $this->getCache()->get("user_logins:$user_id", []);

        // 移除指定token
        $loginRecords = array_filter($loginRecords, function ($record) use ($token) {
            return $record['token'] !== $token;
        });

        // 重新索引数组
        $loginRecords = array_values($loginRecords);

        // 更新登录记录
        $this->getCache()->set("user_logins:$user_id", $loginRecords);
    }

    public function redirectLogin(): string
    {
        if (config("sso.enable")) {
            return (new SSOLoginManager())->redirectToProvider();
        } else {
            return (new PwdLoginManager())->redirectToProvider();
        }
    }

}
