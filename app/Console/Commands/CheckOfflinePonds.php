<?php

namespace App\Console\Commands;

use App\Models\PondDevice;
use App\Models\SensorData;
use App\Services\PondAlertService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class CheckOfflinePonds extends Command
{
    protected $signature = 'ponds:check-offline {--minutes=10 : Minutes without a reading before a device counts as offline}';
    protected $description = 'Notify farmers when a pond device stops sending readings';

    public function handle(PondAlertService $alerts): int
    {
        $threshold = now()->subMinutes((int) $this->option('minutes'));

        foreach (PondDevice::where('status', 'Active')->get() as $device) {
            $last = SensorData::where('device_id', $device->device_id)->max('timestamp');

            // Hanya peranti yang pernah aktif dalam 7 hari terakhir (elak amaran untuk peranti lama)
            if (! $last || Carbon::parse($last)->lt(now()->subDays(7)) || Carbon::parse($last)->gt($threshold)) {
                continue;
            }

            if ($alerts->deviceOffline($device, Carbon::parse($last))) {
                $this->info("Offline alert sent: {$device->pond_name}");
            }
        }

        return self::SUCCESS;
    }
}
