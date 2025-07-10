<?php

namespace nova\plugin\login\db\Model;

use nova\plugin\login\db\Dao\UserDao;
use nova\plugin\orm\object\Model;

class RecordModel extends Model
{
    public int $time = 0;
    public string $device = "";
    public int $user_id = 0;

    public string $ip = "";

    public string $addr= "";

    function user():?UserModel
    {
        return UserDAO::getInstance()->id($this->user_id);
    }
}