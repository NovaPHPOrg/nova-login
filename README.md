# nova-login

## 个人 Token

登录后打开“登录管理 → 个人Token”，创建 Token。完整 Token 只在创建成功时返回一次，数据库只保存其 SHA-256 哈希。

调用受保护 API 时使用标准 Bearer 请求头：

```http
Authorization: Bearer <your-token>
```

Token 默认继承用户当前角色权限，支持设置有效期和主动撤销。修改密码或删除用户时，该用户的全部个人 Token 会自动失效。请只通过 HTTPS 传输 Token，并避免将完整 Token 写入日志或提交到代码仓库。
