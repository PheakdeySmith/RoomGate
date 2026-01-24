<?php

namespace Modules\Core\Database\Seeders;

use App\Models\Property;
use App\Models\PropertyType;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\Tenant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class PropertyRoomSeeder extends Seeder
{
    public function run(): void
    {
        $tenants = Tenant::query()->orderBy('id')->get();
        if ($tenants->isEmpty()) {
            return;
        }

        $defaultType = PropertyType::query()->whereNull('tenant_id')->first();
        if (!$defaultType) {
            $defaultType = PropertyType::create([
                'tenant_id' => null,
                'name' => 'Apartment',
                'status' => 'active',
            ]);
        }

        $propertyCount = 0;
        foreach ($tenants as $tenant) {
            $roomTypes = [
                RoomType::firstOrCreate(
                    ['tenant_id' => $tenant->id, 'name' => 'Studio'],
                    ['status' => 'active', 'capacity' => 1]
                ),
                RoomType::firstOrCreate(
                    ['tenant_id' => $tenant->id, 'name' => '1BR'],
                    ['status' => 'active', 'capacity' => 2]
                ),
            ];

            if ($propertyCount >= 5) {
                continue;
            }

            $property = Property::create([
                'tenant_id' => $tenant->id,
                'property_type_id' => $defaultType->id,
                'name' => 'Property ' . strtoupper(Str::random(4)),
                'city' => 'Phnom Penh',
                'country' => 'Cambodia',
                'status' => 'active',
            ]);

            Room::create([
                'tenant_id' => $tenant->id,
                'property_id' => $property->id,
                'room_type_id' => $roomTypes[0]->id,
                'room_number' => 'A-101',
                'max_occupants' => 1,
                'monthly_rent_cents' => 12000,
                'currency_code' => 'USD',
                'status' => 'available',
            ]);

            Room::create([
                'tenant_id' => $tenant->id,
                'property_id' => $property->id,
                'room_type_id' => $roomTypes[1]->id,
                'room_number' => 'A-102',
                'max_occupants' => 2,
                'monthly_rent_cents' => 15000,
                'currency_code' => 'USD',
                'status' => 'available',
            ]);

            $propertyCount++;
        }
    }
}
