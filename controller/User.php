<?php

declare(strict_types=1);

namespace nova\plugin\login\controller;

use app\controller\manager\BaseController;
use nova\framework\http\Response;
use nova\plugin\login\db\Dao\RoleDao;
use nova\plugin\login\db\Dao\UserDao;
use nova\plugin\login\db\Model\UserModel;

/**
 * 用户管理控制器
 *
 * 提供用户列表、创建、更新、删除、详情获取等管理功能
 */
class User extends BaseController
{
    /**
     * 获取用户列表
     *
     * @return Response 返回用户列表JSON响应
     */
    public function list(): Response
    {
        $page = (int)$this->request->get('page', 1);
        $pageSize = (int)$this->request->get('pageSize', 10);
        $keyword = (string)$this->request->get('keyword', '');

        $conditions = [];
        if ($keyword !== '') {
            $conditions[] = '(`username` LIKE :kw OR `display_name` LIKE :kw)';
            $conditions[':kw'] = '%' . $keyword . '%';
        }

        $result = UserDao::getInstance()->getAll(null, $conditions, $page, $pageSize, 'id', false);
        $rows = $result['data'];

        foreach ($rows as &$row) {
            $row['role_data'] = RoleDao::getInstance()->id($row['role']);
        }

        return Response::asJson([
            'code' => 200,
            'data' => $rows,
            'count' => $result['total'],
        ]);
    }

    /**
     * 创建或更新用户
     *
     * @return Response 返回操作结果JSON响应
     */
    public function update(): Response
    {
        $dao = UserDao::getInstance();
        $id = (int)$this->request->post('id', 0);

        if ($id > 0) {
            $model = $dao->id($id);
            if (!$model) {
                return Response::asJson(['code' => 404, 'msg' => '不存在该用户'], 404);
            }
            $model->display_name = $this->request->post('display_name', '');
            $model->role = (int)$this->request->post('role', 0);

            $pwd = $this->request->post('password', '');
            if ($pwd !== '') {
                $model->password = password_hash($pwd, PASSWORD_DEFAULT);
            }
        } else {
            $model = new UserModel($this->request->post());
            $model->password = password_hash($model->password, PASSWORD_DEFAULT);
        }

        $model->id = $dao->insertModel($model, true);

        return Response::asJson(['code' => 200, 'msg' => '保存成功'], 200);
    }

    /**
     * 删除用户
     *
     * @return Response 返回操作结果JSON响应
     */
    public function remove(): Response
    {
        $id = (int)$this->request->post('id', 0);
        if ($id === 1) {
            return Response::asJson(['code' => 400, 'msg' => '不能删除默认管理员'], 400);
        }

        $user = UserDao::getInstance()->id($id);
        if (!$user) {
            return Response::asJson(['code' => 404, 'msg' => '用户不存在'], 404);
        }

        UserDao::getInstance()->deleteById($id);

        return Response::asJson(['code' => 200, 'msg' => '删除成功'], 200);
    }

    /**
     * 获取用户详情
     *
     * @param  int      $id 用户ID
     * @return Response 返回用户详情JSON响应
     */
    public function view(int $id): Response
    {
        $user = UserDao::getInstance()->id($id);
        if (!$user) {
            return Response::asJson(['code' => 404, 'msg' => '用户不存在'], 404);
        }

        return Response::asJson(['code' => 200, 'data' => $user], 200);
    }
}
