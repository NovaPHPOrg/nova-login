window.pageLoadFiles = [
    'Form',
    'Request',
    'Toaster',
    'Loader',
];

window.pageOnLoad = function (loading) {
    const loginForm = document.getElementById('loginForm');
    const captchaElement = document.querySelector('nova-captcha');

    // 登录表单处理
    $.form.submit(loginForm, {
        callback: function (data) {
            if (!data.username?.trim()) {
                $.toaster.error('请输入账号');
                return false;
            }
            if (!data.password?.trim()) {
                $.toaster.error('请输入密码');
                return false;
            }

            captchaElement.show((captchaData) => {
                data.captcha = captchaData.captcha;

                loginForm.showLoading();

                $.request.postForm('/login', data, function (response) {
                    loginForm.closeLoading();
                    if (response.code === 200) {
                        $.toaster.success(response.msg);
                        setTimeout(() => {
                            location.href = response.data;
                        }, 500);
                    } else {
                        $.toaster.error(response.msg);
                    }
                }, function () {
                    loginForm.closeLoading();
                });
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

                if (!hitokotoContainer || !hitokotoElement) return;

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

    window.pageOnUnLoad = function () {
        // 页面卸载时的清理工作
    };

    return false;
};