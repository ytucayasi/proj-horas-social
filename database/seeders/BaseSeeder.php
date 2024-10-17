<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Persona;
use App\Models\Role;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class BaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        /* Registramos los permisos */
        foreach (config('admin.modules') as $module) {
            foreach (config('admin.actions') as $action) {
                Permission::create(['name' => $action['ab'] . ' ' . $module['ab']]);
            }
        }

        /* Permission::create(['name' => 'dashboard']);
        Permission::create(['name' => 'info']); */

        /* Registramos los roles */
        $super_admin = Role::create(['name' => 'Super Admin']);
        $admin = Role::create(['name' => 'Admin']);

        /* Asignamos los permisos a los roles */
        $admin->syncPermissions(Permission::all());

        /* Creamos los usuarios */
        $user1 = \App\Models\User::factory()->create([
            'name' => 'Super Admin',
            'email' => 'superadmin@upeu.edu.pe',
        ]);
        $user1->assignRole($super_admin);

        $user2 = \App\Models\User::factory()->create([
            'name' => 'Admin',
            'email' => 'admin@upeu.edu.pe',
        ]);
        $user2->assignRole($admin);
    }
}
