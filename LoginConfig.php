<?php

declare(strict_types=1);

namespace nova\plugin\login;

use nova\framework\core\ConfigObject;

class LoginConfig extends ConfigObject
{
    /* ---------- 基础 ---------- */
    public int    $allowedLoginCount    = 1;
    public string $loginCallback = '/';
    public string $systemName    = '登录';

    /* ---------- SSO ---------- */
    public bool   $ssoEnable         = false;
    public string $ssoProviderUrl    = '';
    public string $ssoClientId       = '';
    public string $ssoClientSecret   = '';
    public bool   $ssoMustHasAccount = true;
}
