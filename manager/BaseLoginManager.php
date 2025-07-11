<?php

declare(strict_types=1);

namespace nova\plugin\login\manager;

use nova\plugin\cookie\Session;
use nova\plugin\login\LoginConfig;

abstract class BaseLoginManager
{
    protected LoginConfig $loginConfig;

    public function __construct()
    {
        $this->loginConfig = new LoginConfig();
        Session::getInstance()->start();
    }

    abstract public function redirectToProvider(): string;

    abstract public static function register();

}
