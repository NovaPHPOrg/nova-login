<?php

declare(strict_types=1);

namespace nova\plugin\login\db\Model;

use nova\plugin\orm\object\Model;

class UserModel extends Model
{
    /**
     * Username for login
     */
    public string $username = '';

    /**
     * Hashed password
     */
    public string $password = '';

    /**
     * User's display name
     */
    public string $display_name = '';

    public string $avatar = '';

    /**
     * Get unique fields for this model
     */
    public function getUnique(): array
    {
        return ['username']; // Email is now the only unique identifier
    }

    /**
     * Get fields that should not be HTML escaped
     */
    public function getNoEscape(): array
    {
        return ['password'];
    }

}
