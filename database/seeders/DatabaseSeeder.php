<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            BusinessSettingsSeeder::class,
            TranslationSeeder::class,
            \Modules\User\Database\Seeders\UserDatabaseSeeder::class,
            \Modules\Core\Database\Seeders\CoreDatabaseSeeder::class,
        ]);
    }
}
