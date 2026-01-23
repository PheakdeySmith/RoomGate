<?php

namespace Modules\User\Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DemoUsersSeeder extends Seeder
{
    public function run(): void
    {
        $password = Hash::make('11111111');

        $users = [
            [
                'name' => 'Admin',
                'email' => 'admin@gmail.com',
                'platform_role' => 'platform_admin',
                'roles' => ['platform_admin'],
            ],
        ];

        foreach ($users as $entry) {
            $user = User::updateOrCreate(
                ['email' => $entry['email']],
                [
                    'name' => $entry['name'],
                    'password' => $password,
                    'platform_role' => $entry['platform_role'],
                    'status' => 'active',
                    'email_verified_at' => now(),
                    'remember_token' => Str::random(10),
                ]
            );

            $user->syncRoles($entry['roles']);
        }
    }
}
