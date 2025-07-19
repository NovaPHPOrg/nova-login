<?php

declare(strict_types=1);

namespace nova\plugin\login\manager;

use nova\plugin\login\LoginConfig;

/**
 * 登录管理器基类
 *
 * 这是一个抽象基类，定义了登录管理器的基本结构和接口。
 * 所有具体的登录管理器（如密码登录、SSO登录等）都应该继承此类。
 *
 * @package nova\plugin\login\manager
 * @author Nova Framework
 */
abstract class BaseLoginManager
{
    /**
     * 登录配置对象
     *
     * 包含登录相关的配置信息，如登录URL、回调地址等
     *
     * @var LoginConfig
     */
    protected LoginConfig $loginConfig;

    /**
     * 构造函数
     *
     * 初始化登录管理器，创建默认的登录配置对象
     */
    public function __construct()
    {
        $this->loginConfig = new LoginConfig();
    }

    /**
     * 重定向到登录提供者
     *
     * 抽象方法，子类必须实现此方法来处理用户重定向到具体的登录提供者
     * （如OAuth服务商、SSO服务等）
     *
     * @return string 重定向URL
     */
    abstract public function redirectToProvider(): string;

    /**
     * 注册登录管理器
     *
     * 静态方法，用于在系统中注册具体的登录管理器实现。
     * 子类必须实现此方法来完成登录管理器的注册流程。
     *
     * @return void
     */
    abstract public static function register();

}
