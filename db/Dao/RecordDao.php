<?php

declare(strict_types=1);

namespace nova\plugin\login\db\Dao;

use nova\framework\core\Context;
use nova\plugin\device\UserAgent;
use nova\plugin\login\db\Model\RecordModel;
use nova\plugin\login\db\Model\UserModel;
use nova\plugin\orm\object\Dao;

class RecordDao extends Dao
{
    public function records(UserModel $user): array
    {
        return $this->select()
            ->where(['user_id' => $user->id])
            ->orderBy('time', 'DESC')
            ->orderBy('id', 'DESC')
            ->commit();
    }

    public function add(int $user_id): RecordModel
    {
        $record = new RecordModel();
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
        [$OsName, $OsImg, $BrowserName, $BrowserImg] = UserAgent::parse($ua);
        $record->device = "$OsImg $OsName $BrowserImg $BrowserName";
        $record->user_id = $user_id;
        $record->time = time();
        $record->ip = Context::instance()->request()->getClientIP();
        $record->id = $this->insertModel($record);

        return $record;
    }

    public function id(int $id): ?RecordModel
    {
        return $this->find(null, ['id' => $id]);
    }

    public function deleteByUserId(int $userId): void
    {
        $this->delete()->where(['user_id' => $userId])->commit();
    }
}
