<?php

declare(strict_types=1);

namespace nova\plugin\login\db\Dao;

use nova\plugin\login\db\Model\RoleModel;
use nova\plugin\orm\object\Dao;

/**
 * 角色数据访问对象类
 *
 * 负责角色相关的数据库操作
 *
 * @package nova\plugin\login\db\Dao
 * @since 1.0.0
 */
class RoleDao extends Dao
{
    /**
     * 根据ID获取角色
     *
     * @param  int            $id 角色ID
     * @return RoleModel|null 找到返回角色模型对象，未找到返回 null
     */
    public function id(int $id): ?RoleModel
    {
        return $this->find(null, ['id' => $id]);
    }

    /**
     * 删除角色
     *
     * @param  int  $id 角色ID
     * @return void
     */
    public function deleteById(int $id): void
    {
        $this->delete()->where(['id' => $id])->commit();
    }

    /**
     * 获取角色映射表（id => RoleModel）
     *
     * @return array 角色映射数组
     */
    public function getRoleMap(): array
    {
        $roles = $this->getAll();
        $map = [];
        foreach ($roles['data'] as $role) {
            $map[$role->id] = $role;
        }
        return $map;
    }

    /**
     * 创建角色表时的初始化操作
     *
     * @return void
     */
    public function onCreateTable()
    {
        $role = new RoleModel();
        $role->id = 1;
        $role->name = "超级管理员";
        $role->permissions = [ 'all' ];
        $this->insertModel($role, true);
    }
}
