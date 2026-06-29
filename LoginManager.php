<?php

declare(strict_types=1);

namespace nova\plugin\login;

use Exception;
use nova\framework\core\Logger;
use nova\framework\core\StaticRegister;
use nova\framework\event\EventManager;
use nova\framework\exception\AppExitException;
use nova\framework\http\Response;
use nova\framework\route\RouteTrait;
use nova\plugin\cookie\Session;
use nova\plugin\login\db\Dao\RecordDao;
use nova\plugin\login\db\Dao\UserDao;
use nova\plugin\login\db\Model\RecordModel;
use nova\plugin\login\db\Model\UserModel;
use nova\plugin\login\manager\PwdLoginManager;
use nova\plugin\login\manager\SSOLoginManager;
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
        $this->controllerNamespace = 'nova\\plugin\\login\\controller\\';
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
        $this->get('/login', $this->map('index', 'index'));
        $this->get('/login/captcha', $this->map('index', 'captcha'));
        $this->post('/login', $this->map('index', 'login'));
        $this->get('/login/logout', $this->map('index', 'logout'));
        $this->get('/login/callback', $this->map('index', 'callback'));

        $this->get('/login/pwd/config', $this->map('pwd', 'config'));
        $this->post('/login/pwd/config', $this->map('pwd', 'save'));

        // OIDC认证登录
        $this->get('/login/oidc/config', $this->map('oidc', 'config'));
        $this->post('/login/oidc/config', $this->map('oidc', 'save'));

        // 用户列表
        $this->get('/login/user/list', $this->map('user', 'list'));
        $this->get('/login/user/records', $this->map('user', 'records'));
        $this->post('/login/user/kick', $this->map('user', 'kick'));
        $this->post('/login/user/update', $this->map('user', 'update'));
        $this->post('/login/user/remove', $this->map('user', 'remove'));
        $this->get('/login/user/{id}', $this->map('user', 'view'));

        // 角色列表
        $this->get('/login/role/list', $this->map('role', 'list'));
        $this->post('/login/role/update', $this->map('role', 'update'));
        $this->post('/login/role/remove', $this->map('role', 'remove'));
        $this->get('/login/role/{id}', $this->map('role', 'view'));
    }

    /**
     * 注册登录管理器相关信息
     *
     * @return void
     */
    public static function registerInfo(): void
    {
        self::getInstance()->bindPrefixDispatch('/login');
        AdminPage::bind(LoginTpl::getInstance());

        // 插件静态资源统一入口：/{plugin}/static/{file}，公开访问，无需权限
        // 优先级高于插件前缀分发（500），命中即直接输出文件
        EventManager::addListener('route.before', function ($event, $uri) {
            self::serveStaticAsset($uri);
        }, 100);
    }

    /**
     * 服务插件静态资源
     *
     * 约定所有插件静态资源位于 nova/plugin/{plugin}/static/ 下，
     * 通过 /{plugin}/static/{file} 统一访问。命中且文件合法时直接输出并终止请求；
     * 否则静默返回，交由后续路由处理。
     *
     * @param  string           $uri 当前请求 URI
     * @throws AppExitException 命中静态资源时抛出以输出响应
     */
    private static function serveStaticAsset(string $uri): void
    {
        if (!preg_match('#^/([a-z0-9_]+)/static/(.+)$#i', $uri, $matches)) {
            return;
        }

        $base = realpath(ROOT_PATH . DS . 'nova' . DS . 'plugin' . DS . $matches[1] . DS . 'static');
        if ($base === false) {
            return;
        }

        $real = realpath($base . DS . urldecode($matches[2]));
        if ($real === false || !str_starts_with($real, $base . DS)) {
            return;
        }

        throw new AppExitException(Response::asStatic($real));
    }

    /**
     * 用户登录
     *
     * @param  UserModel $user 用户模型
     * @return bool      登录成功返回true，失败返回false
     */
    public function login(UserModel $user): bool
    {
        Logger::debug('Login attempt', [
            'userId' => $user->id,
            'username' => $user->username,
        ]);

        try {
            $loginRecords = RecordDao::getInstance()->records($user);
            $allowed = max(1, LoginConfig::getInstance()->allowedLoginCount);

            while (count($loginRecords) >= $allowed) {
                $oldest = array_pop($loginRecords);
                if ($oldest instanceof RecordModel) {
                    Logger::info('Expired login record deleted', ['recordId' => $oldest->id]);
                    RecordDao::getInstance()->deleteModel($oldest);
                }
            }

            $record = RecordDao::getInstance()->add($user->id);
            Session::getInstance()->set('__record', $record);
            Logger::info('User login successful', [
                'userId' => $user->id,
                'username' => $user->username,
                'recordId' => $record->id,
            ]);
            return true;
        } catch (Exception $e) {
            Logger::error('Login failed', [
                'userId' => $user->id ?? 'unknown',
                'username' => $user->username ?? 'unknown',
                'message' => $e->getMessage(),
                'trace' => $e->getTrace(),
            ]);
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
        UserDao::getInstance()->initTable();
        $session = Session::getInstance();
        $requestUri = $_SERVER['REQUEST_URI'];
        $record = $session->get('__record');

        Logger::debug('Checking login status', ['requestUri' => $requestUri, 'hasRecord' => $record !== null]);

        if (!$record instanceof RecordModel || RecordDao::getInstance()->id($record->id) === null) {
            Logger::info('Login check failed: invalid record', ['requestUri' => $requestUri]);
            $this->setRedirectUriIfNeeded($requestUri);
            return null;
        }

        $user = $record->user();
        if (!$user instanceof UserModel) {
            Logger::info('Login check failed: user not found', ['recordId' => $record->id, 'requestUri' => $requestUri]);
            Session::getInstance()->delete('__record');
            $this->setRedirectUriIfNeeded($requestUri);
            return null;
        }

        Logger::debug('Login check passed', ['userId' => $user->id, 'username' => $user->username, 'requestUri' => $requestUri]);
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

        Logger::debug('Logout attempt', ['hasRecord' => $record !== null]);

        try {
            if ($record instanceof RecordModel && $dao->id($record->id) !== null) {
                $dao->deleteModel($record);
                Logger::info('Login record deleted', ['recordId' => $record->id]);
            }
            return true;
        } catch (Throwable $e) {
            Logger::error('Logout failed', ['message' => $e->getMessage(), 'trace' => $e->getTrace()]);
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
        Logger::debug('Redirect to login called');
        if (LoginConfig::getInstance()->ssoEnable) {
            $url = SSOLoginManager::getInstance()->redirectToProvider();
        } else {
            $url = PwdLoginManager::getInstance()->redirectToProvider();
        }
        return $url;
    }
}
