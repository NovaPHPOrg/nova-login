<?php

declare(strict_types=1);

namespace nova\plugin\login;

use nova\framework\event\EventManager;

/**
 * 后台页面绑定器
 *
 * 把一个 {@see AdminPageInterface} 接入后台的三个事件：路由注册、菜单、页面渲染。
 * 消除各插件 Manager 中重复的事件挂载代码。
 *
 * @package nova\plugin\login
 */
final class AdminPage
{
    /**
     * 绑定后台页面到 admin.router / admin.menu / admin.init 事件
     *
     * @param AdminPageInterface $page 后台页面实现
     */
    public static function bind(AdminPageInterface $page): void
    {
        EventManager::addListener('admin.router', function ($event, $route) use ($page) {
            $page->registerRouter($route[0], $route[1]);
        });

        EventManager::addListener('admin.menu', function ($event, &$menu) use ($page) {
            $item = $page->menu();
            // 允许页面返回空菜单以退出菜单展示（仅注册路由/渲染）
            if (!empty($item)) {
                $menu[] = $item;
            }
        });

        EventManager::addListener('admin.init', function ($event, &$data) use ($page) {
            [$view, , $request] = $data;
            return $page->route($view, $request);
        });
    }
}
