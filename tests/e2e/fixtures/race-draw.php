<?php

// Draw a lot for one registration exactly as Confirm does — LotAllocator inside
// a transaction — and, if asked, hold the transaction open afterwards so another
// process can try to draw while the lock is still held.
//
//     run.php race-draw.php <registration_id> <hold_seconds>

use App\Exceptions\LotUnavailable;
use App\Models\EventRegistration;
use App\Services\LotAllocator;
use Illuminate\Support\Facades\DB;

[$registrationId, $hold] = [(int) ($args[0] ?? 0), (int) ($args[1] ?? 0)];

$started = microtime(true);

try {
    $plot = DB::transaction(function () use ($registrationId, $hold) {
        $registration = EventRegistration::lockForUpdate()->findOrFail($registrationId);
        $plot = (new LotAllocator)->assign($registration);
        sleep($hold);

        return $plot;
    });

    $result = ['registration' => $registrationId, 'plot' => $plot->plot_code];
} catch (LotUnavailable $e) {
    $result = ['registration' => $registrationId, 'refused' => $e->reason];
}

$result['seconds'] = round(microtime(true) - $started, 2);

echo 'RESULT '.json_encode($result).PHP_EOL;
