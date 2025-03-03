<?php
declare(strict_types=1);

namespace nova\plugin\login\db\Model;

use nova\plugin\login\db\Dao\RbacDao;
use nova\plugin\login\db\Dao\UserDao;
use nova\plugin\orm\object\Model;

class UserModel extends Model
{
    /**
     * User's unique identifier
     */
    public int $id = 0;
    
    /**
     * Username for login (deprecated, use email instead)
     * @deprecated
     */
    public string $username = '';
    
    /**
     * Hashed password
     */
    public string $password = '';
    
    /**
     * User's email address (used as login identifier)
     */
    public string $email = '';
    
    /**
     * User's display name
     */
    public string $display_name = '';


    public string $avatar = '';
    
    /**
     * User's status (active, inactive, banned)
     */
    public string $status = 'active';
    
    /**
     * User's assigned roles
     * Format: ['role_id1', 'role_id2', ...]
     */
    public array $roles = [];
    
    /**
     * Get unique fields for this model
     */
    public function getUnique(): array
    {
        return ['email']; // Email is now the only unique identifier
    }
    
    /**
     * Get fields that should not be HTML escaped
     */
    public function getNoEscape(): array
    {
        return ['password', 'roles'];
    }
    
    /**
     * Check if the user account is active
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Check if the user has a specific role
     */
    public function hasRole(string $roleId): bool
    {
        return in_array($roleId, $this->roles);
    }



    public function hasPermission(string $permissionName): bool
    {
        if (empty($this->roles)) {
            return false;
        }
        
        $rbacDao = RbacDao::getInstance();
        
        // Check each role individually
        foreach ($this->roles as $roleId) {
            $role = $rbacDao->name($roleId);
            if ($role && $role->hasPermission($permissionName)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Assign a role to the user
     */
    public function assignRole(string $roleId): bool
    {
        if (!$this->hasRole($roleId)) {
            $this->roles[] = $roleId;
            UserDao::getInstance()->updateModel($this);
            return true;
        }
        return false;
    }
    
    /**
     * Remove a role from the user
     */
    public function removeRole(string $roleId): bool
    {
        $key = array_search($roleId, $this->roles);
        if ($key !== false) {
            unset($this->roles[$key]);
            $this->roles = array_values($this->roles); // Reindex array
            UserDao::getInstance()->updateModel($this);
            return true;
        }
        return false;
    }

    /**
     * Authenticate user with email and password
     * 
     * @param string $email User's email
     * @param string $password Plain text password to verify
     * @return bool True if authentication successful
     */
    public function authenticate(string $email, string $password): bool
    {
        return $this->email === $email && password_verify($password, $this->password);
    }
}