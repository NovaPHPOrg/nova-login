<?php

declare(strict_types=1);

namespace nova\plugin\login;

use nova\framework\core\Instance;
use nova\framework\http\Request;
use nova\framework\http\Response;
use nova\framework\route\Route;
use nova\plugin\tpl\ViewException;
use nova\plugin\tpl\ViewResponse;

/**
 * 登录模板管理器
 *
 * 负责登录相关页面的路由注册和模板渲染
 *
 * @package nova\plugin\login
 * @since 1.0.0
 */
class LoginTpl extends Instance
{
    /**
     * 注册登录页面路由
     *
     * @param  string $model      模型名称
     * @param  string $controller 控制器名称
     * @return void
     */
    public function registerRouter(string $model, string $controller): void
    {
        $default = \nova\framework\route($model, $controller, "init");
        Route::getInstance()
            ->get('/login/pwd', $default)
            ->get('/login/oidc', $default)
            ->get('/login/user', $default)
            ->get('/login/role', $default);
    }

    /**
     * 处理路由并返回模板响应
     *
     * @param  ViewResponse  $viewResponse 视图响应对象
     * @param  Request       $request      HTTP请求对象
     * @return Response      视图响应
     * @throws ViewException 模板异常
     */
    public function route(ViewResponse $viewResponse, Request $request): Response
    {
        $uri = $request->getUri();
        $data = explode('/', $uri);

        if (sizeof($data) !== 3) {
            return Response::asRedirect("/404");
        }

        $action = trim($data[2]);

        return $viewResponse->asTpl(ROOT_PATH . DS . 'nova/plugin/login/tpl/' . $action . ".tpl");
    }
}
