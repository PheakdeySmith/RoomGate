<?php

namespace Modules\Core\App\Services;

use App\Models\Room;
use App\Models\UtilityMeter;
use App\Models\UtilityType;

class RoomUtilitySetup
{
    private const DEFAULT_UTILITY_CODES = ['electricity', 'water'];

    public function ensureDefaultRoomMeters(Room $room, int $tenantId): void
    {
        $utilityTypes = UtilityType::query()
            ->whereNull('tenant_id')
            ->whereIn('code', self::DEFAULT_UTILITY_CODES)
            ->where('is_active', true)
            ->get();

        foreach ($utilityTypes as $type) {
            $meterCode = strtoupper(sprintf('RM-%s-%s', $room->id, $type->code));

            UtilityMeter::query()->firstOrCreate(
                [
                    'tenant_id' => $tenantId,
                    'room_id' => $room->id,
                    'utility_type_id' => $type->id,
                ],
                [
                    'property_id' => $room->property_id,
                    'meter_code' => $meterCode,
                    'unit_of_measure' => $type->unit_of_measure,
                    'status' => 'active',
                    'installed_at' => null,
                ]
            );
        }
    }
}
