<?php

declare(strict_types=1);

namespace nova\plugin\login\db\Model;

use nova\plugin\orm\object\Model;

/**
 * 角色模型类
 *
 * 用于管理用户角色信息，包括角色名称和权限列表
 *
 * @package nova\plugin\login\db\Model
 * @since 1.0.0
 */
class RoleModel extends Model
{
    /**
     * 角色名称
     *
     * @var string
     */
    public string $name = '无权限';

    /**
     * 角色权限数组
     *
     * 存储该角色拥有的权限标识列表
     *
     * @var array
     */
    public array $permissions = [];

    /**
     * 获取模型的唯一字段
     *
     * @return array 唯一字段数组
     */
    public function getUnique(): array
    {
        return ['name'];
    }
}
