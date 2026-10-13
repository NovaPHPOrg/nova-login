<?php

declare(strict_types=1);

namespace nova\plugin\login\controller;

use nova\framework\http\Response;
use nova\plugin\login\db\Dao\PersonalTokenDao;
use nova\plugin\login\db\Model\PersonalTokenModel;

/** 当前用户的个人 Token 管理接口。 */
class Token extends BaseAPIController
{
    public function list(): Response
    {
        $rows = [];
        foreach (PersonalTokenDao::getInstance()->listByUserId($this->userModel->id) as $token) {
            if (!$token instanceof PersonalTokenModel) {
                continue;
            }
            $rows[] = [
                'id' => $token->id,
                'name' => $token->name,
                'token_prefix' => $token->token_prefix,
                'created_at' => $token->created_at,
                'last_used_at' => $token->last_used_at,
                'expires_at' => $token->expires_at,
                'revoked_at' => $token->revoked_at,
                'active' => $token->revoked_at === 0 && ($token->expires_at === 0 || $token->expires_at > time()),
            ];
        }
        return Response::asJson(['code' => 200, 'data' => $rows, 'count' => count($rows)]);
    }

    public function create(): Response
    {
        $name = trim((string)$this->request->post('name', ''));
        if ($name === '' || mb_strlen($name) > 64) {
            return Response::asJson(['code' => 400, 'msg' => 'Token名称不能为空且不能超过64个字符']);
        }

        $days = (int)$this->request->post('expires_days', 0);
        if ($days < 0 || $days > 3650) {
            return Response::asJson(['code' => 400, 'msg' => '有效期必须是0到3650天，0表示永不过期']);
        }

        $plainToken = bin2hex(random_bytes(32));
        $token = new PersonalTokenModel();
        $token->user_id = $this->userModel->id;
        $token->name = $name;
        $token->token_hash = hash('sha256', $plainToken);
        $token->token_prefix = substr($plainToken, 0, 12);
        $token->created_at = time();
        $token->expires_at = $days === 0 ? 0 : time() + $days * 86400;
        $token->id = PersonalTokenDao::getInstance()->insertModel($token);

        return Response::asJson([
            'code' => 200,
            'msg' => 'Token创建成功，请立即复制保存，之后将无法再次查看完整Token',
            'data' => ['token' => $plainToken, 'name' => $token->name],
        ]);
    }

    public function revoke(): Response
    {
        $id = (int)$this->request->post('id', 0);
        $token = PersonalTokenDao::getInstance()->id($id);
        if (!$token instanceof PersonalTokenModel || $token->user_id !== $this->userModel->id) {
            return Response::asJson(['code' => 404, 'msg' => 'Token不存在']);
        }
        PersonalTokenDao::getInstance()->deleteToken($token);
        return Response::asJson(['code' => 200, 'msg' => 'Token已删除']);
    }
}
