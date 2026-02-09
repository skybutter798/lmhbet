<?php

// /home/lmh/app/app/Observers/BetRecordObserver.php

namespace App\Observers;

use App\Models\BetRecord;
use App\Services\TurnoverService;

class BetRecordObserver
{
    public function created(BetRecord $bet): void
    {
        app(TurnoverService::class)->applySettledBet($bet);
    }

    public function updated(BetRecord $bet): void
    {
        // Apply only when it transitions to settled
        if ($bet->wasChanged('status')) {
            app(TurnoverService::class)->applySettledBet($bet);
        }
    }
}