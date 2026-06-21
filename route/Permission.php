<?php

declare(strict_types=1);

namespace nova\plugin\login\route;

use nova\framework\core\Context;

use nova\framework\core\Instance;

use nova\plugin\login\db\Model\UserModel;

/**
 * 权限路由器
 *
 * 负责检查用户是否有权限访问特定的路由
 *
 * @package nova\plugin\login\route
 * @since 1.0.0
 */
class Permission extends Instance
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
     * @param  UserModel $userModel 用户模型
     * @return bool      有权限返回true，否则返回false
     */
    public function hasPermission(UserModel $userModel): bool
    {
        $role = $userModel->role();

        if (empty($role->permissions)) {
            return false;
        }

        if (in_array('all', $role->permissions)) {
            return true;
        }

        $permissionRules = $this->rebuildPermissions();

        $request = Context::instance()->request();
        $method = $request->getRequestMethod();
        $uri = $request->getPath();

        $ruleKey = $method . ' ' . $uri;

        foreach ($permissionRules as $key => $value) {
            if ($this->matchUri($key, $ruleKey)) {
                foreach ($value as $permissionName) {
                    if (in_array($permissionName, $role->permissions)) {
                        return true;
                    }
                }
                return false;
            }
        }
        return true;
    }

    private function rebuildPermissions(): array
    {
        $permissions = [];

        foreach ($this->permissions as $name => $rules) {
            foreach ($rules as $rule) {
                [$method, $url] = $this->getRule($rule);

                if ($method === 'ANY') {
                    foreach (['GET', 'POST', 'PUT', 'DELETE', 'PATCH', 'HEAD', 'OPTIONS'] as $httpMethod) {
                        $r = $httpMethod . ' ' . $url;
                        $permissions[$r][] = $name;
                    }
                } else {
                    $permissions[$rule][] = $name;
                }
            }
        }

        return $permissions;
    }

    private function getRule(string $rule): array
    {
        $parts = explode(' ', $rule, 2);
        if (count($parts) !== 2) {
            return [];
        }

        return $parts;

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
        // 将 * 转换为正则表达式 [^/]* （匹配除/外的任意字符）
        // 注意：仅转换未转义的 *
        $pattern = preg_quote($pattern, '#');
        $pattern = str_replace('\*', '([^/]*)', $pattern);
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

    public function filterMenu(
        array $menus,
        UserModel $userModel,
        ?array $permissionRules = null
    ): array {
        $permissions = $userModel->role()->permissions;

        if (in_array('all', $permissions, true)) {
            return $menus;
        }

        $permissionRules ??= array_filter($this->rebuildPermissions(), function ($menu, $key) {
            return str_starts_with($key, "GET ");
        }, ARRAY_FILTER_USE_BOTH);

        // 获取当前请求的 HTTP method
        $method = 'GET';

        $result = [];

        foreach ($menus as $menu) {
            // 处理分组菜单
            if (isset($menu['sub'])) {
                $menu['sub'] = $this->filterMenu(
                    $menu['sub'],
                    $userModel,
                    $permissionRules
                );
                if (!empty($menu['sub'])) {
                    $result[] = $menu;
                }
                continue;
            }

            // 没有URL的菜单直接跳过
            if (empty($menu['url'])) {
                continue;
            }

            $uri = strtok($menu['url'], '?');

            // 使用当前请求的 HTTP method 构建 ruleKey
            $ruleKey = $method . ' ' . $uri;

            $matchedAnyRule = false;
            $hasPermission = false;

            foreach ($permissionRules as $key => $rulePermissions) {

                // 检查 ruleKey 是否匹配规则（支持通配符）
                if (!$this->matchUri($key, $ruleKey)) {
                    continue;
                }

                $matchedAnyRule = true;

                // 检查用户是否有对应权限
                foreach ($rulePermissions as $permissionName) {
                    if (in_array($permissionName, $permissions, true)) {
                        $hasPermission = true;
                        break 2;
                    }
                }
            }

            // 只有匹配了规则且有权限才显示
            // 如果没有匹配任何规则，默认不显示（Fail Close）
            if ($hasPermission || !$matchedAnyRule) {
                $result[] = $menu;
            }
        }

        return $result;
    }
}
