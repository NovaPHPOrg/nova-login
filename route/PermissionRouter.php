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
        return $this->permissions;
    }

    /**
     * 注册新的权限
     *
     * @param  string $name        权限名称
     * @param  array  $permissions 权限规则数组
     * @return void
     */
    public function registerPermissions(string $name, array $permissions): void
    {
        $this->permissions[$name] = $permissions;
    }
}
