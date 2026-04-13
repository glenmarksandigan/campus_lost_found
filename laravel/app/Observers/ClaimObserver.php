<?php

namespace App\Observers;

use App\Models\Claim;
use App\Services\ActivityLogger;

class ClaimObserver
{
    public function created(Claim $claim)
    {
        ActivityLogger::log('create', 'claim', $claim->id, "A new claim was submitted for item #{$claim->item_id}.");
    }

    public function updated(Claim $claim)
    {
        if ($claim->isDirty('status')) {
            ActivityLogger::log('update', 'claim', $claim->id, "Claim status changed to '{$claim->status}'.");
        }
    }

    public function deleted(Claim $claim)
    {
        ActivityLogger::log('delete', 'claim', $claim->id, "Claim #{$claim->id} was deleted.");
    }
}
