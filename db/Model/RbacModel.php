<?php
declare(strict_types=1);

namespace nova\plugin\login\db\Model;

use nova\plugin\login\Permissions;
use nova\plugin\orm\object\Model;

class RbacModel extends Model
{
    /**
     * Name of the role or permission (unique identifier)
     */
    public string $name = '';
    /**
     * Human-readable description
     */
    public string $description = '';
    /**
     *
     * @var array
     */
    public array $permissions = [];
    /**
     * Get unique fields for this model
     * 
     * @return array
     */
    public function getUnique(): array
    {
        return ['name'];
    }

    public function hasPermission(string $name): bool
    {
        // 1. Check if this role directly has the permission in its permissions array
        if (in_array($name, $this->permissions)) {
            return true;
        }
        
        // 2. Check if this role's name is the same as the requested permission
        if ($this->name === $name) {
            return true;
        }
        
        // 3. Check if this role's name is in the parent permissions of the requested permission
        $permissionsManager = Permissions::getInstance();
        $parentPermissions = $permissionsManager->getParent($name);
        if (in_array($this->name, $parentPermissions)) {
            return true;
        }
        
        // Permission not found in any check
        return false;
    }

}