<?php

declare(strict_types=1);

namespace nova\plugin\login\controller;

use nova\framework\http\Response;
use nova\framework\route\Controller;
use nova\plugin\captcha\Captcha;
use nova\plugin\login\db\Dao\UserDao;
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
            return Response::asRedirect(LoginManager::getInstance()->getRedirect());
        }

        $view = new ViewResponse();
        $view->init(
            '',
            ['title' => $this->context->config('system_name', '管理后台')],
            '{',
            '}',
            ROOT_PATH . DS . 'nova' . DS . 'plugin' . DS . 'login' . DS . 'tpl' . DS
        );
        return $view->asTpl('index');
    }

    /**
     * 输出静态资源
     *
     * @param  string   $file 文件名
     * @return Response 返回静态资源响应
     */
    public function static($file): Response
    {
        $file = urldecode($file);
        $file = str_replace("..", "", $file);
        return Response::asStatic(ROOT_PATH . '/nova/plugin/login/static/' . $file);
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

        if (!isset($post['username'], $post['password'], $post['captcha'])) {
            return Response::asJson(['code' => 400, 'msg' => '缺少必要参数'], 400);
        }

        if (!Captcha::verify((int)$post['captcha'])) {
            return Response::asJson(['code' => 403, 'msg' => '验证码错误'], 403);
        }

        $userDao = UserDao::getInstance();
        $user = $userDao->login($post['username'], $post['password']);

        if (!$user) {
            return Response::asJson(['code' => 403, 'msg' => '用户名或密码错误'], 403);
        }

        LoginManager::getInstance()->login($user);
        $redirect = LoginManager::getInstance()->getRedirect();

        return Response::asJson(['code' => 200, 'msg' => '登录成功', 'data' => $redirect]);
    }

    /**
     * 用户登出
     *
     * @return Response 返回登出重定向响应
     */
    public function logout(): Response
    {
        LoginManager::getInstance()->logout();
        return Pjax::redirectTo("/login");
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
        $user = SSOLoginManager::getInstance()->handleCallback($code, $state);
        LoginManager::getInstance()->login($user);
        $redirect = LoginManager::getInstance()->getRedirect();
        return Response::asRedirect($redirect);
    }
}
