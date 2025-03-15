<?php

declare(strict_types=1);

namespace nova\plugin\login\manager;

use nova\plugin\login\db\Model\UserModel;

abstract class BaseLoginManager
{
    /**
     * Authenticate a user
     *
     * @param  array          $credentials User credentials
     * @return bool|UserModel Whether authentication was successful
     */
    abstract public function authenticate(array $credentials): bool|UserModel;

    abstract public  function redirectToProvider(): string;

    abstract public static function register();

}
