<?php
declare(strict_types=1);

namespace nova\plugin\login\manager;

use nova\framework\http\Response;
use nova\plugin\login\db\Model\UserModel;

abstract class BaseLoginManager
{
    /**
     * Authenticate a user
     *
     * @param array $credentials User credentials
     * @return bool|UserModel Whether authentication was successful
     */
    public abstract function authenticate(array $credentials): bool|UserModel;


    public abstract function redirectToProvider():string;


    static abstract function register();

}