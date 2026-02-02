<?php

namespace App\Console\Commands;

use App\Models\Contract;
use App\Models\Room;
use Illuminate\Console\Command;

class ReconcileRoomStatus extends Command
{
    protected $signature = 'rooms:reconcile-status';

    protected $description = 'Ensure room status matches active contract occupancy.';

    public function handle(): int
    {
        $updated = 0;

        Room::query()->orderBy('id')->chunk(200, function ($rooms) use (&$updated) {
            foreach ($rooms as $room) {
                $hasActive = Contract::query()
                    ->where('tenant_id', $room->tenant_id)
                    ->where('room_id', $room->id)
                    ->where('status', 'active')
                    ->exists();

                if ($hasActive) {
                    if ($room->status !== 'occupied') {
                        $room->update(['status' => 'occupied']);
                        $updated++;
                    }
                    continue;
                }

                if ($room->status === 'occupied') {
                    $room->update(['status' => 'available']);
                    $updated++;
                }
            }
        });

        $this->info("Rooms updated: {$updated}");

        return self::SUCCESS;
    }
}
