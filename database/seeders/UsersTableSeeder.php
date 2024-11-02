<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UsersTableSeeder extends Seeder
{
    public function run()
    {
        $password = Hash::make('Admin-1234');
        $inputs = [
            ['name' => 'dev aunna',
             'email' => 'dev@aunnait.es',
             'email_verified_at' => \Carbon\Carbon::now(),
             'password' => $password,
            ]
        ];

        foreach ($inputs as $index => $input) {
            // Buscar un usuario con el mismo correo electrónico o crear uno nuevo
            User::updateOrCreate(['email' => $input['email']], $input);

            $user = User::where('email', $input['email'])->first();

            switch ($index) {
                case 0:
                    $user->assignRole('Administrador');
                    break;
            }
        }
    }
}
