<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $role_id = DB::table('roles')->insertGetId([
            'name' => 'Database Administrator',
        ]);

        $module_id = DB::table('modules')->insertGetId([
            'name' => 'Schema Designing',
            'description' => 'Designing phase'
        ]);

        $permission_id = DB::table('permissions')->insertGetId([
            'name' => 'User.update',
            'module_id' => $module_id,
            'description' => 'Update permission for user'
        ]);

        DB::table('rolepermissions')->insert([
            'role_id' => $role_id,
            'permission_id' => $permission_id,
            'module_id' => $module_id,
        ]);

        $user_id = DB::table('users')->insertGetId([
            'first_name' => Str::random(8),
            'last_name' => Str::random(5),
            'email' => Str::random(10).'@gmail.com',
            'country_code' => '+1',
            'mobile_number' => '9119992999',
            'password' => Str::random(15),
            'status' => 1,
            'lang' => 'en',
            'created_by' => 12345678,
            'updated_by' => 23456789
        ]);

        DB::table('userroles')->insertGetId([
            'user_id' => $user_id,
            'role_id' => $role_id,
        ]);
    }
}
