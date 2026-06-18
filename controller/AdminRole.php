<?php

declare(strict_types=1);

namespace nova\plugin\login\controller;

use app\controller\BaseController;
use nova\framework\http\Response;
use nova\plugin\login\db\Dao\RoleDao;
use nova\plugin\login\db\Model\RoleModel;
use nova\plugin\login\manager\PermissionManager;

/**
 * 角色管理 (RBAC)
 */
class AdminRole extends BaseController
{
    public function list(): Response
    {
        $page = max(1, intval($this->request->get('page', 1)));
        $pageSize = min(100, max(1, intval($this->request->get('pageSize', 20))));
        $keyword = trim((string)$this->request->get('keyword', ''));

        $conditions = [];
        if ($keyword !== '') {
            $kw = '%' . $keyword . '%';
            $conditions[] = '(`name` LIKE :kw)';
            $conditions[':kw'] = $kw;
        }

        $result = RoleDao::getInstance()->getAll(null, $conditions, $page, $pageSize);

        return Response::asJson([
            'code' => 200,
            'data' => $result['data'],
            'count' => $result['total'],
        ]);
    }

    public function save(): Response
    {
        $data = $this->request->post();
        $dao = RoleDao::getInstance();
        $id = intval($data['id'] ?? 0);

        $model = new RoleModel($data);
        $model->name = trim($model->name);

        // 处理 permissions
        if (isset($data['permissions'])) {
            if (is_string($data['permissions'])) {
                $model->permissions = array_filter(explode(',', $data['permissions']));
            } else if (is_array($data['permissions'])) {
                $model->permissions = $data['permissions'];
            }
        } else {
            $model->permissions = [];
        }

        if (empty($model->name)) {
            return Response::asJson(['code' => 400, 'msg' => '角色名称不能为空']);
        }

        if ($id > 0) {
            $dao->updateModel($model);
        } else {
            $dao->insertModel($model);
        }

        return Response::asJson(['code' => 200, 'msg' => '保存成功']);
    }

    public function remove(): Response
    {
        $id = intval($this->request->post('id', 0));
        RoleDao::getInstance()->delete()->where(['id' => $id])->commit();
        return Response::asJson(['code' => 200, 'msg' => '删除成功']);
    }

    public function all(): Response
    {
        $roles = RoleDao::getInstance()->select()->commit();
        return Response::asJson(['code' => 200, 'data' => $roles]);
    }

    /**
     * 获取所有可用的权限定义
     */
    public function permissions(): Response
    {
        $permissions = PermissionManager::getInstance()->getPermissions();
        $data = [['id' => 'all', 'name' => '超级管理员 (所有权限)']];

        foreach ($permissions as $id => $rules) {
            $data[] = [
                'id' => $id,
                'name' => $id . ' (' . implode(', ', (array)$rules) . ')'
            ];
        }

        return Response::asJson([
            'code' => 200,
            'data' => $data
        ]);
    }
}
