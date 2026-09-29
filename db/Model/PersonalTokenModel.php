<?php

declare(strict_types=1);

namespace nova\plugin\login\db\Model;

use nova\plugin\login\db\Dao\UserDao;
use nova\plugin\orm\object\Model;

/** 个人访问 Token 模型，数据库不保存明文 Token。 */
class PersonalTokenModel extends Model
{
    public int $user_id = 0;
    public string $name = '';
    public string $token_hash = '';
    public string $token_prefix = '';
    public int $created_at = 0;
    public int $last_used_at = 0;
    public int $expires_at = 0;
    public int $revoked_at = 0;

    public function getUnique(): array
    {
        return ['token_hash'];
    }

    public function user(): ?UserModel
    {
        return UserDao::getInstance()->id($this->user_id);
    }
}
