<?php

declare(strict_types=1);

namespace nova\plugin\login\db\Dao;

use nova\framework\core\Logger;
use nova\plugin\avatar\Avatar;
use nova\plugin\login\db\Model\UserModel;
use nova\plugin\orm\object\Dao;

class UserDao extends Dao
{
    public function onCreateTable(): void
    {
        $user = new UserModel();
        $user->avatar = Avatar::toBase64(Avatar::svg('admin'));
        $user->display_name = 'Super Admin';
        $rand_passwd = bin2hex(random_bytes(8)); // 生成16字符的随机密码
        $user->password = password_hash($rand_passwd, PASSWORD_DEFAULT);
        $user->username = "admin";
        // 插入管理员用户
        $this->insertModel($user);
        // 记录随机生成的密码，以便管理员可以登录
        $info =  "初始管理员账户创建成功，账户: {$user->username}，密码: {$rand_passwd}";
        Logger::info($info);
        // 也可以将密码写入一个安全的文件
        file_put_contents(ROOT_PATH . '/runtime/admin_password.txt', $info);
    }

    public function login(string $username, string $password): ?UserModel
    {
        $user = $this->username($username);
        if (empty($user) || !password_verify($password, $user->password)) {
            return null;
        }
        return $user;
    }

    public function username($username): ?UserModel
    {
        return $this->find(null, ['username' => $username]);
    }


    public function id(int $user_id): ?UserModel
    {
        return $this->find(null, ['id' => $user_id]);
    }

}
