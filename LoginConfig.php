<?php

declare(strict_types=1);

namespace nova\plugin\login;

use app\Application;
use nova\framework\core\ConfigObject;

/**
 * 登录配置类
 *
 * 管理登录模块的所有配置项
 *
 * @package nova\plugin\login
 * @since 1.0.0
 */
class LoginConfig extends ConfigObject
{
    /**
     * @var int 允许的最大同时登录数
     */
    public int $allowedLoginCount = 1;

    /**
     * @var string 登录成功后的默认回调地址
     */
    public string $loginCallback = '/';

    /**
     * @var string 系统名称
     */
    public string $systemName = Application::SYSTEM_NAME;

    /**
     * @var bool 是否启用SSO单点登录
     */
    public bool $ssoEnable = false;

    /**
     * @var string SSO认证服务器URL
     */
    public string $ssoProviderUrl = '';

    /**
     * @var string SSO客户端ID
     */
    public string $ssoClientId = '';

    /**
     * @var string SSO客户端密钥
     */
    public string $ssoClientSecret = '';

    /**
     * @var bool 是否必须已有账户才能SSO登录
     */
    public bool $ssoMustHasAccount = true;

    /**
     * @var string SSO用户标识字段名
     */
    public string $ssoUserField = 'username';

    /**
     * @var string SSO用户信息接口路径
     */
    public string $ssoUserInfoUrl = '/userinfo';

    /**
     * @var string SSO显示名称字段
     */
    public string $ssoDisplayNameField = 'nickname';

    /**
     * @var string SSO头像字段
     */
    public string $ssoAvatarField = 'avatar_url';
}
