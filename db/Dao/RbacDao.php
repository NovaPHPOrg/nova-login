<?php
declare(strict_types=1);

namespace nova\plugin\login\db\Dao;

use nova\plugin\login\Permissions;
use nova\plugin\orm\object\Dao;
use nova\plugin\login\db\Model\RbacModel;

class RbacDao extends Dao
{
    /**
     * 当表被创建的时候初始化默认数据
     * @return void
     */
    public function onCreateTable(): void
    {
        // 创建默认的RBAC角色和权限
        $model = new RbacModel();
        $model->name = 'admin';
        $model->description = '系统管理员';
        $model->permissions = [Permissions::ALL];
        $this->insertModel($model);
        
        // 可以添加更多默认角色
        $model = new RbacModel();
        $model->name = 'user';
        $model->description = '普通用户';
        $model->permissions = [Permissions::BASIC];
        $this->insertModel($model);
    }

    public function name($name):?RbacModel
    {
        return $this->find(null,['name' => $name]);
    }
}