<?php

declare(strict_types=1);

namespace nova\plugin\login\controller;

use nova\framework\core\Context;
use nova\framework\core\Logger;
use nova\framework\http\Response;
use nova\framework\route\Controller;
use nova\plugin\captcha\Captcha;
use nova\plugin\cookie\Session;
use nova\plugin\login\db\Dao\UserDao;
use nova\plugin\login\LoginConfig;
use nova\plugin\login\LoginManager;
use nova\plugin\login\manager\SSOLoginManager;
use nova\plugin\tpl\Pjax;
use nova\plugin\tpl\ViewResponse;

/**
 * 登录入口控制器
 *
 * 处理登录页面展示、登录请求、登出等基础登录功能
 */
class Index extends Controller
{
    /**
     * 显示登录页面
     *
     * @return Response 返回登录页面响应
     */
    public function index(): Response
    {
        $user = LoginManager::getInstance()->checkLogin();
        if ($user) {
            Logger::info('User already logged in, redirecting', ['userId' => $user->id, 'username' => $user->username]);
            return Response::asRedirect(LoginManager::getInstance()->getRedirect());
        }

        Logger::debug('Login page accessed');
        $view = new ViewResponse();
        $view->init(
            '',
            ['title' => LoginConfig::getInstance()->systemName ?? "管理后台"],
            '{',
            '}',
            ROOT_PATH . DS . 'nova' . DS . 'plugin' . DS . 'login' . DS . 'tpl' . DS
        );
        return $view->asTpl('index');
    }

    /**
     * 输出验证码
     *
     * @return Response 返回验证码图片响应
     */
    public function captcha(): Response
    {
        $captcha = new Captcha();
        return $captcha->create();
    }

    /**
     * 处理登录请求
     *
     * @return Response 返回登录结果JSON响应
     */
    public function login(): Response
    {
        $post = $this->request->post();
        $username = $post['username'] ?? '';

        Logger::debug('Login attempt', ['username' => $username, 'hasCaptcha' => isset($post['captcha'])]);

        if (!isset($post['username'], $post['password'], $post['captcha'])) {
            Logger::warning('Login failed: missing parameters', ['username' => $username]);
            return Response::asJson(['code' => 400, 'msg' => '缺少必要参数'], 400);
        }

        if (!Captcha::verify((int)$post['captcha'])) {
            Logger::warning('Login failed: invalid captcha', ['username' => $username]);
            return Response::asJson(['code' => 403, 'msg' => '验证码错误'], 403);
        }

        $userDao = UserDao::getInstance();
        $user = $userDao->login($post['username'], $post['password']);

        if (!$user) {
            Logger::warning('Login failed: invalid credentials', ['username' => $username, 'ip' => $this->request->getClientIP()]);
            return Response::asJson(['code' => 403, 'msg' => '用户名或密码错误'], 403);
        }

        LoginManager::getInstance()->login($user);
        $redirect = LoginManager::getInstance()->getRedirect();
        Logger::info('Login successful', ['username' => $username, 'redirect' => $redirect]);

        return Response::asJson(['code' => 200, 'msg' => '登录成功', 'data' => $redirect]);
    }

    /**
     * 用户登出：销毁会话后展示退出确认页；配置了跳转地址则提供可选入口
     *
     * @return Response
     */
    public function logout(): Response
    {
        $user = LoginManager::getInstance()->checkLogin();
        if ($user) {
            Logger::info('User logout', ['userId' => $user->id, 'username' => $user->username]);
        }
        LoginManager::getInstance()->logout();

        $config = LoginConfig::getInstance();
        $redirect = trim($config->logoutRedirect);

        $view = new ViewResponse();
        $view->init(
            '',
            [
                'title' => $config->systemName ?? '管理后台',
                'logoutRedirect' => $redirect,
            ],
            '{',
            '}',
            ROOT_PATH . DS . 'nova' . DS . 'plugin' . DS . 'login' . DS . 'tpl' . DS
        );
        return $view->asTpl('logout');
    }

    /**
     * 处理SSO回调
     *
     * @return Response 返回重定向响应
     */
    public function callback(): Response
    {
        $code = $this->request->get('code', '');
        $state = $this->request->get('state', '');
        Logger::debug('SSO callback', ['code' => !empty($code), 'state' => $state]);

        // 复用缓存结果时不会经过 handleCallback，state 必须在这里与本会话绑定，否则拿到回调 URL 的任何人都能登录
        if ($state === '' || $state !== Session::getInstance()->get('sso_state')) {
            Logger::warning('SSO callback failed: invalid state');
            return Pjax::responseError(403);
        }

        // 授权码只能兑换一次：重复回调（刷新、并发）排队等第一个请求的结果并复用，而不是拿旧 code 再去兑换
        $lock = fopen(RUNTIME_PATH . DS . 'sso_callback.lock', 'c');
        flock($lock, LOCK_EX);
        try {
            $cache = Context::instance()->cache;
            $key = 'sso_code/' . md5($code . $state);
            $userId = $cache->get($key);
            $user = $userId === null
                ? SSOLoginManager::getInstance()->handleCallback($code, $state)
                : UserDao::getInstance()->id($userId);
            if ($user) {
                $cache->set($key, $user->id, 300);
            }
        } finally {
            flock($lock, LOCK_UN);
            fclose($lock);
        }

        if (!$user) {
            Logger::warning('SSO callback failed');
            return Pjax::responseError(403);
        }

        LoginManager::getInstance()->login($user);
        $redirect = LoginManager::getInstance()->getRedirect();
        Logger::info('SSO login successful', ['username' => $user->username, 'redirect' => $redirect]);
        return Response::asRedirect($redirect);
    }
}
