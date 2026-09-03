<!DOCTYPE html>
<html lang="zh-CN" class="mdui-theme-auto">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, shrink-to-fit=no"/>
    <meta name="renderer" content="webkit"/>
    <title>{$title} - 已退出</title>

    <link rel="stylesheet" href="/static/bundle?file=framework/icons/fonts.css,framework/libs/mdui.css,framework/base.css&type=css&v={$__v}">

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

        .logout-container {
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

        .logout-card {
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

        .mdui-theme-dark .logout-card {
            background: rgba(var(--mdui-color-surface-container), 0.55) !important;
        }

        @media (prefers-color-scheme: dark) {
            .mdui-theme-auto .logout-card {
                background: rgba(var(--mdui-color-surface-container), 0.55) !important;
            }
        }

        .logout-icon {
            font-size: 3rem;
            color: rgb(var(--mdui-color-primary));
            margin-bottom: 1rem;
        }

        .logout-title {
            margin: 0 0 0.75rem;
            font-size: var(--mdui-typescale-headline-small-size);
            font-weight: var(--mdui-typescale-headline-small-weight);
            line-height: var(--mdui-typescale-headline-small-line-height);
        }

        .logout-desc {
            margin: 0;
            color: rgb(var(--mdui-color-on-surface-variant));
            font-size: var(--mdui-typescale-body-medium-size);
            line-height: var(--mdui-typescale-body-medium-line-height);
        }

        .logout-actions {
            margin-top: 1.5rem;
        }

        .logout-actions mdui-button {
            width: 100%;
        }

        .copyright {
            margin-top: 1rem;
            font-size: var(--mdui-typescale-body-small-size);
            text-align: center;
            color: rgba(var(--mdui-color-on-primary-light), 0.88);
        }

        .copyright a {
            color: inherit;
            text-decoration: none;
        }

        .copyright a:hover {
            text-decoration: underline;
        }
    </style>
    <script src="/static/bundle?file=framework/libs/mdui.global.min.js&type=js&v={$__v}"></script>
</head>
<body>
    <div class="logout-container">
        <mdui-card variant="elevated" class="logout-card">
            <mdui-icon class="logout-icon" name="check_circle"></mdui-icon>
            <h2 class="logout-title">已退出登录</h2>
            <p class="logout-desc">你可以关闭此页面了</p>
            {if $logoutRedirect != ''}
            <div class="logout-actions">
                <mdui-button href="{$logoutRedirect}" variant="tonal" icon="arrow_forward">继续访问</mdui-button>
            </div>
            {/if}
        </mdui-card>
        <div class="copyright">
            <p>© {date('Y')} <a href="https://ankio.net" target="_blank">Ankio</a>. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
