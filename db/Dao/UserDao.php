<?php

declare(strict_types=1);

namespace nova\plugin\login\db\Dao;

use nova\framework\core\Logger;
use nova\plugin\login\avatar\Avatar;
use nova\plugin\login\db\Model\UserModel;
use nova\plugin\orm\object\Dao;

class UserDao extends Dao
{
    public function onCreateTable(): void
    {
        $user = new UserModel();
        $user->avatar = Avatar::svg('admin');
        $user->roles = ['admin'];
        $user->status = 'active';
        $user->display_name = 'Super Admin';
        $user->email = 'admin@admin.com';
        $rand_passwd = bin2hex(random_bytes(8)); // 生成16字符的随机密码
        $user->password = password_hash($rand_passwd, PASSWORD_DEFAULT);
        // 插入管理员用户
        $this->insertModel($user);
        // 记录随机生成的密码，以便管理员可以登录
        $info =  "初始管理员账户创建成功，邮箱: {$user->email}，密码: {$rand_passwd}";
        Logger::info($info);
        // 也可以将密码写入一个安全的文件

        file_put_contents(ROOT_PATH . '/runtime/admin_password.txt', $info);
    }

    public function login(string $email, string $password): ?UserModel
    {
        $user = $this->findByEmail($email);
        if (empty($user) || !$user->authenticate($email, $password)) {
            return null;
        }
        return $user;
    }

    public function findByEmail($email): ?UserModel
    {
        return $this->find(null, ['email' => $email]);
    }

}
