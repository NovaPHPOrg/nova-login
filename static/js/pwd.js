window.pageLoadFiles = [
    'Form',
    'Request',
    'Toaster',
];

window.pageOnLoad = function () {
    $.form.submit("#form_pwd", {
        callback: function (data) {
            if (data.new_password && data.new_password !== data.confirm_password) {
                $.toaster.error('新密码和确认密码不一致');
                return false;
            }

            $.request.postForm("/login/pwd/save", data, function (ret) {
                if (ret.code !== 200) {
                    $.toaster.error(ret.msg);
                } else {
                    $.toaster.success(ret.msg);
                    if (ret.msg === '密码修改成功，请重新登录') {
                        setTimeout(() => {
                            location.href = '/login';
                        }, 1500);
                    } else {
                        setTimeout(() => {
                            location.reload();
                        }, 500);
                    }
                }
            });

            return false;
        }
    });

    $.request.get("/login/pwd/config", {}, function (ret) {
        if (ret.code !== 200) {
            $.toaster.error(ret.msg);
        } else {
            $.form.val("#form_pwd",ret.data)

        }
    });


    window.pageOnUnLoad = function () {
        // 页面卸载时的清理工作
    };

    return false;
};