<?php

declare(strict_types=1);

namespace nova\plugin\login\controller;

use app\controller\manager\BaseController;
use nova\framework\http\Response;
use nova\plugin\login\db\Dao\RoleDao;
use nova\plugin\login\db\Dao\UserDao;
use nova\plugin\login\db\Model\RoleModel;
use nova\plugin\login\route\PermissionRouter;

/**
 * 角色管理控制器
 *
 * 提供角色列表、创建、更新、删除、详情获取等管理功能
 */
class Role extends BaseController
{
    /**
     * 获取角色列表
     *
     * @return Response 返回角色列表JSON响应
     */
    public function list(): Response
    {
        $page = (int)$this->request->get('page', 1);
        $pageSize = (int)$this->request->get('pageSize', 10);
        $keyword = (string)$this->request->get('keyword', '');

        $conditions = [];
        if ($keyword !== '') {
            $conditions[] = '(`name` LIKE :kw)';
            $conditions[':kw'] = '%' . $keyword . '%';
        }

        $result = RoleDao::getInstance()->getAll(null, $conditions, $page, $pageSize);

        $permissions = PermissionRouter::getInstance()->permissions();

        // 转换权限列表为显示名称
        if (isset($result['data']) && is_array($result['data'])) {
            foreach ($result['data'] as &$item) {

                $array = $item->toArray();
                $array['permissions_display']  = array_map(function ($perm) use ($permissions) {
                    return $permissions[$perm] ?? $perm;
                }, $item->permissions);

                $array['permissions']  = $item->permissions;

                $item = $array;
            }
        }

        return Response::asJson([
            'code' => 200,
            'data' => $result['data'],
            'count' => $result['total'],
        ]);
    }

    /**
     * 获取所有权限列表
     *
     * @return Response 返回权限列表JSON响应
     */
    public function permissions(): Response
    {
        return Response::asJson(['code' => 200, 'data' => PermissionRouter::getInstance()->permissions()], 200);
    }

    /**
     * 创建或更新角色
     *
     * @return Response 返回操作结果JSON响应
     */
    public function update(): Response
    {
        $dao = RoleDao::getInstance();
        $model = new RoleModel($this->request->post());
        $model->id = $dao->insertModel($model, true);

        return Response::asJson(['code' => 200, 'msg' => '保存成功'], 200);
    }

    /**
     * 删除角色
     *
     * @return Response 返回操作结果JSON响应
     */
    public function remove(): Response
    {
        $id = (int)$this->request->post('id', 0);
        $role = RoleDao::getInstance()->id($id);

        if (!$role) {
            return Response::asJson(['code' => 404, 'msg' => '角色不存在'], 404);
        }

        $count = UserDao::getInstance()->countByRole($id);
        if ($count > 0) {
            return Response::asJson(['code' => 400, 'msg' => '该角色仍有 ' . $count . ' 个用户，无法删除'], 400);
        }

        RoleDao::getInstance()->deleteById($id);

        return Response::asJson(['code' => 200, 'msg' => '删除成功'], 200);
    }

}
