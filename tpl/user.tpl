<title id="title">用户管理 - {$title}</title>
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
            <mdui-icon name="people" class="refresh mr-2"></mdui-icon>
            <span>用户管理</span>
            <div style="flex-grow: 1"></div>
            <mdui-button icon="add" id="addUser">创建用户</mdui-button>
        </div>
        <div class="col-xs12">
            <div id="dataTable" class="table-card mt-2 w-100"></div>
        </div>
    </div>

    <mdui-dialog-form label="用户编辑" id="userDialog">
        <form class="row col-space16">
            <mdui-text-field name="id" type="hidden"></mdui-text-field>
            <div class="col-md12">
                <mdui-text-field label="用户名" name="username" variant="outlined" required></mdui-text-field>
            </div>
            <div class="col-md12">
                <mdui-text-field label="显示名称" name="display_name" variant="outlined" required></mdui-text-field>
            </div>
            <div class="col-md12">
                <mdui-select label="角色" name="role" variant="outlined" required>
                    <mdui-menu id="roleMenu"></mdui-menu>
                </mdui-select>
            </div>
            <div class="col-md12">
                <mdui-text-field label="密码" name="password" type="password" variant="outlined" helper="留空则保持原密码"></mdui-text-field>
            </div>
        </form>
    </mdui-dialog-form>
</div>

<script id="script" src="/login/static/js/user.js?v={$__v}"></script>