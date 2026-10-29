<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Admin
        User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@traceverte.test',
            'role' => 'admin',
        ]);

        // Producer
        User::factory()->create([
            'name' => 'Producer User',
            'email' => 'producer@traceverte.test',
            'role' => 'producer',
        ]);

        // Transformer
        User::factory()->create([
            'name' => 'Transformer User',
            'email' => 'transformer@traceverte.test',
            'role' => 'transformer',
        ]);

        // Distributor
        User::factory()->create([
            'name' => 'Distributor User',
            'email' => 'distributor@traceverte.test',
            'role' => 'distributor',
        ]);

        // Consumer
        User::factory()->create([
            'name' => 'Consumer User',
            'email' => 'consumer@traceverte.test',
            'role' => 'consumer',
        ]);

        // Random users
        User::factory(45)->create();
    }
}
