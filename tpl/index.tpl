<!DOCTYPE html>
<html lang="zh-CN" class="mdui-theme-auto">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, shrink-to-fit=no"/>
    <meta name="renderer" content="webkit"/>
    <title>{$title} - 登录</title>

    <link rel="stylesheet" href="/static/bundle?file=framework/icons/fonts.css,framework/libs/mdui.css,framework/base.css,framework/utils/Loading.css&type=css&v={$__v}">

    <link rel="apple-touch-icon" sizes="180x180" href="/static/icons/apple-touch-icon.png?v={$__v}"/>
    <link rel="icon" type="image/png" sizes="32x32" href="/static/icons/favicon-32x32.png?v={$__v}"/>
    <link rel="icon" type="image/png" sizes="16x16" href="/static/icons/favicon-16x16.png?v={$__v}"/>
    <link rel="icon" type="image/ico" href="/static/icons/favicon.ico?v={$__v}"/>

    <style>
        body {
            background-image: url('https://api.ankio.net/bing');
            background-size: cover;
            background-attachment: fixed;
            background-position: center;
            min-height: 100vh;
            position: relative;
        }

        body::before {
            content: '';
            position: fixed;
            inset: 0;
            background: rgba(var(--mdui-color-scrim), 0.38);
            pointer-events: none;
            z-index: 1;
        }

        .mdui-theme-dark body::before {
            background: rgba(var(--mdui-color-scrim), 0.55);
        }

        @media (prefers-color-scheme: dark) {
            .mdui-theme-auto body::before {
                background: rgba(var(--mdui-color-scrim), 0.55);
            }
        }

        .login-container {
            position: relative;
            z-index: 2;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 1rem;
            box-sizing: border-box;
        }

        /* 毛玻璃跟当前主题 surface-container 走，日浅夜深 */
        .login-card {
            width: 100%;
            max-width: 400px;
            padding: 2rem !important;
            background: rgba(var(--mdui-color-surface-container), 0.78) !important;
            border: 1px solid rgba(var(--mdui-color-outline-variant), 0.6);
            border-radius: var(--mdui-shape-corner-extra-large);
            backdrop-filter: blur(16px) saturate(140%);
            -webkit-backdrop-filter: blur(16px) saturate(140%);
            box-shadow: var(--mdui-elevation-level2);
            color: rgb(var(--mdui-color-on-surface));
            text-align: center;
        }

        .mdui-theme-dark .login-card {
            background: rgba(var(--mdui-color-surface-container), 0.55) !important;
        }

        @media (prefers-color-scheme: dark) {
            .mdui-theme-auto .login-card {
                background: rgba(var(--mdui-color-surface-container), 0.55) !important;
            }
        }

        .login-logo {
            width: min(5.5rem, 28vw);
            height: auto;
            aspect-ratio: 1 / 1;
            margin: 0 auto 1.25rem;
            display: block;
            object-fit: cover;
            border-radius: 22%;
            filter: drop-shadow(0 4px 12px rgba(var(--mdui-color-shadow), 0.35));
        }

        .login-title {
            margin: 0 0 1.75rem;
            color: rgb(var(--mdui-color-on-surface));
            font-size: var(--mdui-typescale-headline-small-size);
            font-weight: var(--mdui-typescale-headline-small-weight);
            line-height: var(--mdui-typescale-headline-small-line-height);
            letter-spacing: var(--mdui-typescale-headline-small-tracking);
        }

        .login-card mdui-text-field {
            text-align: left;
            margin-bottom: 1rem;
        }

        .login-card mdui-button {
            margin-top: 0.5rem;
        }

        .login-overlay-text {
            color: rgba(var(--mdui-color-on-primary-light), 0.88);
        }

        .copyright {
            margin-top: 1rem;
            font-size: var(--mdui-typescale-body-small-size);
            line-height: var(--mdui-typescale-body-small-line-height);
            text-align: center;
        }

        .copyright a {
            color: inherit;
            text-decoration: none;
        }

        .copyright a:hover {
            text-decoration: underline;
        }

        .settings-fab {
            position: fixed;
            right: 1rem;
            bottom: 1rem;
            z-index: 100;
            display: flex;
            flex-direction: column;
        }

        .settings-fab mdui-menu {
            background: transparent;
            border: 0;
            box-shadow: none;
            width: unset;
            max-width: unset;
            min-width: unset;
        }

        .hitokoto-container {
            position: fixed;
            bottom: 10px;
            left: 50%;
            transform: translateX(-50%);
            padding: 10px;
            max-width: 90%;
            text-align: center;
            z-index: 3;
            opacity: 0;
            transition: opacity 0.5s ease-in-out, transform 0.5s ease-in-out;
        }

        .hitokoto-container.show {
            opacity: 1;
            transform: translateX(-50%) translateY(0);
        }
    </style>
</head>

<body>
    <div class="login-container">
        <mdui-card variant="elevated" class="login-card">
            <img class="login-logo" src="/static/icons/apple-touch-icon.png?v={$__v}" alt="">
            <h2 class="login-title">{$title}管理后台</h2>
            <form action="#" method="post" id="loginForm">
                <mdui-text-field
                    variant="outlined"
                    icon="people"
                    name="username"
                    label="账号"
                    required>
                </mdui-text-field>
                <mdui-text-field
                    variant="outlined"
                    icon="lock"
                    name="password"
                    label="密码"
                    toggle-password
                    type="password"
                    required>
                </mdui-text-field>
                <mdui-button form="loginForm" type="submit" variant="filled" full-width>登录</mdui-button>
            </form>
        </mdui-card>

        <nova-captcha></nova-captcha>

        <div class="copyright login-overlay-text">
            <p>© {date('Y')} <a href="https://ankio.net" target="_blank">Ankio</a>. All rights reserved.</p>
        </div>
    </div>

    <div class="settings-fab">
        <mdui-dropdown>
            <mdui-fab icon="settings" slot="trigger"></mdui-fab>
            <mdui-menu>
                <theme-switcher class="mb-2"></theme-switcher>
                <lang-switcher></lang-switcher>
            </mdui-menu>
        </mdui-dropdown>
    </div>

    <div class="hitokoto-container login-overlay-text" id="hitokotoContainer">
        <p id="hitokoto"></p>
    </div>

    <script src="/static/bundle?file=
    framework/libs/vhcheck.min.js,
    framework/libs/mdui.global.min.js,
    framework/bootloader.js,
    framework/utils/Loading.js,
    framework/utils/Logger.js,
    framework/utils/Loader.js,
    framework/utils/Event.js,
    framework/utils/Toaster.js,
    framework/utils/Form.js,
    framework/utils/Request.js,
    framework/theme/ThemeSwitcher.js,
    framework/language/NodeUtils.js,
    framework/language/TranslateUtils.js,
    framework/language/Language.js,
    components/captcha/Captcha.js
    &type=js&v={$__v}"></script>
    <script src="/login/static/js/index.js"></script>
</body>
</html>
