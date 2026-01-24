<?php

namespace Modules\Core\Database\Seeders;

use App\Models\PropertyType;
use Illuminate\Database\Seeder;

class PropertyTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            'Apartment',
            'House',
            'Condo',
            'Villa',
            'Dorm',
            'Townhouse',
            'Mixed-use',
        ];

        foreach ($types as $name) {
            PropertyType::firstOrCreate(
                ['tenant_id' => null, 'name' => $name],
                ['status' => 'active']
            );
        }
    }
}
