<?php

namespace Modules\Core\Database\Seeders;

use Illuminate\Database\Seeder;

class CoreDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $seeders = [
            PlanSeeder::class,
            PropertyTypeSeeder::class,
            UtilitySeeder::class,
        ];

        if (!app()->environment('production')) {
            $seeders[] = PropertyRoomSeeder::class;
        }

        $this->call($seeders);
    }
}
