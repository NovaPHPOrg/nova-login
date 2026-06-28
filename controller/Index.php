<?php

declare(strict_types=1);

namespace nova\plugin\login\controller;

use nova\framework\core\Logger;
use nova\framework\http\Response;
use nova\framework\route\Controller;
use nova\plugin\captcha\Captcha;
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
     * 用户登出
     *
     * @return Response 返回登出重定向响应
     */
    public function logout(): Response
    {
        $user = LoginManager::getInstance()->checkLogin();
        if ($user) {
            Logger::info('User logout', ['userId' => $user->id, 'username' => $user->username]);
        }
        LoginManager::getInstance()->logout();
        return Pjax::redirectTo(LoginConfig::getInstance()->logoutRedirect);
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

        $user = SSOLoginManager::getInstance()->handleCallback($code, $state);
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
