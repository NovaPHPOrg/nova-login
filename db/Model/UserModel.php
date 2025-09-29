<?php

declare(strict_types=1);

namespace nova\plugin\login\db\Model;


use nova\plugin\orm\object\Model;

/**
 * 用户模型类
 *
 * 用于管理用户账户信息，包括用户名、密码、显示名称和头像等基本信息
 * 继承自 Model 基类，提供数据库操作功能
 *
 * @package nova\plugin\login\db\Model
 * @since 1.0.0
 */
class UserModel extends Model
{
    /**
     * 用户名
     *
     * 用于登录的唯一标识符，通常为邮箱地址或自定义用户名
     *
     * @var string
     */
    public string $username = '';

    /**
     * 密码哈希值
     *
     * 存储经过加密处理的用户密码，不应以明文形式存储
     *
     * @var string
     */
    public string $password = '';

    /**
     * 用户显示名称
     *
     * 用于界面显示的友好名称，可以与用户名不同
     *
     * @var string
     */
    public string $display_name = '';

    /**
     * 用户头像
     *
     * 用户头像的URL地址或文件路径
     *
     * @var string
     */
    public string $avatar = '';

    /**
     * 获取模型的唯一字段
     *
     * 返回用于标识记录唯一性的字段数组
     * 这些字段在数据库中应该具有唯一约束
     *
     * @return array 唯一字段数组
     */
    public function getUnique(): array
    {
        return ['username']; // 用户名是唯一的标识符
    }

    /**
     * 获取不需要HTML转义的字段
     *
     * 返回在输出时不需要进行HTML转义的字段数组
     * 通常包括已经过安全处理的字段，如密码哈希值
     *
     * @return array 不需要HTML转义的字段数组
     */
    public function getNoEscape(): array
    {
        return ['password'];
    }

    public function getSchemaVersion(): int
    {
        return 3;
    }

    public function getUpgradeSql(): array
    {
        return [
            "1_2" => [
                "ALTER TABLE `user` ADD COLUMN `role` INT NOT NULL DEFAULT 2 COMMENT '用户角色';",
                "UPDATE `user` SET `role` = 1 WHERE id = 1;",
                "UPDATE `user` SET `role` = 2 WHERE id <> 1;",
            ],
           
            "2_3" => [
                "ALTER TABLE `user` ADD COLUMN `permissions` TEXT COMMENT '用户权限数组';",
                "UPDATE `user` SET `permissions` = 'a:1:{i:0;s:3:\"all\";}' WHERE role = 1;",
                "UPDATE `user` SET `permissions` = 'a:0:{}' WHERE role <> 1;",
                "ALTER TABLE `user` DROP COLUMN `role`;",
                "DROP TABLE IF EXISTS `role`;"
                ]
        ];
    }

    // 用户权限：以数组存储具体权限标识
    public array $permissions = [];



    public function hasPermission(string $permission): bool
    {
        return (in_array('all', $this->permissions, true) || in_array($permission, $this->permissions, true));
    }

}
