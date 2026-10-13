window.pageLoadFiles = ['DataTable', 'DialogForm', 'Request', 'Toaster', 'Layer'];

window.pageOnLoad = function () {
    let currentToken = '';
    let table = new DataTable('#dataTable');
    let tokenDialog = document.querySelector('#tokenDialog');
    let newTokenDialog = document.querySelector('#newTokenDialog');

    function formatTime(timestamp) {
        return timestamp ? $.formatDateTime(new Date(timestamp * 1000)) : '从未';
    }

    table.load({
        uri: '/login/token/list',
        columns: [
            { field: 'name', name: '名称', width: '180px' },
            { field: 'token_prefix', name: 'Token前缀', formatter: function (value) { return $.escapeHtml(value || '') + '...'; } },
            { field: 'created_at', name: '创建时间', formatter: formatTime },
            { field: 'last_used_at', name: '最后使用', formatter: formatTime },
            { field: 'expires_at', name: '过期时间', formatter: function (value) { return value ? formatTime(value) : '永不过期'; } },
            { field: 'active', name: '状态', formatter: function (value) { return value ? '有效' : '已失效'; } },
            {
                field: 'active', name: '操作', align: 'center', width: '100px',
                formatter: function (value, row, index) {
                    return '<mdui-button-icon icon="delete" color="error" class="revoke-token" data-index="' + index + '" title="删除"></mdui-button-icon>';
                }
            }
        ],
        page: false,
        selectable: false,
        mobile: true,
        empty_msg: '暂无Token'
    });

    $('#addToken').on('click', function () {
        tokenDialog.open(true);
    });

    tokenDialog.submit('/login/token/create', function (data, response) {
        currentToken = response.data.token;
        $('#newToken').text(currentToken);
        newTokenDialog.open = true;
        table.reload({}, true);
    });

    $('#copyToken').on('click', function () {
        navigator.clipboard.writeText(currentToken).then(function () { $.toaster.success('Token已复制'); });
    });

    $('#closeTokenDialog').on('click', function () {
        newTokenDialog.open = false;
    });

    $('#refreshTable').on('click', function () {
        table.reload({}, true);
    });

    $('#dataTable').on('click', '.revoke-token', function () {
        let row = table.getRow($(this).data('index'));
        if (!row) return;
        let id = row.id;
        $.layer.confirm({
            msg: '删除后使用该Token的程序将立即无法访问，确定继续吗？',
            yes: function () {
                $.request.postForm('/login/token/revoke', {id: id}, function (ret) {
                    if (ret.code === 200) { $.toaster.success(ret.msg); table.reload({}, true); }
                    else { $.toaster.error(ret.msg); }
                });
            }
        });
    });

    window.pageOnUnLoad = function () { table.destroy(); };
    return false;
};





