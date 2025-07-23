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

/**
 * 登录管理器
 *
 * 负责处理用户登录、登出、登录状态检查等功能
 * 支持多种登录方式（密码登录、SSO登录）
 * 提供登录数量限制和会话管理功能
 *
 * @package nova\plugin\login
 */
class LoginManager extends StaticRegister
{
    /**
     * 注册登录管理器相关信息
     *
     * 在系统启动时注册各种登录管理器
     */
    public static function registerInfo(): void
    {
        SSOLoginManager::register();
        PwdLoginManager::register();
    }

    /**
     * 获取登录管理器实例
     *
     * 使用单例模式，确保全局只有一个登录管理器实例
     *
     * @return LoginManager 登录管理器实例
     */
    public static function getInstance(): LoginManager
    {
        return Context::instance()->getOrCreateInstance("loginManager", function () {
            return new LoginManager();
        });
    }

    /** @var LoginConfig 登录配置对象 */
    private LoginConfig $loginConfig;

    /**
     * 构造函数
     *
     * 初始化登录配置
     */
    public function __construct()
    {
        $this->loginConfig = new LoginConfig();
    }

    /**
     * 用户登录
     *
     * 记录用户登录信息，如果登录数量超过限制，会将最早的登录踢下线
     * 登录成功后会创建会话记录并保存用户信息到Session中
     *
     * @param  UserModel $user 用户模型对象
     * @return bool      登录是否成功
     */
    public function login(UserModel $user): bool
    {
        try {
            // 获取用户当前的登录记录
            $loginRecords = RecordDao::getInstance()->records($user);

            // 如果登录数量超过限制，删除最早的登录记录
            while (count($loginRecords) > $this->loginConfig->allowedLoginCount) {
                // 获取第一个（最早）的登录记录
                $record = array_shift($loginRecords);
                // 从数据库中删除该记录
                RecordDao::getInstance()->deleteModel($record);
                // 重新获取登录记录列表
                $loginRecords = RecordDao::getInstance()->records($user);
            }

            // 创建新的登录记录
            $record = RecordDao::getInstance()->add($user->id);

            // 将登录记录和用户信息保存到Session中
            Session::getInstance()->set('record', $record);
            Session::getInstance()->set('user', $user);
            return true;
        } catch (\Exception $e) {
            // 记录错误日志
            Logger::error($e->getMessage(), $e->getTrace());
            return false;
        }
    }

    /**
     * 检查用户登录状态
     *
     * 通过比对Session中的token和数据库中的记录来验证登录状态
     * 防止多地登录和会话劫持
     *
     * @return UserModel|null 如果已登录返回用户模型，否则返回null
     */
    public function checkLogin(): ?UserModel
    {
        // 从Session中获取登录记录
        $record = Session::getInstance()->get('record');

        // 检查登录记录是否存在且有效
        if (!($record instanceof RecordModel)) {
            Session::getInstance()->set('redirect_uri', $_SERVER['REQUEST_URI']);
            return null;
        }

        // 检查数据库中是否还存在该登录记录
        if (RecordDao::getInstance()->id($record->id) == null) {
            Session::getInstance()->set('redirect_uri', $_SERVER['REQUEST_URI']);
            return null;
        }

        // 获取用户信息
        $user = $record->user();
        $sessionUser = Session::getInstance()->get('user');

        // 如果Session中有用户信息，使用Session中的显示名称和头像
        if ($sessionUser instanceof UserModel) {
            $user->display_name = $sessionUser->display_name;
            $user->avatar = $sessionUser->avatar;
        }

        // 如果用户信息为空，销毁Session并返回null
        if (empty($user)) {
            Session::getInstance()->set('redirect_uri', $_SERVER['REQUEST_URI']);
            return null;
        }

        return $user;
    }

    /**
     * 用户登出
     *
     * 删除登录记录并销毁Session
     *
     * @return bool 登出是否成功
     */
    public function logout(): bool
    {
        $session = Session::getInstance();
        $record = $session->get('record');
        $dao = RecordDao::getInstance();

        try {
            // 如果确实拿到了有效的RecordModel且数据库中存在，再删除
            if ($record instanceof RecordModel
                && $dao->id($record->id) !== null
            ) {
                $dao->deleteModel($record);
            }
            return true;
        } catch (\Throwable $e) {
            // 记录错误日志
            Logger::error($e->getMessage(), $e->getTrace());
            return false;
        } finally {
            // 无论成功还是失败，都只在这里销毁session
            $session->destroy();
        }
    }

    /**
     * 重定向到登录页面
     *
     * 根据配置决定使用SSO登录还是密码登录
     *
     * @return string 登录页面的URL
     */
    public function redirectLogin(): string
    {
        if ($this->loginConfig->ssoEnable) {
            // 如果启用了SSO，重定向到SSO提供商
            return (new SSOLoginManager())->redirectToProvider();
        } else {
            // 否则使用密码登录
            return (new PwdLoginManager())->redirectToProvider();
        }
    }

}
