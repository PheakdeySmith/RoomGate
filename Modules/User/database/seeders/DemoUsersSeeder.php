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
        $password = Hash::make('password');

        $users = [
            [
                'name' => 'Platform Admin',
                'email' => 'platform.admin@roomgate.test',
                'platform_role' => 'platform_admin',
                'roles' => ['platform_admin'],
            ],
            [
                'name' => 'Support User',
                'email' => 'support@roomgate.test',
                'platform_role' => 'support',
                'roles' => ['support'],
            ],
            [
                'name' => 'Billing Admin',
                'email' => 'billing.admin@roomgate.test',
                'platform_role' => 'billing_admin',
                'roles' => ['billing_admin'],
            ],
            [
                'name' => 'Tenant Owner',
                'email' => 'owner@roomgate.test',
                'platform_role' => 'none',
                'roles' => ['owner'],
            ],
            [
                'name' => 'Tenant Admin',
                'email' => 'tenant.admin@roomgate.test',
                'platform_role' => 'none',
                'roles' => ['admin'],
            ],
            [
                'name' => 'Tenant Staff',
                'email' => 'staff@roomgate.test',
                'platform_role' => 'none',
                'roles' => ['staff'],
            ],
            [
                'name' => 'Tenant User',
                'email' => 'tenant@roomgate.test',
                'platform_role' => 'none',
                'roles' => ['tenant'],
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
