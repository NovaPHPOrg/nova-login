<?php

declare(strict_types=1);

namespace nova\plugin\login\route;

use nova\framework\core\Instance;
use nova\framework\http\Request;
use nova\plugin\login\db\Model\UserModel;

/**
 * 权限路由器
 *
 * 负责检查用户是否有权限访问特定的路由
 *
 * @package nova\plugin\login\route
 * @since 1.0.0
 */
class PermissionRouter extends Instance
{
    /**
     * 权限定义
     *
     * 键为权限名称，值为权限规则数组
     * 规则格式: "METHOD /path" 或 "ANY /path"
     *
     * @var array
     */
    private array $permissions = [
        'user_manage' => [
            'ANY /login/pwd',
            'ANY /login/oidc',
            'ANY /login/user*',
            'ANY /login/role*',
        ],
    ];

    private array $display = [
        'all'  => '全部权限',
        'user_manage' => "用户管理",
    ];

    /**
     * 判断用户是否有权限访问
     *
     * @param  Request   $request   HTTP请求对象
     * @param  UserModel $userModel 用户模型
     * @return bool      有权限返回true，否则返回false
     */
    public function hasPermission(Request $request, UserModel $userModel): bool
    {
        $role = $userModel->role();
        if (empty($role->permissions)) {
            return false;
        }

        if (in_array('all', $role->permissions)) {
            return true;
        }

        $uri = $request->getUri();
        $method = $request->getRequestMethod();

        foreach ($role->permissions as $permissionName) {
            if (!isset($this->permissions[$permissionName])) {
                continue;
            }

            foreach ($this->permissions[$permissionName] as $rule) {
                $parts = explode(' ', $rule, 2);
                if (count($parts) !== 2) {
                    continue;
                }

                [$ruleMethod, $rulePath] = $parts;

                if ($ruleMethod !== 'ANY' && $ruleMethod !== $method) {
                    continue;
                }

                if ($this->matchUri($rulePath, $uri)) {
                    return true;
                }
            }
        }

        return true;
    }

    /**
     * 匹配URI（支持 * 通配符）
     *
     * @param  string $pattern 匹配模式
     * @param  string $uri     待匹配的URI
     * @return bool   匹配成功返回true
     */
    private function matchUri(string $pattern, string $uri): bool
    {
        $pattern = str_replace('*', '.*', $pattern);
        return (bool)preg_match('#^' . $pattern . '$#', $uri);
    }

    /**
     * 获取所有权限定义
     *
     * @return array 权限定义数组
     */
    public function permissions(): array
    {
        return $this->display;
    }

    /**
     * 注册新的权限
     *
     * @param  string $name        权限名称
     * @param  array  $permissions 权限规则数组
     * @return void
     */
    public function registerPermissions(string $display, string $name, array $permissions): void
    {
        $this->permissions[$name] = $permissions;
        $this->display[$name] = $display;
    }

    public function filterMenu(array $menus, array $permissions): array
    {
        // 如果用户拥有all权限，返回所有菜单
        if (in_array('all', $permissions)) {
            return $menus;
        }

        // 过滤菜单项
        return array_filter($menus, function ($menu) use ($permissions) {
            // 如果没有url字段，保留（通常是分组标题）
            if (!isset($menu['url'])) {
                return true;
            }

            $url = $menu['url'];

            // 检查当前菜单项是否有权限
            $hasPermission = $this->checkMenuPermission($url, $permissions);

            // 如果有子菜单，递归过滤子菜单
            if (isset($menu['sub']) && is_array($menu['sub'])) {
                $menu['sub'] = $this->filterMenu($menu['sub'], $permissions);
                // 如果子菜单全部被过滤掉，且当前菜单项无权限，则移除
                if (empty($menu['sub']) && !$hasPermission) {
                    return false;
                }
                // 如果子菜单还有保留项，保留当前菜单项
                return true;
            }

            return $hasPermission;
        });
    }

    /**
     * 检查单个菜单项是否有权限
     *
     * @param  string $url         菜单URL
     * @param  array  $permissions 用户权限列表
     * @return bool   有权限返回true
     */
    private function checkMenuPermission(string $url, array $permissions): bool
    {
        foreach ($permissions as $permissionName) {
            if (!isset($this->permissions[$permissionName])) {
                continue;
            }

            foreach ($this->permissions[$permissionName] as $rule) {
                $parts = explode(' ', $rule, 2);
                if (count($parts) !== 2) {
                    continue;
                }

                [$ruleMethod, $rulePath] = $parts;

                // 使用matchUri匹配URL（支持 * 通配符）
                if ($this->matchUri($rulePath, $url)) {
                    return true;
                }
            }
        }

        return false;
    }
}
