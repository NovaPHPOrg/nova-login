<?php

declare(strict_types=1);

namespace nova\plugin\login;

use Exception;
use nova\framework\core\Logger;
use nova\framework\core\StaticRegister;
use nova\framework\event\EventManager;
use nova\framework\route\ControllerException;
use nova\framework\route\RouteTrait;
use nova\plugin\cookie\Session;
use nova\plugin\login\db\Dao\RecordDao;
use nova\plugin\login\db\Model\RecordModel;
use nova\plugin\login\db\Model\UserModel;
use nova\plugin\login\manager\PwdLoginManager;
use nova\plugin\login\manager\SSOLoginManager;
use nova\plugin\login\route\LoginRouteObject;
use Throwable;

/**
 * 登录管理器
 *
 * 负责用户登录、登出、会话管理以及路由注册等功能
 *
 * @package nova\plugin\login
 * @since 1.0.0
 */
class LoginManager extends StaticRegister
{
    use RouteTrait;

    /**
     * 构造函数
     */
    public function __construct()
    {
        $this->registerRoutes();
    }

    /**
     * 注册 login 模块的路由规则
     *
     * @return void
     */
    private function registerRoutes(): void
    {
        // 不需要权限
        $this->get('/login', LoginRouteObject::build('index', 'index'));
        $this->get('/login/static/{file}', LoginRouteObject::build('index', 'static'));
        $this->get('/login/captcha', LoginRouteObject::build('index', 'captcha'));
        $this->post('/login', LoginRouteObject::build('index', 'login'));
        $this->get('/login/logout', LoginRouteObject::build('index', 'logout'));
        $this->get('/login/callback', LoginRouteObject::build('index', 'callback'));

        $this->get('/login/pwd/config', LoginRouteObject::build('pwd', 'config'));
        $this->post('/login/pwd/config', LoginRouteObject::build('pwd', 'save'));

        // OIDC认证登录
        $this->get('/login/oidc/config', LoginRouteObject::build('oidc', 'config'));
        $this->post('/login/oidc/config', LoginRouteObject::build('oidc', 'save'));

        // 用户列表
        $this->get('/login/user/list', LoginRouteObject::build('user', 'list'));
        $this->post('/login/user/update', LoginRouteObject::build('user', 'update'));
        $this->post('/login/user/remove', LoginRouteObject::build('user', 'remove'));
        $this->get('/login/user/{id}', LoginRouteObject::build('user', 'view'));

        // 角色列表
        $this->get('/login/role/list', LoginRouteObject::build('role', 'list'));
        $this->post('/login/role/update', LoginRouteObject::build('role', 'update'));
        $this->post('/login/role/remove', LoginRouteObject::build('role', 'remove'));
        $this->get('/login/role/{id}', LoginRouteObject::build('role', 'view'));
    }

    /**
     * 注册登录管理器相关信息
     *
     * @return void
     */
    public static function registerInfo(): void
    {
        EventManager::addListener("route.before", function ($event, &$uri) {
            if (!str_starts_with($uri, "/login")) {
                return;
            }

            $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
            $routeObj = self::getInstance()->dispatch($method, $uri);

            if ($routeObj !== null) {
                try {
                    $routeObj->checkSelf();
                    $routeObj->run();
                } catch (ControllerException $e) {
                    // 静默处理控制器异常
                }
            }
        }, 500);
    }

    /**
     * 用户登录
     *
     * @param  UserModel $user 用户模型
     * @return bool      登录成功返回true，失败返回false
     */
    public function login(UserModel $user): bool
    {
        try {
            $loginRecords = RecordDao::getInstance()->records($user);
            $allowed = max(1, LoginConfig::getInstance()->allowedLoginCount);

            while (count($loginRecords) >= $allowed) {
                $oldest = array_pop($loginRecords);
                if ($oldest instanceof RecordModel) {
                    RecordDao::getInstance()->deleteModel($oldest);
                }
            }

            $record = RecordDao::getInstance()->add($user->id);
            Session::getInstance()->set('__record', $record);
            return true;
        } catch (Exception $e) {
            Logger::error($e->getMessage(), $e->getTrace());
            return false;
        }
    }

    /**
     * 检查用户登录状态
     *
     * @return UserModel|null 返回当前登录用户，未登录返回null
     */
    public function checkLogin(): ?UserModel
    {
        $session = Session::getInstance();
        $requestUri = $_SERVER['REQUEST_URI'];
        $record = $session->get('__record');

        if (!$record instanceof RecordModel || RecordDao::getInstance()->id($record->id) === null) {
            $this->setRedirectUriIfNeeded($requestUri);
            return null;
        }

        $user = $record->user();
        if (!$user instanceof UserModel) {
            Session::getInstance()->delete('__record');
            $this->setRedirectUriIfNeeded($requestUri);
            return null;
        }

        return $user;
    }

    /**
     * 设置重定向URI（仅非POST请求）
     *
     * @param  string $uri 当前请求URI
     * @return void
     */
    private function setRedirectUriIfNeeded(string $uri): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            return;
        }
        if ($uri !== "/login") {
            $this->setRedirectUri($uri);
        }
    }

    /**
     * 设置重定向URI
     *
     * @param  string $uri 重定向地址
     * @return void
     */
    public function setRedirectUri(string $uri): void
    {
        Session::getInstance()->set('__redirect_uri', $uri);
    }

    /**
     * 获取重定向地址
     *
     * @return string 重定向地址
     */
    public function getRedirect(): string
    {
        $redirect = Session::getInstance()->get('__redirect_uri', LoginConfig::getInstance()->loginCallback);
        Session::getInstance()->delete('__redirect_uri');
        return $redirect ?: '/';
    }

    /**
     * 用户登出
     *
     * @return bool 登出成功返回true
     */
    public function logout(): bool
    {
        $session = Session::getInstance();
        $record = $session->get('__record');
        $dao = RecordDao::getInstance();

        try {
            if ($record instanceof RecordModel && $dao->id($record->id) !== null) {
                $dao->deleteModel($record);
            }
            return true;
        } catch (Throwable $e) {
            Logger::error($e->getMessage(), $e->getTrace());
            return false;
        } finally {
            $session->destroy();
        }
    }

    /**
     * 重定向到登录页面
     *
     * @return string 登录页面URL
     */
    public function redirectLogin(): string
    {
        if (LoginConfig::getInstance()->ssoEnable) {
            return SSOLoginManager::getInstance()->redirectToProvider();
        } else {
            return PwdLoginManager::getInstance()->redirectToProvider();
        }
    }
}
