<!DOCTYPE html>
<html lang="zh-cn">

<head>
    <title>{$title}</title>
    <!-- Meta tag Keywords -->
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, shrink-to-fit=no"/>
    <meta name="renderer" content="webkit"/>
    <meta charset="UTF-8" />
    {*MDUI JS库*}
    <link rel="stylesheet" href="/static/framework/libs/mdui.css?v={$__v}">
    <link rel="stylesheet" href="/static/framework/base.css?v={$__v}">
    <script src="/static/framework/libs/mdui.global.min.js?v={$__v}"></script>
    <link rel="stylesheet" href="/static/framework/icons/fonts.css?v={$__v}">
    <link rel="stylesheet" href="/static/framework/utils/Loading.css?v={$__v}">
    <script src="/static/framework/libs/vhcheck.min.js?v={$__v}"></script>

    {if $__debug}
        <meta http-equiv="expires" content="0">
        <meta http-equiv="pragma" content="no-cache">
        <meta http-equiv="cache-control" content="no-cache">
    {/if}


    <style id="style">
        body{
            font-family: 'Roboto', 'Helvetica Neue', Arial, sans-serif;
            font-size: 16px;
            line-height: 1.5;
            color: rgba(var(--mdui-color-on-background));
            background-color: rgba(var(--mdui-color-background));
            margin: 0;
            padding: 0;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;

            /* 设置背景图片 */
            background-image: url('https://api.dujin.org/bing/1366.php');

            /* 背景图片覆盖整个页面 */
            background-size: cover;

            /* 固定背景图片 */
            background-attachment: fixed;

            /* 图片在背景中的位置 */
            background-position: center;

            /* 确保内容区域可滚动 */

            height: 100vh;
            overflow-y: auto;
            position: relative;
            z-index: 0;
        }
        :root{
            --overlay-color: rgba(0,0,0,0.5);
        }

        .mdui-theme-light{
            --overlay-color: rgba(191,191,191,0.3);
        }

        .mdui-theme-auto{
            @media (prefers-color-scheme: light) {
                --overlay-color: rgba(191,191,191,0.3);
            }
        }
        body::before {
            content: '';

            /* 固定叠加层位置 */
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;

            /* 半透明的黑色叠加层，颜色可以根据需要调整 */
            background:var(--overlay-color);

            /* 使叠加层位于背景图片之上，内容之下 */
            z-index: 1;
            pointer-events: none; /* 允许点击事件穿过叠加层 */
        }




        .container {
            z-index: 2;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 1rem;
        }

        mdui-card {
        }

        .content-input {
        }

        .content-input h2 {
            margin-bottom: 1.5rem;
            text-align: center;
            font-weight: 700;
            font-size: 1.5rem;
        }

        .content-input form {
            display: flex;
            flex-direction: column;
        }


        .copyright {
            font-size: 0.875rem;
            color: rgba(var(--mdui-color-on-background),0.8);
        }

        .copyright a {
            color: inherit;
            text-decoration: none;
        }

        .copyright a:hover {
            text-decoration: underline;
        }

        @media (min-width: 768px) {
            .mdui-card {
                flex-direction: row;
            }

        }

        :root{
            --nova-padding: 1rem;
        }

        .right{
            display: flex;
            position: fixed;
            right: 1rem;
            bottom: 1rem;
            z-index:3;

        }
        .right mdui-menu{
            background: transparent;
            border: none;
            box-shadow: none;
            width: unset;
            max-width: unset;
            min-width: unset;
        }

        .hitokoto-container {
            position: fixed;
            bottom: 10px;
            left: 50%;
            padding: 10px;
            border-radius: 5px;
            max-width: 90%;
            overflow: hidden;
            text-align: center;
            transition: transform 0.5s ease-in-out, opacity 0.5s ease-in-out;
            opacity: 0;
            transform: translate(-50%, 20px);
            color: rgba(var(--mdui-color-on-background),0.8);
            z-index: 3;
        }
        .hitokoto-container.show {
            opacity: 1;
            transform: translate(-50%, 0);
        }
    </style>
</head>

<body>

<div class="container">
    <!-- /form -->
    <mdui-card variant="filled" class="p-4 content-input">
        <h2>{$title}管理后台</h2>
        <form action="#" method="post" id="form">
            <mdui-text-field icon="mail" name="email" label="邮箱" class="mb-3" required></mdui-text-field>
            <mdui-text-field icon="lock" name="password" label="密码" toggle-password  type="password" class="mb-3" required></mdui-text-field>
            <mdui-button form="form" type="submit">登录</mdui-button>
        </form>
    </mdui-card>
    <nova-captcha></nova-captcha>
    <div class="copyright text-center mt-2">
        <p>© <script>document.write(new Date().getFullYear())</script> <a href="https://ankio.net">Ankio</a>. All rights reserved.</p>
    </div>

    <!-- //form -->
