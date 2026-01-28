<?php

namespace Modules\User\Database\Seeders;

use Illuminate\Database\Seeder;

class UserDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $seeders = [
            RolePermissionSeeder::class,
        ];

        if (!app()->environment('production')) {
            $seeders[] = DemoUsersSeeder::class;
        }

        $this->call($seeders);
    }
}
