<?php

declare(strict_types=1);

namespace nova\plugin\login;

/**
 * 系统权限管理类
 *
 * 提供基本的权限定义和检查功能
 */
class Permissions
{
    /**
     * 单例实例
     */
    private static ?Permissions $instance = null;

    /**
     * 内置权限常量
     */
    public const string ALL = 'all';
    public const string BASIC = 'basic';

    /**
     * 权限描述映射
     *
     * @var array<string, string>
     */
    private array $descriptions = [];

    /**
     * 权限层级关系
     *
     * @var array<string, array<string>>
     */
    private array $hierarchy = [];

    /**
     * 私有构造函数，防止直接实例化
     */
    private function __construct()
    {
        // 初始化默认权限
        $this->descriptions = [
            self::ALL => '所有权限',
            self::BASIC => '基础权限',
        ];

        $this->hierarchy = [
            self::ALL => [self::BASIC],
        ];
    }

    /**
     * 获取单例实例
     *
     * @return Permissions
     */
    public static function getInstance(): Permissions
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * 获取所有可用权限
     *
     * @return array<string, string> 权限ID => 权限描述
     */
    public function getAll(): array
    {
        return $this->descriptions;
    }

    /**
     * 添加新权限（仅在开发过程中使用）
     *
     * @param  string        $id          权限ID
     * @param  string        $description 权限描述
     * @param  array<string> $includes    包含的子权限
     * @return bool          是否成功添加（已存在则返回false）
     */
    public function register(string $id, string $description, array $includes = []): bool
    {
        // 检查权限是否已存在
        if (isset($this->descriptions[$id])) {
            return false; // 权限已存在，不再添加
        }

        // 添加新权限
        $this->descriptions[$id] = $description;

        if (!empty($includes)) {
            $this->hierarchy[$id] = $includes;
        }

        // 确保ALL权限包含新添加的权限
        if ($id !== self::ALL && !in_array($id, $this->hierarchy[self::ALL])) {
            $this->hierarchy[self::ALL][] = $id;
        }

        return true; // 成功添加新权限
    }

    /**
     * 获取权限描述
     *
     * @param  string $id 权限ID
     * @return string 权限描述，不存在则返回权限ID
     */
    public function getDescription(string $id): string
    {
        return $this->descriptions[$id] ?? $id;
    }

    /**
     * 检查一个权限是否包含另一个权限
     *
     * @param  string $permission 要检查的权限
     * @param  string $required   需要的权限
     * @return bool   如果包含则返回true
     */
    public function contains(string $permission, string $required): bool
    {
        // 相同权限
        if ($permission === $required) {
            return true;
        }

        // ALL权限包含所有其他权限
        if ($permission === self::ALL) {
            return true;
        }

        // 检查层级关系
        return isset($this->hierarchy[$permission]) &&
               in_array($required, $this->hierarchy[$permission]);
    }

    /**
     * 获取包含指定权限的所有父权限
     *
     * @param  string        $id 权限ID
     * @return array<string> 包含该权限的所有父权限ID
     */
    public function getParent(string $id): array
    {
        $parents = [];

        // 如果权限不存在，返回空数组
        if (!isset($this->descriptions[$id])) {
            return $parents;
        }

        // 遍历所有权限层级关系
        foreach ($this->hierarchy as $parentId => $childPermissions) {
            // 如果当前权限在子权限列表中，则添加父权限到结果中
            if (in_array($id, $childPermissions)) {
                $parents[] = $parentId;

                // 递归查找更高层级的父权限
                $grandParents = $this->getParent($parentId);
                if (!empty($grandParents)) {
                    $parents = array_merge($parents, $grandParents);
                }
            }
        }

        // 去重并返回
        return array_unique($parents);
    }

    /**
     * 防止克隆实例
     */
    private function __clone()
    {
    }
}
