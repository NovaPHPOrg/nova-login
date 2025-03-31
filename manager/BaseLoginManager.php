<?php

declare(strict_types=1);

namespace nova\plugin\login\manager;

abstract class BaseLoginManager
{
    abstract public function redirectToProvider(): string;

    abstract public static function register();

}
