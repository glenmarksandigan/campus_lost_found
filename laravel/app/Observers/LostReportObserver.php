<?php

namespace App\Observers;

use App\Models\LostReport;
use App\Services\ActivityLogger;

class LostReportObserver
{
    public function created(LostReport $report)
    {
        ActivityLogger::log('create', 'lost_report', $report->id, "Lost report for '{$report->item_name}' was created.");
    }

    public function updated(LostReport $report)
    {
        if ($report->isDirty('status')) {
            ActivityLogger::log('update', 'lost_report', $report->id, "Status changed to '{$report->status}'.");
        }
    }

    public function deleted(LostReport $report)
    {
        ActivityLogger::log('delete', 'lost_report', $report->id, "Lost report for '{$report->item_name}' was deleted.");
    }
}
