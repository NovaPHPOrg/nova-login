<?php

declare(strict_types=1);

namespace nova\plugin\login\manager;

use nova\framework\core\Instance;

/**
 * 登录管理器基类
 *
 * 这是一个抽象基类，定义了登录管理器的基本结构和接口。
 * 所有具体的登录管理器（如密码登录、SSO登录等）都应该继承此类。
 *
 * @package nova\plugin\login\manager
 * @since 1.0.0
 */
abstract class BaseLoginManager extends Instance
{
    /**
     * 重定向到登录提供者
     *
     * 抽象方法，子类必须实现此方法来处理用户重定向到具体的登录提供者
     * （如OAuth服务商、SSO服务等）
     *
     * @return string 重定向URL
     */
    abstract public function redirectToProvider(): string;
}
