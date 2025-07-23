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
                <div class="col-xs-12 center-vertical">
                    <span class="form-item mr-2">将OIDC作为默认登录服务</span>
                    <mdui-switch name="ssoEnable" value="0"></mdui-switch>
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

<script id="script">
    window.pageLoadFiles = [
        'Form',
    ];

    window.pageOnLoad = function (loading) {
        $.form.manage("/sso/config","#form_oidc");

        window.pageOnUnLoad = function () {
            // 页面卸载时的清理工作
        };
    };
</script> 