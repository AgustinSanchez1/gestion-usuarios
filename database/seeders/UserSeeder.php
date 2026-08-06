<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $password = env('ADMIN_SEED_PASSWORD');

        if (blank($password)) {
            $password = Str::password(20);

            $this->command?->warn('==============================================');
            $this->command?->info('  ADMIN_SEED_PASSWORD no definida en .env');
            $this->command?->info('  Se generó una contraseña aleatoria: '.$password);
            $this->command?->warn('  Guárdala en un lugar seguro. NO se muestra de nuevo.');
            $this->command?->warn('==============================================');
        }

        $user = User::updateOrCreate(
            ['name_user' => 'admin'],
            [
                'name'     => 'Administrador',
                'email'    => 'admin@example.com',
                'password' => Hash::make($password),
            ]
        );

        $user->assignRole('admin');

        $this->command?->info("Usuario admin listo (name_user: admin, email: admin@example.com).");
    }
}