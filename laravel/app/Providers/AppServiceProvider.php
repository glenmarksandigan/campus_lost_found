<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        \App\Models\Item::observe(\App\Observers\ItemObserver::class);
        \App\Models\LostReport::observe(\App\Observers\LostReportObserver::class);
        \App\Models\Claim::observe(\App\Observers\ClaimObserver::class);

        // Share data with topnav
        view()->composer('layouts.partials.topnav', function ($view) {
            if (auth()->check()) {
                $userId = auth()->id();
                
                // Unread messages count
                $unreadCount = \App\Models\Message::where('receiver_id', $userId)
                    ->where('is_read', 0)
                    ->count();

                // My Reports (Found + Lost) - SEPARATE FOR DEBUG
                $foundReports = \Illuminate\Support\Facades\DB::table('items')
                    ->select('id', 'item_name', 'description', 'found_location as location', 'image_path', 'status', 'created_at', \Illuminate\Support\Facades\DB::raw("'Found' as report_type"))
                    ->where('user_id', $userId)
                    ->get();

                $lostReports = \Illuminate\Support\Facades\DB::table('lost_reports')
                    ->select('id', 'item_name', 'description', 'last_seen_location as location', 'image_path', 'status', 'created_at', \Illuminate\Support\Facades\DB::raw("'Lost' as report_type"))
                    ->where('user_id', $userId)
                    ->get();
                
                $myItems = $foundReports->merge($lostReports)->sortByDesc('created_at');

                // My Claims
                $myClaims = \App\Models\Claim::where('user_id', $userId)
                    ->with('item')
                    ->latest()
                    ->get();

                $view->with(compact('unreadCount', 'myItems', 'myClaims'));
            }
        });
    }
}
