<!DOCTYPE html>
<html lang="zh-CN" class="mdui-theme-light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, shrink-to-fit=no"/>
    <meta name="renderer" content="webkit"/>
    <title>{$title} - 登录</title>
    <link rel="preconnect" href="https://fonts.loli.net">
    <link rel="preconnect" href="https://gstatic.loli.net" crossorigin>
    <!-- 使用 font-display=swap 避免字体加载时的布局偏移 -->
    <link href="https://fonts.loli.net/css2?family=Material+Icons&family=Material+Icons+Outlined&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="/static/bundle?file=
    framework/libs/mdui.css,
    framework/base.css,
    framework/utils/Loading.css
    &type=css&v={$__v}">

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
            background: var(--overlay-color);
            pointer-events: none;
        }

        :root {
            --overlay-color: rgba(0, 0, 0, 0.5);
        }

        .mdui-theme-light {
            --overlay-color: rgba(191, 191, 191, 0.3);
        }

        @media (prefers-color-scheme: light) {
            .mdui-theme-auto {
                --overlay-color: rgba(191, 191, 191, 0.3);
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

        .copyright {
            margin-top: 1rem;
            font-size: 0.875rem;
            color: rgba(var(--mdui-color-on-background), 0.8);
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
            color: rgba(var(--mdui-color-on-background), 0.8);
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

        <div class="copyright">
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

    <div class="hitokoto-container" id="hitokotoContainer">
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
    <script>

        const level = debug ? 'debug' : 'error';
        
        $.logger.setLevel(level);
        $.logger.info('App is running in ' + level + ' mode');

        // 登录表单处理
        $.form.submit("#loginForm", {
            callback: function (data) {
                if (!data.username?.trim()) {
                    $.toaster.error('请输入账号');
                    return false;
                }
                if (!data.password?.trim()) {
                    $.toaster.error('请输入密码');
                    return false;
                }

                const captcha = document.querySelector("nova-captcha");
                captcha.show((captchaData) => {
                    data.captcha = captchaData.captcha;
                    
                    $('#loginForm').showLoading();
                    
                    $.request.postForm("/login/pwd", data, 
                        (response) => {
                            if (response.code === 200) {
                                $.toaster.success(response.msg);
                                setTimeout(() => {
                                    location.href = response.data;
                                }, 500);
                            } else {
                                $.toaster.error(response.msg);
                                $('#loginForm').closeLoading();
                            }
                        }, 
                        () => {
                            $('#loginForm').closeLoading();
                        }
                    );
                });

                return false;
            }
        });

        // 一言功能
        function fetchHitokoto() {
            fetch('https://api.ankio.net/hitokoto')
                .then(response => response.json())
                .then(data => {
                    const hitokotoElement = document.getElementById('hitokoto');
                    const hitokotoContainer = document.getElementById('hitokotoContainer');

                    hitokotoContainer.classList.remove('show');
                    
                    const startTime = Date.now();
                    $.translate(data.data.hitokoto, (translated) => {
                        const delay = Math.max(0, 500 - (Date.now() - startTime));
                        setTimeout(() => {
                            hitokotoElement.innerText = translated;
                            hitokotoContainer.classList.add('show');
                        }, delay);
                    });
                    
                    setTimeout(() => {
                        hitokotoElement.innerText = data.data.hitokoto;
                        hitokotoContainer.classList.add('show');
                    }, 500);
                })
                .catch(console.error);
        }

        // 每30秒更新一次一言
        setInterval(fetchHitokoto, 30000);
        fetchHitokoto();
    </script>
</body>
</html>
