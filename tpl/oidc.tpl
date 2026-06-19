<title id="title">统一身份认证（OIDC） - {$title}</title>
<style id="style">
    .action-buttons {
        display: flex;
        justify-content: flex-end;
    }

    mdui-card {
        width: 100%;
    }
</style>

<div id="container" class="container p-4">
    <div class="row col-space16">
        <div class="col-xs-12 title-large center-vertical mb-4">
            <mdui-icon name="badge" class="refresh mr-2"></mdui-icon>
            <span>统一身份认证（OIDC）</span>
        </div>

        <div class="col-xs-12">
            <form class="row col-space16" id="form_oidc">
                <div class="col-xs-12 headline-small mb-2">基础设置</div>

                <div class="col-xs-12">
                    <mdui-text-field
                        label="系统名称"
                        name="systemName"
                        type="text"
                        variant="outlined"
                        required
                        helper="显示在登录页面的系统名称"
                    ></mdui-text-field>
                </div>

                <div class="col-xs-12 col-md-6">
                    <mdui-text-field
                        label="允许同时登录数"
                        name="allowedLoginCount"
                        type="number"
                        variant="outlined"
                        required
                        min="1"
                        helper="同一账号允许同时在线的设备数量"
                    ></mdui-text-field>
                </div>

                <div class="col-xs-12 col-md-6">
                    <mdui-text-field
                        label="登录成功回调"
                        name="loginCallback"
                        type="text"
                        variant="outlined"
                        required
                        helper="登录成功后默认跳转的页面路径"
                    ></mdui-text-field>
                </div>

                <div class="col-xs-12 headline-small mt-4 mb-2">SSO 设置</div>

                <div class="col-xs-12 col-md-6 center-vertical">
                    <span class="form-item mr-2">将OIDC作为默认登录服务</span>
                    <mdui-switch name="ssoEnable" value="1"></mdui-switch>
                </div>

                <div class="col-xs-12 col-md-6 center-vertical">
                    <span class="form-item mr-2">必须系统里面有账户才能登录</span>
                    <mdui-switch name="ssoMustHasAccount" value="1"></mdui-switch>
                </div>

                <div class="col-xs-12">
                    <mdui-text-field
                        label="OIDC提供者"
                        name="ssoProviderUrl"
                        type="text"
                        variant="outlined"
                        required
                        helper="类似于https://xx.xx.xx"
                    ></mdui-text-field>
                </div>

                <div class="col-xs-12">
                    <mdui-text-field
                        label="用户唯一标识字段"
                        name="ssoUserField"
                        type="text"
                        variant="outlined"
                        required
                        helper="SSO返回的用户信息中用于匹配系统账号的字段，默认为username"
                    ></mdui-text-field>
                </div>

                <div class="col-xs-12">
                    <mdui-text-field
                        label="获取用户信息URL"
                        name="ssoUserInfoUrl"
                        type="text"
                        variant="outlined"
                        required
                        helper="SSO提供的获取用户信息接口路径，默认为/userinfo"
                    ></mdui-text-field>
                </div>

                <div class="col-xs-12 col-md-6">
                    <mdui-text-field
                        label="用户名称字段"
                        name="ssoDisplayNameField"
                        type="text"
                        variant="outlined"
                        required
                        helper="SSO返回的用户信息中作为显示名称的字段，默认为nickname"
                    ></mdui-text-field>
                </div>

                <div class="col-xs-12 col-md-6">
                    <mdui-text-field
                        label="用户头像字段"
                        name="ssoAvatarField"
                        type="text"
                        variant="outlined"
                        required
                        helper="SSO返回的用户信息中作为头像URL的字段，默认为avatar_url"
                    ></mdui-text-field>
                </div>

                <div class="col-xs-12 col-md-6">
                    <mdui-text-field
                        label="客户端ID"
                        name="ssoClientId"
                        type="text"
                        variant="outlined"
                    ></mdui-text-field>
                </div>

                <div class="col-xs-12 col-md-6">
                    <mdui-text-field
                        label="客户端密钥"
                        name="ssoClientSecret"
                        type="password"
                        variant="outlined"
                    ></mdui-text-field>
                </div>

                <div class="col-xs-12 action-buttons">
                    <mdui-button id="save_oidc" icon="save" type="submit">
                        保存修改
                    </mdui-button>
                </div>
            </form>
        </div>
    </div>
</div>

<script id="script" src="/login/static/js/oidc.js?v={$__v}"></script>