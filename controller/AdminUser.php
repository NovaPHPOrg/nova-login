<?php

declare(strict_types=1);

namespace nova\plugin\login\controller;

use app\controller\BaseController;
use nova\framework\http\Response;
use nova\plugin\login\db\Dao\RoleDao;
use nova\plugin\login\db\Dao\UserDao;
use nova\plugin\login\db\Model\UserModel;

/**
 * 用户管理 (RBAC)
 */
class AdminUser extends BaseController
{
    public function list(): Response
    {
        $page = max(1, intval($this->request->get('page', 1)));
        $pageSize = min(100, max(1, intval($this->request->get('pageSize', 20))));
        $keyword = trim((string)$this->request->get('keyword', ''));

        $conditions = [];
        if ($keyword !== '') {
            $kw = '%' . $keyword . '%';
            $conditions[] = '(`username` LIKE :kw OR `display_name` LIKE :kw)';
            $conditions[':kw'] = $kw;
        }

        $result = UserDao::getInstance()->getAll(null, $conditions, $page, $pageSize);
        $rows = $result['data'];

        // 获取所有角色以便显示名称
        $roleMap = RoleDao::getInstance()->getRoleMap();

        foreach ($rows as &$row) {
            $row['role_names'] = [];
            if (!empty($row['roles']) && is_array($row['roles'])) {
                foreach ($row['roles'] as $roleId) {
                    if (isset($roleMap[$roleId])) {
                        $row['role_names'][] = $roleMap[$roleId]->name;
                    }
                }
            }
            if (empty($row['role_names'])) {
                $row['role_names'] = ['无角色'];
            }
            $row['role_name'] = implode(', ', $row['role_names']);
        }

        return Response::asJson([
            'code' => 200,
            'data' => $rows,
            'count' => $result['total'],
        ]);
    }

    public function save(): Response
    {
        $data = $this->request->post();
        $dao = UserDao::getInstance();
        $id = intval($data['id'] ?? 0);

        $model = new UserModel($data);
        $model->username = trim($model->username);
        $model->display_name = trim($model->display_name);

        // 处理角色 (RBAC: roles array)
        if (isset($data['role_ids'])) {
            $model->roles = array_map('intval', (array)$data['role_ids']);
        } elseif (isset($data['role_id'])) {
            $model->roles = [intval($data['role_id'])];
        }

        if ($id > 0) {
            $existing = $dao->id($id);
            if (!$existing) {
                return Response::asJson(['code' => 404, 'msg' => '用户不存在']);
            }

            if (empty($data['password'])) {
                $model->password = $existing->password;
            } else {
                $model->password = password_hash($data['password'], PASSWORD_DEFAULT);
            }
            $dao->updateModel($model);
        } else {
            if (empty($data['password'])) {
                return Response::asJson(['code' => 400, 'msg' => '密码不能为空']);
            }
            if ($dao->username($model->username)) {
                return Response::asJson(['code' => 400, 'msg' => '用户名已存在']);
            }
            $model->password = password_hash($data['password'], PASSWORD_DEFAULT);
            $dao->insertModel($model);
        }

        return Response::asJson(['code' => 200, 'msg' => '保存成功']);
    }

    public function remove(): Response
    {
        $id = intval($this->request->post('id', 0));
        if ($id === 1) {
            return Response::asJson(['code' => 400, 'msg' => '不能删除默认管理员']);
        }

        UserDao::getInstance()->delete()->where(['id' => $id])->commit();
        return Response::asJson(['code' => 200, 'msg' => '删除成功']);
    }
}
