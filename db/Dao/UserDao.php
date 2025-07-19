<?php

declare(strict_types=1);

namespace nova\plugin\login\db\Dao;

use nova\framework\core\Logger;
use nova\plugin\avatar\Avatar;
use nova\plugin\login\db\Model\UserModel;
use nova\plugin\orm\object\Dao;

/**
 * 用户数据访问对象类
 *
 * 负责用户相关的数据库操作，包括用户创建、登录验证、用户查询等功能
 * 继承自基础 Dao 类，提供用户数据访问的封装
 */
class UserDao extends Dao
{
    /**
     * 创建用户表时的初始化操作
     *
     * 当用户表首次创建时，会自动创建一个超级管理员账户
     * 生成随机密码并记录到日志和文件中，方便管理员首次登录
     *
     * @return void
     */
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

    /**
     * 用户登录验证
     *
     * 根据用户名和密码验证用户身份
     * 使用 password_verify 函数安全验证密码哈希
     *
     * @param  string         $username 用户名
     * @param  string         $password 密码（明文）
     * @return UserModel|null 验证成功返回用户模型对象，失败返回 null
     */
    public function login(string $username, string $password): ?UserModel
    {
        $user = $this->username($username);
        if (empty($user) || !password_verify($password, $user->password)) {
            return null;
        }
        return $user;
    }

    /**
     * 根据用户名查找用户
     *
     * 通过用户名精确匹配查找用户记录
     *
     * @param  string         $username 用户名
     * @return UserModel|null 找到返回用户模型对象，未找到返回 null
     */
    public function username($username): ?UserModel
    {
        return $this->find(null, ['username' => $username]);
    }

    /**
     * 根据用户ID查找用户
     *
     * 通过用户ID精确匹配查找用户记录
     *
     * @param  int            $user_id 用户ID
     * @return UserModel|null 找到返回用户模型对象，未找到返回 null
     */
    public function id(int $user_id): ?UserModel
    {
        return $this->find(null, ['id' => $user_id]);
    }

}
