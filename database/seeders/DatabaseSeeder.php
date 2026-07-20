<?php

namespace Database\Seeders;

use App\Domain\Constants\UserRoles;
use App\Domain\Entities\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::factory()->create([
            'first_name' => 'Admin',
            'last_name' => 'User',
            'email' => 'admin@example.com',
            'role' => UserRoles::ADMIN,
        ]);
    }
}
