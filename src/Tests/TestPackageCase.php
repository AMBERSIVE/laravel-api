<?php

namespace AMBERSIVE\Api\Tests;

use Tests\TestCase;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestPackageCase extends TestCase
{
    public $permissions = [];
    public $role = null;
    public $roles = [];
    
    /**
     * Generate a list of roles
     *
     * @param  mixed $roles
     * @return array
     */
    public function generateRoles(array $roles = []): array {

        $classRole       = config('ambersive-api.models.model_role', \AMBERSIVE\Api\Models\Role::class);

        $this->roles = [];

        foreach($roles as $index => $role){

            $roleItem = factory($classRole)->create([
                'name'  => $role
            ]);
            $roleItem->syncPermissions([]);
            $this->roles[] = $roleItem;

        }

        return $this->roles;

    }
       
    /**
     * Helper function to generate permission
     *
     * @param  mixed $prefix
     * @param  mixed $addtional
     * @return void
     */
    public function generatePermission(String $prefix, array $addtional = [], $roleName = null, array $users = []):array {

        $classPermission = config('ambersive-api.models.model_permission', \AMBERSIVE\Api\Models\Permission::class);
        $classRole       = config('ambersive-api.models.model_role', \AMBERSIVE\Api\Models\Role::class);

        $permissions    = array_merge(['-all', '-index', '-show', '-update', '-store', '-destroy'], $addtional);        
        $permissionList = [];
        foreach($permissions as $key => $permission){
            $permission = factory($classPermission)->create([
                'name'  => $prefix.$permission,
                'guard_name' => 'api'
            ]);
            $permissionList[] = $permission;
        }

        $permissions = array_merge($this->permissions, $permissionList);
        $permissions = collect($permissions)->map(function($permission){
            if (is_object($permission) == true &&  isset($permission->name)){
                return $permission->name;
            }
            return $permission;
        })->toArray();

        $this->permissions = $permissions;

        // Create a role and assign all permissions to it
        if ($roleName !== null) {
            
            $role = factory($classRole)->create([
                'name'  => $roleName
            ]);
            $role->syncPermissions($role);
            $this->role = $role;

        }

        // Assign the permissions to the given list of users
        if (!empty($users)) {
            foreach($users as $user){
                $user->syncPermissions($this->permissions);
            }
        }

        return $permissionList;
        
    }

    /**
     * Execute the default test for models
     */
    public function executeModelDefaultTest($modelClass = null){

        $this->assertNotNull($modelClass);

        // TODO: Write useful test

    }

}
