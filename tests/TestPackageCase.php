<?php

namespace AMBERSIVE\Tests;

use Config;
use File;

use AMBERSIVE\Tests\TestCase;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestPackageCase extends TestCase
{

    public $permissions = [];
    public $role = null;
    public $roles = [];

    public array  $createPermissions = [];
    public String $createRoleName = "";
    public String $createEndpointName = "";

    protected function setUp(): void
    {
        parent::setUp();

        Config::set('mail.default', 'log');

        Config::set('permission.table_names', [
            'roles'                 => 'roles',
            'permissions'           => 'permissions',
            'model_has_permissions' => 'model_has_permissions',
            'model_has_roles'       => 'model_has_roles',
            'role_has_permissions'  => 'role_has_permissions'
        ]);

        Config::set('permission.column_names', [
            'model_morph_key' => 'model_uuid'
        ]);

        Config::set('auth.providers.users.model', \AMBERSIVE\Api\Models\User::class);
        Config::set('auth:defaults.guard.user', 'api');
        Config::set('auth.guards.api.driver', 'jwt');

        Config::set('ambersive-api.models.user_model', \AMBERSIVE\Api\Models\User::class);

        Config::set('permission.models.permission', \AMBERSIVE\Api\Models\Permission::class);
        Config::set('permission.models.role', \AMBERSIVE\Api\Models\Role::class);
        
        $this->loadMigrationsFrom(__DIR__ . '/../src/Migrations');

        $this->artisan('migrate', [
            '--path' => realpath(__DIR__ . '/../src/Migrations'),
            '--realpath' => false,
            '--database' => 'sqlite',
        ]);

        shell_exec('php artisan api:update --silent');

    }
    
    protected function tearDown(): void 
    {
        parent::setUp();

        if (File::exists(__DIR__.'/../Http/')) {
            File::deleteDirectory(__DIR__.'/../Http/');
        }

        if (File::exists(__DIR__.'/../Test/')) {
            File::deleteDirectory(__DIR__.'/../Test/');
        }

    }

    /**
     * Helper method to prepare controller testings
     *
     * @return void
     */
    public function prepareControllerTesting():void {
        if ($this->createUsers === true) {

            $userClass = config('ambersive-api.models.model_user', \AMBERSIVE\Api\Models\User::class);

            $this->createdUsers['allRights'] = factory($userClass)->create([
                'username'          => 'Default',
                'email'             => 'test@test.com',
                'email_verified_at' => now(),
                'password'          => bcrypt("testtest"),
                'active'            => true,
                'locked'            => false
            ]);

            $this->createdUsers['noRights'] = factory($userClass)->create([
                'username'          => 'Default',
                'email'             => 'test@test2.com',
                'email_verified_at' => now(),
                'password'          => bcrypt("testtest"),
                'active'            => true,
                'locked'            => false
            ]);

            $permissions = $this->generatePermission($this->createEndpointName, $this->createPermissions, $this->createRoleName, [$this->createdUsers['allRights']]);

        }
    }
    
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
                'name'  => $roleName,
                'guard_name' => 'api'
            ]);
            $this->role = $role;

        }

        // Assign the permissions to the given list of users
        if (!empty($users)) {
            foreach($users as $user){
                $user->syncPermissions($permissionList);
            }
        }

        return $permissionList;
        
    }

    /**
     * Execute the default test for models
     */
    public function executeModelDefaultTest($modelClass = null, Callable $callback){

        $this->assertNotNull($modelClass);

        if ($callback){
            $callback($this, $modelClass);
        }

    }

}
