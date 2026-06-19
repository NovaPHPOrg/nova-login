<title id="title">角色管理 - {$title}</title>
<style id="style">
    .table-card {
        box-sizing: border-box;
    }

    mdui-card {
        width: 100%;
    }
</style>

<div id="container" class="container">
    <div class="row col-space16">
        <div class="col-xs12 title-large center-vertical mb-4">
            <mdui-icon name="security" class="refresh mr-2"></mdui-icon>
            <span>角色管理</span>
            <div style="flex-grow: 1"></div>
            <mdui-button icon="add" id="addRole">创建角色</mdui-button>
        </div>
        <div class="col-xs12">
            <div id="dataTable" class="table-card mt-2 w-100"></div>
        </div>
    </div>

    <mdui-dialog-form label="角色编辑" id="roleDialog">
        <form class="row col-space16">
            <mdui-text-field name="id" type="hidden"></mdui-text-field>
            <div class="col-md12">
                <mdui-text-field label="角色名称" name="name" variant="outlined" required></mdui-text-field>
            </div>
            <div class="col-md12">
                <mdui-text-field
                    label="权限列表"
                    name="permissions"
                    variant="outlined"
                    helper="多个权限用英文逗号分隔，例如: user_manage,read_notify"
                ></mdui-text-field>
            </div>
        </form>
    </mdui-dialog-form>
</div>

<script id="script" src="/login/static/js/role.js?v={$__v}"></script>