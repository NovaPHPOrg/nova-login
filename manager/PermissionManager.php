<?php

declare(strict_types=1);

namespace nova\plugin\login\manager;

use nova\framework\core\Context;
use nova\framework\core\StaticRegister;
use nova\plugin\login\db\Dao\RoleDao;

/**
 * 权限管理器
 *
 * 负责管理应用定义的静态权限，并提供权限匹配功能。
 */
class PermissionManager extends StaticRegister
{
    /**
     * 权限定义数组
     * 格式: ['perm_name' => ['GET /path/*', 'POST /path']]
     */
    private array $permissions = [];

    /**
     * 白名单路由（无需权限检查）
     */
    private array $whitelist = [
        'ANY /login',
        'ANY /logout',
        'ANY /captcha',
        'ANY /static/*',
    ];

    /**
     * 注册权限定义
     */
    public function registerPermissions(array $permissions): void
    {
        foreach ($permissions as $name => $rules) {
            if (!isset($this->permissions[$name])) {
                $this->permissions[$name] = [];
            }
            $this->permissions[$name] = array_merge($this->permissions[$name], (array)$rules);
        }
    }

    /**
     * 注册白名单
     */
    public function registerWhitelist(array $whitelist): void
    {
        $this->whitelist = array_merge($this->whitelist, $whitelist);
    }

    public function getPermissions(): array
    {
        return $this->permissions;
    }

    /**
     * 检查是否在白名单中
     */
    public function isWhitelisted(string $method, string $uri): bool
    {
        foreach ($this->whitelist as $rule) {
            if ($this->matchRule($method, $uri, $rule)) {
                return true;
            }
        }
        return false;
    }

    /**
     * 匹配规则
     */
    private function matchRule(string $method, string $uri, string $rule): bool
    {
        $parts = explode(' ', trim($rule), 2);
        if (count($parts) === 2) {
            $rMethod = strtoupper($parts[0]);
            $rUri = $parts[1];
        } else {
            $rMethod = 'ANY';
            $rUri = $parts[0];
        }

        if ($rMethod !== 'ANY' && $rMethod !== strtoupper($method)) {
            return false;
        }

        return $this->matchUri($uri, $rUri);
    }

    /**
     * 匹配 URI (支持 *)
     */
    private function matchUri(string $uri, string $pattern): bool
    {
        // 简单匹配
        if ($uri === $pattern) {
            return true;
        }

        // 正则匹配
        $pattern = str_replace(['/', '*'], ['\/', '.*'], $pattern);
        return (bool)preg_match('/^' . $pattern . '$/i', $uri);
    }

    /**
     * 检查用户是否有权限访问指定 URI
     */
    public function check(string $method, string $uri, array $userPermissions, array $userRoles = []): bool
    {
        if (in_array('all', $userPermissions, true)) {
            return true;
        }

        // 收集所有权限标识
        $allUserPermissions = $userPermissions;
        if (!empty($userRoles)) {
            $roleDao = RoleDao::getInstance();
            foreach ($userRoles as $roleId) {
                $role = $roleDao->id((int)$roleId);
                if ($role) {
                    if (in_array('all', $role->permissions, true)) {
                        return true;
                    }
                    $allUserPermissions = array_merge($allUserPermissions, $role->permissions);
                }
            }
        }
        $allUserPermissions = array_unique($allUserPermissions);

        foreach ($allUserPermissions as $pName) {
            if (!isset($this->permissions[$pName])) {
                continue;
            }

            $rules = $this->permissions[$pName];
            foreach ($rules as $rule) {
                if ($this->matchRule($method, $uri, $rule)) {
                    return true;
                }
            }
        }

        return false;
    }

    public static function getInstance(): PermissionManager
    {
        return Context::instance()->getOrCreateInstance("permissionManager", function () {
            return new PermissionManager();
        });
    }
}
