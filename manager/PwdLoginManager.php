<?php

declare(strict_types=1);

namespace nova\plugin\login\manager;

/**
 * 密码登录管理器
 *
 * 处理基于用户名和密码的登录流程
 *
 * @package nova\plugin\login\manager
 * @since 1.0.0
 */
class PwdLoginManager extends BaseLoginManager
{
    /**
     * 重定向到登录提供者
     *
     * @return string 登录页面URL
     */
    public function redirectToProvider(): string
    {
        return '/login';
    }
}
