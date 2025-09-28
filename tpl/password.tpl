<title id="title">密码安全 - {$title}</title>
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
            <mdui-icon name="password" class="refresh mr-2"></mdui-icon>
            <span>密码安全</span>
        </div>

        <div class="col-xs-12">
            <form class="row col-space16" id="form_pwd">
                <div class="col-xs-12">
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

                <div class="col-xs-12">
                    <mdui-text-field
                            label="当前密码"
                            name="current_password"
                            type="password"
                            variant="outlined"
                            required
                            helper="请输入您的当前密码以验证身份"
                    ></mdui-text-field>
                </div>

                <div class="col-xs-12 col-md-6">
                    <mdui-text-field
                            label="新密码"
                            name="new_password"
                            type="password"
                            variant="outlined"
                            helper="如需要修改密码，请输入新的密码"
                    ></mdui-text-field>
                </div>

                <div class="col-xs-12 col-md-6">
                    <mdui-text-field
                            label="确认新密码"
                            name="confirm_password"
                            type="password"
                            variant="outlined"
                            helper="请再次输入新密码以确保输入正确"
                    ></mdui-text-field>
                </div>

                <div class="col-xs-12 action-buttons">
                    <mdui-button id="save" icon="save" type="submit">
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
        $.form.submit("#form_pwd",{
            callback: function (data) {
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
            }
        });

        window.pageOnUnLoad = function () {
            // 页面卸载时的清理工作
        };
    };
</script> 