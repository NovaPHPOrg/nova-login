<?php

declare(strict_types=1);

namespace nova\plugin\login;

use nova\framework\core\Context;
use nova\framework\core\Logger;
use nova\framework\core\StaticRegister;
use nova\plugin\cookie\Session;
use nova\plugin\login\db\Dao\RecordDao;
use nova\plugin\login\db\Model\RecordModel;
use nova\plugin\login\db\Model\UserModel;
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
     * @param  UserModel $user 登录id
     * @return bool      登录是否成功
     */
    public function login(UserModel $user): bool
    {
        try {
            $loginRecords = RecordDao::getInstance()->records($user);

            while (count($loginRecords) > $this->loginConfig->allowedLoginCount) {
                //获取第一个一个记录
                $record = array_shift($loginRecords);
                RecordDao::getInstance()->deleteModel($record);
                $loginRecords = RecordDao::getInstance()->records($user);
            }

            $record = RecordDao::getInstance()->add($user->id);

            Session::getInstance()->set('record', $record);
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
     * @return ?UserModel 是否已登录
     */
    public function checkLogin(): ?UserModel
    {
        /**
         * @var $record RecordModel
         */
        $record = Session::getInstance()->get('record');
        if (!($record instanceof RecordModel)) {
            Session::getInstance()->destroy();
            return null;
        }
        if (RecordDao::getInstance()->id($record->id) == null) {
            Session::getInstance()->destroy();
            return null;
        }
        $user = $record->user();
        $sessionUser = Session::getInstance()->get('user');
        if ($sessionUser instanceof UserModel) {
            $user->display_name = $sessionUser->display_name;
            $user->avatar = $sessionUser->avatar;
        }

        if (empty($user)) {
            Session::getInstance()->destroy();
            return null;
        }

        return $user;
    }

    /**
     * 用户登出
     *
     * @return bool 登出是否成功
     */
    public function logout(): bool
    {
        $session = Session::getInstance();
        $record = $session->get('record');
        $dao = RecordDao::getInstance();

        try {
            // 如果确实拿到了有效的 RecordModel 且数据库中存在，再删除
            if ($record instanceof RecordModel
                && $dao->id($record->id) !== null
            ) {
                $dao->deleteModel($record);
            }
            return true;
        } catch (\Throwable $e) {
            Logger::error($e->getMessage(), $e->getTrace());
            return false;
        } finally {
            // 无论成功还是失败，都只在这里销毁 session
            $session->destroy();
        }
    }

    public function redirectLogin(): string
    {
        if ($this->loginConfig->ssoEnable) {
            return (new SSOLoginManager())->redirectToProvider();
        } else {
            return (new PwdLoginManager())->redirectToProvider();
        }
    }

}
