<?php

declare(strict_types=1);

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
        User::factory()->admin()->create([
            'username' => 'admin',
            'email' => config('seed.admin.email'),
            'password' => config('seed.admin.password'),
        ]);

        User::factory()->moderator()->create([
            'username' => 'moderador',
            'email' => config('seed.moderator.email'),
            'password' => config('seed.moderator.password'),
        ]);

        User::factory(20)->create();
    }
}
