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
        $basic = Role::create(['name' => 'User']);

        /*  Acciones */
        $basic_permissions = Permission::where('name', 'like', 'mostrar%')->get();

        /* Asignamos los permisos a los roles */
        $admin->syncPermissions(Permission::all());
        $basic->syncPermissions($basic_permissions);

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

        $user3 = \App\Models\User::factory()->create([
            'name' => 'User Prueba',
            'email' => 'user@upeu.edu.pe',
        ]);
        $user3->assignRole($basic);

        /* Personas */
        Persona::create([
            'id' => $user3->id,
            'nombre' => 'User',
            'dni' => '12345678',
            'apellido_paterno' => 'Prueba',
            'apellido_materno' => 'Prueba',
            'fecha_nacimiento' => '2000-01-01',
            'genero' => '1',
            'tipo_persona' => '1',
            'area_id' => '1',
        ]);
    }
}
