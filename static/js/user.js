window.pageLoadFiles = [
    'DataTable',
    'Form',
    'DialogForm',
    'Request',
    'Toaster',
    'Loader',
];

window.pageOnLoad = function (loading) {
    function initTable() {
        let table = new DataTable("#dataTable");
        table.load({
            uri: "/login/user/list",
            columns: [
                {
                    field: "id",
                    name: "ID",
                    align: "center",
                    width: 80,
                },
                {
                    field: "username",
                    name: "用户名",
                    align: "center",
                },
                {
                    field: "display_name",
                    name: "显示名称",
                    align: "center",
                },
                {
                    field: "role_data.name",
                    name: "角色",
                    align: "center",
                },
                {
                    field: "id",
                    name: "操作",
                    align: "center",
                    width: 280,
                    fixed: "right",
                    formatter: function (value, row, index) {
                        return `
<mdui-button-icon data-index="${index}" icon="edit" class="action-editor" title="编辑"></mdui-button-icon>
<mdui-button-icon data-index="${index}" icon="delete" class="action-delete" title="删除" color="error"></mdui-button-icon>
`;
                    },
                },
            ],
            mobile: true,
            lineHeight: "auto",
            height: "auto",
            events: {
                onRowClick: function (row, rowIndex) {
                },
                onCellClick: function (row, rowIndex, colIndex, colName) {
                },
                onPaged: function (page) {
                },
            },
            empty_msg: "无数据",
            page: true,
            selectable: false
        });
        return table;
    }

    let table = initTable();
    let dialog = document.querySelector("#userDialog");

    // 用户编辑
    $("#dataTable").on("click", ".action-editor", function () {
        let row = table.getRow($(this).data("index"));
        if (!row) {
            $.toaster.error("无法获取用户信息");
            return;
        }
        $.request.get("/login/user/" + row.id, function (data) {
            dialog.setValue(data);
            dialog.open();
        });
    });

    // 用户删除
    $("#dataTable").on("click", ".action-delete", function () {
        let row = table.getRow($(this).data("index"));
        if (!row) {
            $.toaster.error("无法获取用户信息");
            return;
        }
        if (row.id === 1) {
            $.toaster.error("不能删除默认管理员");
            return;
        }
        $.dialog.confirm("确定要删除该用户吗？", function () {
            $.request.postForm("/login/user/remove", { id: row.id }, function (data) {
                if (data.code === 200) {
                    $.toaster.success(data.msg);
                    table.reload({}, true);
                } else {
                    $.toaster.error(data.msg);
                }
            });
        });
    });

    // 新建用户
    $("#addUser").on("click", function () {
        dialog.setValue({});
        dialog.open(true);
    });

    // 保存用户
    dialog.submit("/login/user/update", function (data, response) {
        table.reload({}, true);
    });

    window.pageOnUnLoad = function () {
        table.destroy();
    };

    return false;
};