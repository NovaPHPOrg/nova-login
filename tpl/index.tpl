<!DOCTYPE html>
<html lang="zh-cn">

<head>
    <title>遇见山</title>
    <!-- Meta tag Keywords -->
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, shrink-to-fit=no"/>
    <meta name="renderer" content="webkit"/>
    <meta charset="UTF-8" />
    {include file="publicHeader.tpl"}
    <link rel="stylesheet" href="../../../static/login/login.css">
    <style id="style">
    </style>
</head>

<body>

<div class="container">
    <!-- /form -->
    <mdui-card variant="filled" class="p-4 content-input">
        <h2>遇见山管理后台</h2>
        <form action="#" method="post" id="form">
            <mdui-text-field icon="person" name="username" label="用户名" class="mb-3"></mdui-text-field>
            <mdui-text-field icon="lock" name="password" label="密码" toggle-password  type="password" class="mb-3"></mdui-text-field>
            <mdui-button form="form" type="submit">登录</mdui-button>
        </form>
    </mdui-card>

    <div class="copyright text-center mt-2">
        <p class="copy-footer-29">© <script>document.write(new Date().getFullYear())</script> <a href="https://ankio.net">Ankio</a>. All rights reserved.</p>
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
{include file="publicScript.tpl"}
<script src="../../../static/login.js"></script>
</body>
</html>
