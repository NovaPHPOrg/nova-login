window.pageLoadFiles = [
    'DataTable',
    'Form',
    'DialogForm',
    'Request',
    'Toaster',
    'Loader',
    'Layer'
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
                    field: "role_name",
                    name: "角色",
                    align: "center",
                    formatter: function (value, row, index) {
                        if (!value) {
                            return '<span class="badge badge-error">无角色</span>';
                        }
                        return '<span class="badge badge-primary">' + value + '</span>';
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
    let dialog = document.querySelector("#userDialog");
    let usernameField = document.querySelector("#usernameField");

    // 筛选输入框联动
    let searchTimeout = null;
    const searchInput = document.querySelector("#searchInput");
    searchInput.addEventListener("input", function (e) {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(function () {
            table.reload({ search: e.target.value }, true);
        }, 500);
    });

    // 初始化时设置表格参数为当前搜索值
    const initialSearch = searchInput.value;
    if (initialSearch) {
        table.reload({ search: initialSearch }, true);
    }

    // 刷新表格
    document.querySelector("#refreshTable").addEventListener("click", function () {
        table.reload({}, true);
    });

    // 用户编辑
    $("#dataTable").on("click", ".action-editor", function () {
        let row = table.getRow($(this).data("index"));
        if (!row) {
            $.toaster.error("无法获取用户信息");
            return;
        }
        row.password = "";
        dialog.setValue(row);

        // 编辑模式：用户名不可更改
        if (row.id) {
            usernameField.setAttribute("disabled", "true");
            usernameField.setAttribute("readonly", "true");
        } else {
            usernameField.removeAttribute("disabled");
            usernameField.removeAttribute("readonly");
        }

        dialog.open();
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
        $.layer.confirm({
            msg: "确定要删除该用户吗？",
            yes: function () {
                $.request.postForm("/login/user/remove", { id: row.id }, function (data) {
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

    // 新建用户
    document.querySelector("#addUser").addEventListener("click", function () {
        dialog.setValue({});
        usernameField.removeAttribute("disabled");
        usernameField.removeAttribute("readonly");
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