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
            uri: "/login/role/list",
            columns: [
                {
                    field: "id",
                    name: "ID",
                    align: "center",
                    width: 80,
                },
                {
                    field: "name",
                    name: "角色名称",
                    align: "center",
                },
                {
                    field: "permissions",
                    name: "权限列表",
                    align: "center",
                    formatter: function (value, row, index) {
                        return value && value.length ? value.join(", ") : "无权限";
                    },
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
    let dialog = document.querySelector("#roleDialog");

    // 角色编辑
    $("#dataTable").on("click", ".action-editor", function () {
        let row = table.getRow($(this).data("index"));
        if (!row) {
            $.toaster.error("无法获取角色信息");
            return;
        }
        $.request.get("/login/role/" + row.id, function (data) {
            dialog.setValue(data);
            dialog.open();
        });
    });

    // 角色删除
    $("#dataTable").on("click", ".action-delete", function () {
        let row = table.getRow($(this).data("index"));
        if (!row) {
            $.toaster.error("无法获取角色信息");
            return;
        }
        $.dialog.confirm("确定要删除该角色吗？删除后使用该角色的用户将失去权限。", function () {
            $.request.postForm("/login/role/remove", { id: row.id }, function (data) {
                if (data.code === 200) {
                    $.toaster.success(data.msg);
                    table.reload({}, true);
                } else {
                    $.toaster.error(data.msg);
                }
            });
        });
    });

    // 新建角色
    $("#addRole").on("click", function () {
        dialog.setValue({});
        dialog.open(true);
    });

    // 保存角色
    dialog.submit("/login/role/update", function (data, response) {
        table.reload({}, true);
    });

    window.pageOnUnLoad = function () {
        table.destroy();
    };

    return false;
};