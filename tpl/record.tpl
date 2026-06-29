<title id="title">登录记录 - {$title}</title>
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
            <mdui-icon name="history" class="refresh mr-2"></mdui-icon>
            <span>登录记录</span>
            <div style="flex-grow: 1"></div>
            <mdui-button-icon icon="refresh" id="refreshTable" variant="outlined"></mdui-button-icon>
        </div>
        <div class="col-xs12">
            <div id="dataTable" class="table-card w-100"></div>
        </div>
    </div>
</div>

<script id="script" src="/login/static/js/record.js?v={$__v}"></script>