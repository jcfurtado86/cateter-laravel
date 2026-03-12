<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::create([
            'name' => 'Administrador',
            'email' => 'admin@cateter.com',
            'password_hash' => Hash::make('admin123'),
            'role' => 'ADMIN',
            'active' => true,
        ]);

        User::create([
            'name' => 'Dr. Médico',
            'email' => 'medico@cateter.com',
            'password_hash' => Hash::make('medico123'),
            'role' => 'DOCTOR',
            'active' => true,
        ]);

        $this->call(PatientSeeder::class);
    }
}
