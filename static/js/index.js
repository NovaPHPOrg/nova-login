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

            $.request.postForm("/login", data,
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
window.mainAppLoading.close();