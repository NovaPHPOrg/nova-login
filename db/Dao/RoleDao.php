<?php

declare(strict_types=1);

namespace nova\plugin\login\db\Dao;

use nova\plugin\login\db\Model\RoleModel;
use nova\plugin\orm\object\Dao;

class RoleDao extends Dao
{
    public function onCreateTable(): void
    {
        $this->insertModel(new RoleModel([
            'id' => 1,
            'permissions' => ['all'],
            'name' => "超级管理员"
        ]));

        $this->insertModel(new RoleModel([
            'id' => 2,
            'permissions' => [],
            "name" => "普通用户"
        ]));
    }

    public function id(int $id): ?RoleModel
    {
        return $this->find(null, ['id' => $id]);
    }
}
