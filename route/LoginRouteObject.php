<?php

declare(strict_types=1);

namespace nova\plugin\login\route;

use nova\framework\route\AbstractRouteObject;

/**
 * 登录模块路由对象
 *
 * 用于构建登录模块的路由
 *
 * @package nova\plugin\login\route
 * @since 1.0.0
 */
class LoginRouteObject extends AbstractRouteObject
{
    /**
     * 获取控制器类名
     *
     * @return string 控制器类名
     */
    protected function getControllerClass(): string
    {
        return "nova\\plugin\\login\\controller\\".ucfirst($this->controller);
    }

    /**
     * 构建路由对象
     *
     * @param  string $controller 控制器名称
     * @param  string $action     动作名称
     * @param  array  $params     路由参数
     * @return self   路由对象实例
     */
    public static function build(string $controller, string $action, array $params = []): self
    {
        return new LoginRouteObject('', $controller, $action, $params);
    }
}