</div>
<!-- copyright-->
<mdui-dropdown class="right">
    <mdui-fab icon="settings"  slot="trigger"></mdui-fab>
    <mdui-menu>
        <theme-switcher class="mb-2"></theme-switcher>
        <lang-switcher></lang-switcher>
    </mdui-menu>

</mdui-dropdown>

<div class="hitokoto-container" id="hitokotoContainer">
    <p id="hitokoto"></p>
</div>
<script src="/static/framework/bootloader.js?v={$__v}"></script>
<script src="/static/framework/utils/Loading.js?v={$__v}"></script>
<script src="/static/framework/utils/Logger.js?v={$__v}"></script>
<script src="/static/framework/utils/Loader.js?v={$__v}"></script>
<script src="/static/framework/utils/Event.js?v={$__v}"></script>
<script src="/static/framework/utils/Toaster.js?v={$__v}"></script>
<script src="/static/framework/utils/Request.js?v={$__v}"></script>
<script src="/static/components/theme/ThemeSwitcher.js?v={$__v}"></script>
<script src="/static/components/language/Language.js?v={$__v}"></script>
<script src="/static/components/captcha/Captcha.js?v={$__v}"></script>
<script>
    window._v = "{$__v}"
    let level = debug ? 'debug' : 'error';
    $.logger.setLevel(level);
    $.logger.info('App is running in ' + level + ' mode');
    $.preloader([
        'Loading',
        'Logger',
        'Event',
        'Toaster',
        'Request',
        'ThemeSwitcher',
        'Language',
        'Captcha'
    ]);
    window.loading && window.loading.close();
    $.request.setBaseUrl(baseUri).setOnCode(401,()=>{
        $.toaster.error('登录已过期，请重新登录');
        setTimeout(()=>{
            window.location.href = '/login';
        },1000);
    });
</script>
<script>
    let needCaptcha = false;

    // 页面加载时检查是否需要显示验证码
    $.request.get("/login/need_captcha", function(response) {
        if (response.code === 200 && response.data === true) {
            needCaptcha = true;
        }
    });

    $("#form").on("submit", function (e) {
        e.preventDefault();
        let data = $(this).serializeObject();
        
        // 验证邮箱和密码是否为空
        if (!data.email || !data.email.trim()) {
            $.toaster.error('请输入邮箱');
            return false;
        }
        if (!data.password || !data.password.trim()) {
            $.toaster.error('请输入密码');
            return false;
        }

        const submitForm = (captchaData = null) => {
            if (captchaData) {
                data.captcha = captchaData.captcha;
                data.captcha_state = captchaData.state;
            }

            let loading = new Loading(document.querySelector("#form"));
            loading.show();
            
            $.request.postForm("/login/pwd", data, function (response) {
                if (response.code === 200) {
                    $.toaster.success(response.msg);
                    setTimeout(function () {
                        location.href = response.data;
                    }, 500);
                } else {
                    $.toaster.error(response.msg);
                    // 根据后端返回决定是否显示验证码
                    if (response.need_captcha) {
                        needCaptcha = true;
                        showCaptcha(submitForm);
                    }
                    loading.close();
                }
            }, function () {
                loading.close();
            });
        };

        if (needCaptcha) {
            showCaptcha(submitForm);
        } else {
            submitForm();
        }
        
        return false;
    });

    function showCaptcha(submitForm) {
        let captcha = document.querySelector("nova-captcha");
        captcha.show(function (data) {
            submitForm(data.captcha)
        })
    }

    function fetchHitokoto() {
        fetch('https://international.v1.hitokoto.cn')
            .then(response => response.json())
            .then(data => {
                const hitokotoElement = document.getElementById('hitokoto');
                const hitokotoContainer = document.getElementById('hitokotoContainer');

                hitokotoContainer.classList.remove('show');
                let t = Date.now();
                $.translate(data.hitokoto, (translated) => {
                    let t4 = 500 - (Date.now() - t);
                    t4 = t4 < 0 ? 0 : t4;
                    setTimeout(() => {
                        hitokotoElement.innerText = translated;
                        hitokotoContainer.classList.add('show');
                    }, t4);
                });
                setTimeout(() => {
                    hitokotoElement.innerText = data.hitokoto;
                    hitokotoContainer.classList.add('show');
                }, 500); // Delay to allow for collapse before showing new text
            })
            .catch(console.error);
    }

    setInterval(fetchHitokoto, 5000); // Fetch new hitokoto every 5 seconds

    // Initial fetch
    fetchHitokoto();
</script>
</body>
</html>
