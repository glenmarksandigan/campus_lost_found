<?php

namespace App\Observers;

use App\Models\Item;
use App\Services\ActivityLogger;

class ItemObserver
{
    public function created(Item $item)
    {
        ActivityLogger::log('create', 'item', $item->id, "Item '{$item->item_name}' was reported found.");
    }

    public function updated(Item $item)
    {
        if ($item->isDirty('status')) {
            ActivityLogger::log('update', 'item', $item->id, "Status changed to '{$item->status}'.");
        }
    }

    public function deleted(Item $item)
    {
        ActivityLogger::log('delete', 'item', $item->id, "Item '{$item->item_name}' was deleted.");
    }
}
