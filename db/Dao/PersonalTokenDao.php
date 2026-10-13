<?php

declare(strict_types=1);

namespace nova\plugin\login\db\Dao;

use nova\plugin\login\db\Model\PersonalTokenModel;
use nova\plugin\orm\object\Dao;

/** 个人 Token 数据访问对象。 */
class PersonalTokenDao extends Dao
{
    public function hash(string $hash): ?PersonalTokenModel
    {
        return $this->find(null, ['token_hash' => $hash]);
    }

    public function id(int $id): ?PersonalTokenModel
    {
        return $this->find(null, ['id' => $id]);
    }

    public function listByUserId(int $userId): array
    {
        return $this->select()
            ->where(['user_id' => $userId, 'revoked_at' => 0])
            ->orderBy('id', 'DESC')
            ->commit();
    }

    public function revokeByUserId(int $userId): void
    {
        $this->delete()->where(['user_id' => $userId])->commit();
    }

    public function deleteToken(PersonalTokenModel $token): void
    {
        $this->delete()->where([
            'id' => $token->id,
            'user_id' => $token->user_id,
        ])->commit();
    }
}
