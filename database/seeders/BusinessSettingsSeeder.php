<?php

namespace Database\Seeders;

use App\Models\BusinessSetting;
use Illuminate\Database\Seeder;

class BusinessSettingsSeeder extends Seeder
{
    public function run(): void
    {
        if (BusinessSetting::query()->exists()) {
            return;
        }

        BusinessSetting::query()->create([
            'app_name' => 'RoomGate',
            'app_short_name' => 'RoomGate',
            'company_name' => 'RoomGate',
            'tagline' => 'Property management made simple.',
            'support_email' => 'support@roomgate.test',
        ]);
    }
}
