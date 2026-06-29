window.pageLoadFiles = [
    'DataTable',
    'Toaster',
    'Request',
    'Layer',
];

window.pageOnLoad = function () {
    let table = new DataTable("#dataTable");
    table.load({
        uri: "/login/user/records",
        columns: [
            {
                field: "time",
                name: "登录时间",
                align: "center",
                width: "120px",
                formatter: function (value) {
                    return $.formatDateTime(new Date(value * 1000));
                },
            },
            {
                field: "ip",
                name: "IP地址",
                align: "center",
                width: "120px",
            },
            {
                field: "device",
                name: "设备",
                align: "center",
                width: "250px",
            },
            {
                field: "is_current",
                name: "操作",
                align: "center",
                width: 'auto',
                formatter: function (value, row, index) {
                    if (value) {
                        return '<span class="badge badge-primary">当前会话</span>';
                    }
                    return '<mdui-button-icon data-index="' + index + '" icon="logout" class="action-kick" title="踢下线"></mdui-button-icon>';
                },
            },
        ],
        mobile: true,
        lineHeight: "auto",
        height: "auto",
        empty_msg: "暂无登录记录",
        page: true,
        selectable: false,
    });

    $("#dataTable").on("click", ".action-kick", function () {
        let row = table.getRow($(this).data("index"));
        if (!row) return;
        $.layer.confirm({
            msg: "确定要将该设备踢下线吗？",
            yes: function () {
                $.request.postForm("/login/user/kick", { id: row.id }, function (data) {
                    if (data.code === 200) {
                        $.toaster.success(data.msg);
                        table.reload({}, true);
                    } else {
                        $.toaster.error(data.msg);
                    }
                });
            }
        });
    });

    document.querySelector("#refreshTable").addEventListener("click", function () {
        table.reload({}, true);
    });

    window.pageOnUnLoad = function () {
        table.destroy();
    };

    return false;
};
