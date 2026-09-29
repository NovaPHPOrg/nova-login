<?php

declare(strict_types=1);

namespace nova\plugin\login;

use nova\framework\core\Context;
use nova\framework\core\Logger;
use nova\plugin\login\db\Dao\PersonalTokenDao;
use nova\plugin\login\db\Model\PersonalTokenModel;
use nova\plugin\login\db\Model\UserModel;

/** 负责 Bearer Token 的提取、校验和使用时间更新。 */
class TokenManager
{
    public function tokenFromRequest(): ?string
    {
        $request = Context::instance()->request();
        $header = $request->getHeaderValue('Authorization');
        if (!is_string($header)) {
            foreach ($request->getHeaders() as $name => $value) {
                if (strcasecmp((string)$name, 'Authorization') === 0) {
                    $header = $value;
                    break;
                }
            }
        }
        if (!is_string($header) || !preg_match('/^Bearer\s+([^\s]+)$/i', trim($header), $matches)) {
            return null;
        }
        return $matches[1];
    }

    public function hasBearerToken(): bool
    {
        return $this->tokenFromRequest() !== null;
    }

    public function authenticate(): ?UserModel
    {
        $plainToken = $this->tokenFromRequest();
        if ($plainToken === null || !preg_match('/^[a-f0-9]{64}$/i', $plainToken)) {
            return null;
        }

        $token = PersonalTokenDao::getInstance()->hash(hash('sha256', strtolower($plainToken)));
        if (!$token instanceof PersonalTokenModel || $token->revoked_at > 0 ||
            ($token->expires_at > 0 && $token->expires_at <= time())) {
            return null;
        }

        $user = $token->user();
        if (!$user instanceof UserModel) {
            return null;
        }

        PersonalTokenDao::getInstance()->update()
            ->set(['last_used_at' => time()])
            ->where(['id' => $token->id])
            ->commit();
        Logger::debug('Personal token authentication successful', ['userId' => $user->id, 'tokenId' => $token->id]);
        return $user;
    }
}

