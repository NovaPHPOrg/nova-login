<?php

declare(strict_types=1);

namespace nova\plugin\login;

use nova\framework\http\Request;
use nova\framework\http\Response;
use nova\plugin\tpl\ViewResponse;

/**
 * 后台管理页面契约
 *
 * 实现此接口的插件可通过 {@see AdminPage::bind()} 一次性接入后台的
 * 路由注册、菜单展示与页面渲染。
 *
 * @package nova\plugin\login
 */
interface AdminPageInterface
{
    /**
     * 注册后台页面路由，指向宿主的后台控制器
     *
     * @param string $model      宿主后台控制器所在模块
     * @param string $controller 宿主后台控制器名称
     */
    public function registerRouter(string $model, string $controller): void;

    /**
     * 返回后台菜单项
     *
     * @return array
     */
    public function menu(): array;

    /**
     * 渲染后台页面，未命中返回 null
     *
     * @param  ViewResponse $view    视图响应对象
     * @param  Request      $request HTTP 请求
     * @return ?Response
     */
    public function route(ViewResponse $view, Request $request): ?Response;
}
