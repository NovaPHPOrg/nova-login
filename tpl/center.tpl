<title id="title">账户安全</title>
<style id="style">
    .action-buttons {
        display: flex;
        justify-content: flex-end;
    }
    mdui-card {
        width: 100%;
    }
</style>

<div id="container" class="container">
    <div class="row col-space16 p-4">
        <div class="col-xs12">
            <h2>账户安全</h2>
        </div>
        <div class="col-xs12">
            <mdui-tabs value="passwd" full-width>
                <mdui-tab value="passwd" icon="password">密码安全</mdui-tab>
                <mdui-tab value="oidc" icon="badge">统一身份认证（OIDC）</mdui-tab>
                <mdui-tab-panel slot="panel" value="passwd">

                    <div class="tip-container">
                        <p>修改密码或者修改账号均需要验证密码</p>
                    </div>
                    <form class="row col-space16" id="form_pwd">
                        <div class="col-md12">
                            <mdui-text-field
                                    label="账号"
                                    name="username"
                                    type="text"
                                    variant="outlined"
                                    value="{$username}"
                                    required
                                    helper="请记住你修改后的账号地址，下次使用该账号登录"
                            ></mdui-text-field>
                        </div>
                        <div class="col-md12 ">
                            <div class="row col-space16">
                                <div class="col-md12">
                                    <mdui-text-field
                                            label="当前密码"
                                            name="current_password"
                                            type="password"
                                            variant="outlined"
                                            required
                                            helper="请输入您的当前密码以验证身份"
                                    ></mdui-text-field>
                                </div>
                                <div class="col-xs12 col-md6">
                                    <mdui-text-field
                                            label="新密码"
                                            name="new_password"
                                            type="password"
                                            variant="outlined"
                                            helper="如需要修改密码，请输入新的密码"
                                    ></mdui-text-field>
                                </div>
                                <div class="col-xs12 col-md6">
                                    <mdui-text-field
                                            label="确认新密码"
                                            name="confirm_password"
                                            type="password"
                                            variant="outlined"
                                            helper="请再次输入新密码以确保输入正确"
                                    ></mdui-text-field>
                                </div>
                            </div>
                        </div>
                        <div class="col-md12 action-buttons">
                            <mdui-button id="save" icon="save" type="submit">
                                保存修改
                            </mdui-button>
                        </div>
                    </form>

                </mdui-tab-panel>
                <mdui-tab-panel slot="panel" value="oidc">

                    <div class="tip-container">
                        <p>仅支持标准OIDC服务</p>
                    </div>
                    <form class="row col-space16" id="form_oidc">
                        <div class="col-xs12 center-vertical">
                            <span class="form-item mr-2">将OIDC作为默认登录服务</span>
                            <mdui-switch name="ssoEnable" value="0" ></mdui-switch>
                        </div>
                        <div class="col-md12 ">
                            <div class="row col-space16">
                                <div class="col-md12">
                                    <mdui-text-field
                                            label="OIDC提供者"
                                            name="ssoProviderUrl"
                                            type="text"
                                            variant="outlined"
                                            required
                                            helper="类似于https://xx.xx.xx"
                                    ></mdui-text-field>
                                </div>
                                <div class="col-xs12 col-md6">
                                    <mdui-text-field
                                            label="客户端ID"
                                            name="ssoClientId"
                                            type="text"
                                            variant="outlined"
                                            helper=""
                                    ></mdui-text-field>
                                </div>
                                <div class="col-xs12 col-md6">
                                    <mdui-text-field
                                            label="客户端密钥"
                                            name="ssoClientSecret"
                                            type="password"
                                            variant="outlined"
                                            helper=""
                                    ></mdui-text-field>
                                </div>
                            </div>
                        </div>
                        <div class="col-md12 action-buttons">
                            <mdui-button id="save_oidc" icon="save" type="submit">
                                保存修改
                            </mdui-button>
                        </div>
                    </form>
                </mdui-tab-panel>
            </mdui-tabs>

        </div>
    </div>
</div>

<script id="script">
    window.pageLoadFiles = [
        'Form',
    ];

    window.pageOnLoad = function (loading) {

        $.form.manage("/login/sso","#form_oidc");

        $.form.submit("#form_pwd",function (data) {
            // 验证新密码和确认密码是否一致
            if (data.new_password && data.new_password  !== data.confirm_password ) {
                $.toaster.error('新密码和确认密码不一致');
                return;
            }

            $.request.postForm("/login/reset",data,function (ret) {
                if (ret.code !== 200){
                    $.toaster.error(ret.msg);
                }else{
                    $.toaster.success(ret.msg);
                    location.reload();
                }

            });

        });

        window.pageOnUnLoad = function () {
            // 页面卸载时的清理工作
        };

    };



</script>



