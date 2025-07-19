<?php

declare(strict_types=1);

namespace nova\plugin\login\db\Model;

use nova\plugin\login\db\Dao\UserDao;
use nova\plugin\orm\object\Model;

/**
 * 用户登录记录模型类
 *
 * 用于管理用户登录记录的数据模型，包含登录时间、设备信息、用户ID、IP地址和地理位置等信息
 *
 * @package nova\plugin\login\db\Model
 * @since 1.0.0
 */
class RecordModel extends Model
{
    /**
     * 登录时间戳
     *
     * @var int 登录时间，Unix时间戳格式
     */
    public int $time = 0;

    /**
     * 登录设备信息
     *
     * @var string 设备标识，如浏览器、操作系统等信息
     */
    public string $device = "";

    /**
     * 用户ID
     *
     * @var int 关联的用户ID，对应用户表中的主键
     */
    public int $user_id = 0;

    /**
     * 登录IP地址
     *
     * @var string 用户登录时的IP地址
     */
    public string $ip = "";

    /**
     * 地理位置信息
     *
     * @var string 根据IP地址解析的地理位置信息
     */
    public string $addr = "";

    /**
     * 获取关联的用户信息
     *
     * 根据当前记录的user_id获取对应的用户模型对象
     *
     * @return UserModel|null 返回用户模型对象，如果用户不存在则返回null
     */
    public function user(): ?UserModel
    {
        return UserDAO::getInstance()->id($this->user_id);
    }
}
