<?php

/*
 * Copyright (c) 2025. Lorem ipsum dolor sit amet, consectetur adipiscing elit.
 * Morbi non lorem porttitor neque feugiat blandit. Ut vitae ipsum eget quam lacinia accumsan.
 * Etiam sed turpis ac ipsum condimentum fringilla. Maecenas magna.
 * Proin dapibus sapien vel ante. Aliquam erat volutpat. Pellentesque sagittis ligula eget metus.
 * Vestibulum commodo. Ut rhoncus gravida arcu.
 */

declare(strict_types=1);

/**
 * 登录模块包配置
 *
 * @package nova\plugin\login
 * @since 1.0.0
 */

return [
    "config" => [
        "framework_start" => [
            "nova\\plugin\\login\\LoginManager",
        ],
    ],
    "require" => [
        "tpl", "captcha", "avatar", "http", "device", "ip","orm"
    ],
];
