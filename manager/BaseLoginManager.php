<?php

declare(strict_types=1);

namespace nova\plugin\login\manager;

use nova\plugin\login\db\Model\UserModel;

abstract class BaseLoginManager
{


    abstract public function redirectToProvider(): string;

    abstract public static function register();

}
