<?php

declare(strict_types=1);

namespace nova\plugin\login\db\Model;

use nova\plugin\orm\object\Model;

class RoleModel extends Model
{
    public string $name = "";

    public array $permissions = [];
}
