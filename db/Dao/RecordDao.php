<?php

declare(strict_types=1);

namespace nova\plugin\login\db\Dao;

use nova\framework\core\Context;
use nova\plugin\device\UserAgent;
use nova\plugin\ip\Ip2Region;
use nova\plugin\login\db\Model\RecordModel;
use nova\plugin\login\db\Model\UserModel;
use nova\plugin\orm\object\Dao;

/**
 * 用户登录记录数据访问对象
 *
 * 负责处理用户登录记录的数据库操作，包括查询用户登录记录、
 * 添加新的登录记录以及根据ID查找特定记录等功能。
 *
 * @package nova\plugin\login\db\Dao
 */
class RecordDao extends Dao
{
    /**
     * 获取指定用户的所有登录记录
     *
     * 根据用户ID查询该用户的所有登录记录，按时间倒序排列，
     * 最新的登录记录排在第一位。
     *
     * @param  UserModel $user 用户模型对象
     * @return array     返回该用户的所有登录记录数组
     */
    public function records(UserModel $user): array
    {
        return $this->select()->where([
            'user_id' => $user->id
        ])->commit(); //第一个是最晚的
    }

    /**
     * 添加新的用户登录记录
     *
     * 创建并保存一条新的用户登录记录，包括：
     * - 设备信息（操作系统和浏览器）
     * - 用户ID
     * - 登录时间戳
     * - IP地址
     * - IP地址对应的地理位置信息
     *
     * @param  int         $user_id 用户ID
     * @return RecordModel 返回创建的登录记录模型对象
     */
    public function add(int $user_id): RecordModel
    {
        $record = new RecordModel();
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
        [$OsName, $OsImg, $BrowserName, $BrowserImg] = UserAgent::parse($ua);
        $record->device =  "$OsImg $OsName $BrowserImg $BrowserName";
        $record->user_id = $user_id;
        $record->time = time();
        // 使用框架请求对象获取规范化后的客户端 IP（移除可能的端口）
        $record->ip = Context::instance()->request()->getClientIP();
        $ip2region = new Ip2Region();
        $record->addr = $ip2region->simple($record->ip);
        $record->id = $this->insertModel($record);

        return $record;
    }

    /**
     * 根据记录ID查找登录记录
     *
     * 通过记录的唯一标识符查找特定的登录记录。
     *
     * @param  int              $id 登录记录ID
     * @return RecordModel|null 返回找到的登录记录模型对象，如果未找到则返回null
     */
    public function id(int $id): ?RecordModel
    {
        return $this->find(null, ['id' => $id]);
    }
}
