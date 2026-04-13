<?php

namespace App\Services;

use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;

class ActivityLogger
{
    public static function log($actionType, $targetType, $targetId, $details = null)
    {
        return ActivityLog::create([
            'user_id' => Auth::id() ?? 1, // Fallback for system actions
            'action_type' => $actionType,
            'target_type' => $targetType,
            'target_id' => $targetId,
            'details' => $details,
        ]);
    }
}
