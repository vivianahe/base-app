<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class PermissionsTable extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Role::updateOrCreate(
            ['name' => 'Administrador']
        );
        Role::updateOrCreate(
            ['name' => 'Controlador']
        );


        $permissions = [
            ['name' => 'event_management', 'name_es' => 'Gestión Eventos', 'guard_name' => 'web'],
            ['name' => 'manage_participants', 'name_es' => 'Gestión Participantes', 'guard_name' => 'web'],
            ['name' => 'user_management', 'name_es' => 'Gestión Usuarios', 'guard_name' => 'web'],
            ['name' => 'email_management', 'name_es' => 'Gestión Email', 'guard_name' => 'web'],
            ['name' => 'change_state', 'name_es' => 'Cambiar estado', 'guard_name' => 'web'],
        ];

        foreach ($permissions as $permissionData) {
            $permission = Permission::firstOrNew([
                'name' => $permissionData['name'],
                'name_es' => $permissionData['name_es'],
                'guard_name' => $permissionData['guard_name'],
            ]);

            if (!$permission->exists) {
                $permission->save();
            }
        }


        $roles = Role::all();
        foreach ($roles as $role) {
            if ($role->name == 'Administrador') {
                $role->syncPermissions(Permission::pluck('name'));
            }
        }
    }
}
