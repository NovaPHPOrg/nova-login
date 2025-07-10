<?php

namespace nova\plugin\login\db\Dao;

use nova\plugin\device\UserAgent;
use nova\plugin\login\db\Model\RecordModel;
use nova\plugin\login\db\Model\UserModel;
use nova\plugin\orm\object\Dao;

class RecordDao extends Dao
{
    function records(UserModel $user):array
    {
        return $this->select()->where([
            'user_id' => $user->id
        ])->commit(); //第一个是最晚的
    }

    function add(int $user_id): RecordModel
    {
        $record = new RecordModel();
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
        [$OsName, $OsImg, $BrowserName, $BrowserImg] = UserAgent::parse($ua);
        $record->device =  "$OsImg $OsName $BrowserImg $BrowserName";
        $record->user_id = $user_id;
        $record->time = time();
        $record->id = $this->insertModel($record);
        return $record;
    }

    function id(int $id): ?RecordModel
    {
        return $this->find("id",['id'=>$id]);
    }
}