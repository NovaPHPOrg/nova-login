<title id="title">个人Token - {$title}</title>
<style id="style">
    .table-card {
        box-sizing: border-box;
    }

    .token-value {
        display: block;
        padding: 1rem;
        word-break: break-all;
        font-family: monospace;
        background: var(--mdui-color-surface-container);
        border-radius: var(--mdui-shape-corner-small);
    }
</style>

<div id="container" class="container p-4">
    <div class="row col-space16">
        <div class="col-xs12 title-large center-vertical mb-4">
            <mdui-icon name="key" class="refresh mr-2"></mdui-icon>
            <span>个人Token</span>
            <div style="flex-grow: 1"></div>
            <mdui-button icon="add" id="addToken">创建</mdui-button>
            <mdui-button-icon icon="refresh" id="refreshTable" variant="outlined" class="ml-2"></mdui-button-icon>
        </div>
        <div class="col-xs12">
            <div id="dataTable" class="table-card mt-2 w-100"></div>
        </div>
    </div>

    <mdui-dialog-form label="创建Token" saveName="创建" id="tokenDialog">
        <form id="tokenForm" class="row col-space16">
            <div class="col-md12">
                <mdui-text-field label="Token名称" name="name" variant="outlined" required helper="例如：部署脚本、API客户端"></mdui-text-field>
            </div>
            <div class="col-md12">
                <mdui-text-field label="有效期（天）" name="expires_days" type="number" value="0" min="0" max="3650" variant="outlined" helper="0表示永不过期"></mdui-text-field>
            </div>
        </form>
    </mdui-dialog-form>

    <mdui-dialog id="newTokenDialog" close-on-esc close-on-overlay-click>
        <mdui-top-app-bar slot="header">
            <mdui-top-app-bar-title>Token创建成功</mdui-top-app-bar-title>
            <mdui-button-icon icon="close" id="closeTokenDialog"></mdui-button-icon>
        </mdui-top-app-bar>
        <div>
            <p>请立即复制并妥善保存，关闭此窗口后无法再次查看完整Token。</p>
            <div id="newToken" class="token-value"></div>
        </div>
        <mdui-button slot="action" id="copyToken" icon="content_copy">复制Token</mdui-button>
    </mdui-dialog>
</div>

<script id="script" src="/login/static/js/token.js?v={$__v}"></script>


