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
 * @since 1.0.0
 */
class RecordDao extends Dao
{
    /**
     * 获取指定用户的所有登录记录
     *
     * @param  UserModel $user 用户模型对象
     * @return array     返回该用户的所有登录记录数组（按时间倒序）
     */
    public function records(UserModel $user): array
    {
        return $this->select()
            ->where(['user_id' => $user->id])
            ->orderBy('time', 'DESC')
            ->orderBy('id', 'DESC')
            ->commit();
    }

    /**
     * 添加新的用户登录记录
     *
     * @param  int         $user_id 用户ID
     * @return RecordModel 返回创建的登录记录模型对象
     */
    public function add(int $user_id): RecordModel
    {
        $record = new RecordModel();
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
        [$OsName, $OsImg, $BrowserName, $BrowserImg] = UserAgent::parse($ua);
        $record->device = "$OsImg $OsName $BrowserImg $BrowserName";
        $record->user_id = $user_id;
        $record->time = time();
        $record->ip = Context::instance()->request()->getClientIP();
        $ip2region = new Ip2Region();
        $record->addr = $ip2region->simple($record->ip);
        $record->id = $this->insertModel($record);

        return $record;
    }

    /**
     * 根据记录ID查找登录记录
     *
     * @param  int              $id 登录记录ID
     * @return RecordModel|null 返回找到的登录记录模型对象，如果未找到则返回null
     */
    public function id(int $id): ?RecordModel
    {
        return $this->find(null, ['id' => $id]);
    }

    public function deleteByUserId(int $userId): void
    {
        $this->delete()->where(['user_id' => $userId])->commit();
    }
}
