<?php

declare(strict_types=1);

namespace nova\plugin\login\controller;

use nova\framework\http\Response;
use nova\plugin\login\db\Dao\RecordDao;
use nova\plugin\login\db\Dao\UserDao;

/**
 * 密码管理控制器
 *
 * 处理用户修改密码等密码相关功能
 */
class Pwd extends BaseController
{
    /**
     * 获取密码配置
     *
     * 返回当前登录用户的用户名和显示名称
     *
     * @return Response 返回密码配置JSON响应
     */
    public function config(): Response
    {
        return Response::asJson([
            'code' => 200,
            'data' => [
                'username' => $this->user->username,
                'display_name' => $this->user->display_name,
            ],
        ]);
    }

    /**
     * 保存密码修改
     *
     * @return Response 返回操作结果JSON响应
     */
    public function save(): Response
    {
        $post = $this->request->post();
        $currentUser = $this->user;
        $userDao = UserDao::getInstance();

        // 验证当前密码
        if ($post['current_password'] ?? null) {
            if ($userDao->login($currentUser->username, $post['current_password']) === null) {
                return Response::asJson(['code' => 400, 'msg' => '当前密码错误'], 400);
            }
        } else {
            return Response::asJson(['code' => 400, 'msg' => '请提供当前密码'], 400);
        }

        // 更新用户名（可选）
        if ($post['username'] ?? null) {
            if (!preg_match('/^[a-z0-9]{5,10}$/', $post['username'])) {
                return Response::asJson(['code' => 400, 'msg' => '用户名格式错误'], 400);
            }
            $existing = $userDao->username($post['username']);
            if ($existing && $existing->id !== $currentUser->id) {
                return Response::asJson(['code' => 400, 'msg' => '用户名已被使用'], 400);
            }
            $currentUser->username = $post['username'];
        }

        // 更新密码（可选）
        if ($post['new_password'] ?? null) {
            if (strlen($post['new_password']) < 8) {
                return Response::asJson(['code' => 400, 'msg' => '密码长度至少为8位'], 400);
            }
            $currentUser->password = password_hash($post['new_password'], PASSWORD_DEFAULT);
        }

        // 保存更改
        $userDao->updateModel($currentUser);

        // 密码修改后登出所有会话
        if ($post['new_password'] ?? null) {
            $this->logoutAllSessions($currentUser->id);
            return Response::asJson(['code' => 200, 'msg' => '密码修改成功，请重新登录'], 200);
        }

        return Response::asJson(['code' => 200, 'msg' => '资料修改成功'], 200);
    }

    /**
     * 登出用户的所有会话
     *
     * @param  int  $userId 用户ID
     * @return void
     */
    private function logoutAllSessions(int $userId): void
    {
        $recordDao = RecordDao::getInstance();
        $records = $recordDao->select()->where(['user_id' => $userId])->commit();

        foreach ($records as $record) {
            $recordDao->deleteModel($record);
        }
    }
}
