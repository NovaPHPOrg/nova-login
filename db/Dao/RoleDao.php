<?php

declare(strict_types=1);

namespace nova\plugin\login\db\Dao;

use nova\plugin\login\db\Model\RoleModel;
use nova\plugin\orm\object\Dao;

class RoleDao extends Dao
{
    public function id(int $id): ?RoleModel
    {
        return $this->find(null, ['id' => $id]);
    }

    /**
     * 获取所有角色并以 ID 为键
     * @return RoleModel[]
     */
    public function getRoleMap(): array
    {
        $roles = $this->select()->commit();
        return array_column($roles, null, 'id');
    }
}
