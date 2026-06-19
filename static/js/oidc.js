window.pageLoadFiles = [
    'Form',
    'Request',
    'Toaster',
];

window.pageOnLoad = function () {
    $.form.manage("/login/oidc/config", "#form_oidc");

    window.pageOnUnLoad = function () {
        // 页面卸载时的清理工作
    };

    return false;
};