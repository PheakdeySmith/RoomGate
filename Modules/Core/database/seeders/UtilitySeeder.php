<?php

namespace Modules\Core\Database\Seeders;

use App\Models\UtilityType;
use Illuminate\Database\Seeder;

class UtilitySeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            [
                'code' => 'electricity',
                'name' => 'Electricity',
                'unit_of_measure' => 'kWh',
                'billing_type' => 'metered',
            ],
            [
                'code' => 'water',
                'name' => 'Water',
                'unit_of_measure' => 'm3',
                'billing_type' => 'metered',
            ],
            [
                'code' => 'internet',
                'name' => 'Internet',
                'unit_of_measure' => 'month',
                'billing_type' => 'flat_rate',
            ],
        ];

        foreach ($types as $type) {
            UtilityType::query()->updateOrCreate(
                ['tenant_id' => null, 'code' => $type['code']],
                array_merge($type, ['is_active' => true])
            );
        }
    }
}
