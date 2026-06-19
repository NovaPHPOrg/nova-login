<!DOCTYPE html>
<html lang="zh-CN" class="mdui-theme-light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, shrink-to-fit=no"/>
    <meta name="renderer" content="webkit"/>
    <title>{$title} - 登录</title>

    <link rel="stylesheet" href="/static/bundle?file=framework/icons/fonts.css,framework/libs/mdui.css,framework/base.css,framework/utils/Loading.css&type=css&v={$__v}">

    <style>
        :root {
            /* 夜间：黑色遮罩深一点 */
            --login-overlay: rgba(0, 0, 0, 0.58);
            /* 遮罩上的文字：固定使用浅色主题 on 色，保证黑底可读 */
            --login-overlay-on: rgba(var(--mdui-color-on-primary-light), 0.88);
        }

        .mdui-theme-light {
            /* 白天：黑色遮罩浅一点 */
            --login-overlay: rgba(0, 0, 0, 0.38);
        }

        .mdui-theme-dark {
            --login-overlay: rgba(0, 0, 0, 0.58);
        }

        @media (prefers-color-scheme: light) {
            .mdui-theme-auto {
                --login-overlay: rgba(0, 0, 0, 0.38);
            }
        }

        @media (prefers-color-scheme: dark) {
            .mdui-theme-auto {
                --login-overlay: rgba(0, 0, 0, 0.58);
            }
        }

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
            background: var(--login-overlay);
            pointer-events: none;
            z-index: 1;
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

        .login-card {
            width: 100%;
            max-width: 400px;
        }

        .login-title {
            margin-bottom: 1.5rem;
            text-align: center;
            font-weight: 700;
            font-size: 1.5rem;
        }

        .login-overlay-text {
            color: var(--login-overlay-on);
        }

        .copyright {
            margin-top: 1rem;
            font-size: 0.875rem;
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

        .settings-fab  mdui-menu {
            background: transparent;
            border: 0;
            box-shadow: none;
            width: unset;
            max-width: unset;
            min-width: unset
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
        <mdui-card variant="filled" class="login-card p-4">
            <h2 class="login-title">{$title}管理后台</h2>
            <form action="#" method="post" id="loginForm">
                <mdui-text-field 
                    icon="people" 
                    name="username" 
                    label="账号" 
                    class="mb-3" 
                    required>
                </mdui-text-field>
                <mdui-text-field 
                    icon="lock" 
                    name="password" 
                    label="密码" 
                    toggle-password 
                    type="password" 
                    class="mb-3" 
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
