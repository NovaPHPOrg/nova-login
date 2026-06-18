<?php

declare(strict_types=1);

namespace nova\plugin\login\db\Model;

use nova\plugin\orm\object\Model;

/**
 * 角色模型
 */
class RoleModel extends Model
{
    /**
     * 角色名称
     *
     * @var string
     */
    public string $name = '';

    /**
     * 权限标识数组
     *
     * @var array
     */
    public array $permissions = [];

    public function getUnique(): array
    {
        return ['name'];
    }

    public function getTableName(): string
    {
        return 'role';
    }
}
