<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Item;
use App\Models\LostReport;
use App\Models\Claim;
use App\Models\User;
use App\Models\ActivityLog;
use App\Models\LostContact;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        if (in_array((int)$user->type_id, [4, 5, 6, 2])) {
            return match ((int)$user->type_id) {
                    4 => $this->admin(),
                    5 => $this->superadmin(),
                    6 => $this->organizer(),
                    2 => $this->guard(),
                };
        }

        // ── Student / Browse Gallery ────────────────────────────────────
        $userId = $user->id;

        // Found Items with claim info
        $foundItems = Item::whereIn('status', ['Published', 'Claiming'])
            ->latest()
            ->get()
            ->map(function ($item) use ($userId) {
                // Load claims
                $claims = Claim::where('item_id', $item->id)
                    ->join('users', 'claims.user_id', '=', 'users.id')
                    ->select('claims.*', 'users.fname', 'users.lname')
                    ->get();

                $item->claimer_names = $claims->map(fn($c) => $c->fname . ' ' . $c->lname)->implode(', ');
                $item->my_claim = $claims->firstWhere('user_id', $userId);
                $item->has_my_claim = $claims->where('user_id', $userId)->count() > 0;
                $item->verified_count = $claims->where('status', 'verified')->count();
                $item->is_uploader = ((int)$item->user_id === (int)$userId);

                // Finder info (for Contact Finder modal)
                if (trim($item->storage_location) === "Finder's Possession" && !$item->is_uploader) {
                    $finder = User::find($item->user_id);
                    $item->finder_user = $finder;
                }

                return $item;
            });

        // Lost Reports
        $lostReports = LostReport::whereIn('status', ['Lost', 'Matching', 'Resolved'])
            ->latest()
            ->get()
            ->map(function ($report) use ($userId) {
                $report->is_owner = ((int)$report->user_id === (int)$userId);

                // Finder details for matching reports
                if ($report->status === 'Matching') {
                    // Check lost_contacts table
                    $contact = LostContact::where('report_id', $report->id)->latest()->first();
                    if ($contact) {
                        $report->finder_detail = $contact;
                    }

                    // Check items linked to this report
                    $linkedItem = Item::where('lost_report_id', $report->id)
                        ->join('users', 'items.user_id', '=', 'users.id')
                        ->select('items.*', 'users.fname as finder_fname', 'users.lname as finder_lname',
                                 'users.email as finder_email', 'users.contact_number as finder_contact',
                                 'users.id as finder_user_id')
                        ->first();
                    if ($linkedItem && !$report->finder_detail) {
                        $report->finder_detail = (object)[
                            'finder_name' => $linkedItem->finder_fname . ' ' . $linkedItem->finder_lname,
                            'finder_email' => $linkedItem->finder_email,
                            'finder_contact' => $linkedItem->finder_contact,
                            'finder_user_id' => $linkedItem->finder_user_id,
                            'found_location' => $linkedItem->found_location,
                            'message' => $linkedItem->description,
                            'image_path' => $linkedItem->image_path,
                            'created_at' => $linkedItem->created_at,
                        ];
                    }
                }

                return $report;
            });

        return view('dashboard', compact('foundItems', 'lostReports'));
    }

    public function admin()
    {
        // ── Found Items Stats
        $statsQuery = Item::selectRaw("status, COUNT(*) as count")->groupBy('status')->pluck('count', 'status');
        $pendingCount   = $statsQuery['Pending']   ?? 0;
        $publishedCount = $statsQuery['Published'] ?? 0;
        $claimingCount  = $statsQuery['Claiming']  ?? 0;
        $returnedCount  = $statsQuery['Returned']  ?? 0;
        $totalItems     = $statsQuery->sum();

        // ── Lost Items Stats
        $lostStats      = LostReport::selectRaw("status, COUNT(*) as count")->groupBy('status')->pluck('count', 'status');
        $unresolvedLost = $lostStats['Lost']     ?? 0;
        $matchingLost   = $lostStats['Matching'] ?? 0;
        $resolvedLost   = $lostStats['Resolved'] ?? 0;
        $totalLost      = $lostStats->sum();

        // ── User Stats
        $totalStudents  = User::where('type_id', 1)->count();
        $pendingUsers   = User::where('type_id', 1)->where('status', 'pending')->count();
        $approvedUsers  = User::where('type_id', 1)->where('status', 'approved')->count();
        $newUsersMonth  = User::where('type_id', 1)->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->count();

        // ── This Month
        $thisMonth         = Item::whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->count();
        $thisMonthReturned = Item::where('status', 'Returned')->whereMonth('updated_at', now()->month)->whereYear('updated_at', now()->year)->count();
        $successRate       = $totalItems > 0 ? round(($returnedCount / $totalItems) * 100) : 0;

        // ── Chart Data: Monthly (current year)
        $monthlyFound = Item::selectRaw("MONTH(created_at) as m, COUNT(*) as c")->whereYear('created_at', now()->year)->groupBy('m')->pluck('c', 'm');
        $monthlyLost  = LostReport::selectRaw("MONTH(created_at) as m, COUNT(*) as c")->whereYear('created_at', now()->year)->groupBy('m')->pluck('c', 'm');
        $monthlyFoundData = $monthlyLostData = [];
        for ($i = 1; $i <= 12; $i++) {
            $monthlyFoundData[] = $monthlyFound[$i] ?? 0;
            $monthlyLostData[]  = $monthlyLost[$i]  ?? 0;
        }

        // ── Activity Log
        $recentActivity = ActivityLog::with('user')->latest()->take(50)->get();

        // ── Pending Claims
        $pendingClaims = Claim::where('status', 'pending')->count();
        $totalClaimed = $returnedCount;

        return view('dashboards.admin', compact(
            'totalItems', 'totalLost', 'totalClaimed', 'pendingClaims',
            'pendingCount', 'publishedCount', 'claimingCount', 'returnedCount',
            'unresolvedLost', 'matchingLost', 'resolvedLost',
            'totalStudents', 'pendingUsers', 'approvedUsers', 'newUsersMonth',
            'thisMonth', 'thisMonthReturned', 'successRate',
            'monthlyFoundData', 'monthlyLostData',
            'recentActivity'
        ));
    }

    public function superadmin()
    {
        $totalItems     = Item::count();
        $totalLost      = LostReport::count();
        $totalUsers     = User::whereIn('type_id', [1, 3])->count();
        $pendingUsers   = User::where('status', 'pending')->count();
        $returnedItems  = Item::where('status', 'Returned')->count();
        $pendingItems   = Item::where('status', 'Pending')->count();
        $activeTasks    = DB::table('admin_tasks')->whereIn('status', ['pending', 'in_progress'])->count();

        $recentActivity = ActivityLog::with('user')->latest()->take(20)->get();

        // Organizer stats
        $organizerStats = DB::table('users')
            ->leftJoin('admin_tasks', 'users.id', '=', 'admin_tasks.assigned_to')
            ->where('users.type_id', 6)
            ->select('users.id', 'users.fname', 'users.lname', 'users.organizer_role',
                DB::raw("COUNT(CASE WHEN admin_tasks.status = 'completed' THEN 1 END) as completed_tasks"),
                DB::raw("COUNT(CASE WHEN admin_tasks.status IN ('pending','in_progress') THEN 1 END) as active_tasks"))
            ->groupBy('users.id', 'users.fname', 'users.lname', 'users.organizer_role')
            ->orderByRaw("users.organizer_role DESC, users.fname")
            ->get();

        // Monthly trends (6 months)
        $monthlyData = [];
        for ($i = 5; $i >= 0; $i--) {
            $month = now()->subMonths($i)->format('Y-m');
            $label = now()->subMonths($i)->format('M Y');
            $items = Item::whereRaw("DATE_FORMAT(created_at, '%Y-%m') = ?", [$month])->count();
            $lost  = LostReport::whereRaw("DATE_FORMAT(created_at, '%Y-%m') = ?", [$month])->count();
            $monthlyData[] = ['label' => $label, 'items' => $items, 'lost' => $lost];
        }

        return view('dashboards.superadmin', compact(
            'totalItems', 'totalLost', 'totalUsers', 'pendingUsers',
            'returnedItems', 'pendingItems', 'activeTasks',
            'recentActivity', 'organizerStats', 'monthlyData'
        ));
    }

    public function organizer()
    {
        $user = auth()->user();
        $pendingItems   = Item::where('status', 'Pending')->count();
        $publishedItems = Item::where('status', 'Published')->count();
        $unresolvedLost = LostReport::where('status', 'Lost')->count();
        $claimingItems  = Item::where('status', 'Claiming')->count();

        $hasEditAccess = $user->hasEditAccess();

        // My Tasks
        $tasks = DB::table('admin_tasks')
            ->join('users', 'admin_tasks.assigned_by', '=', 'users.id')
            ->where('admin_tasks.assigned_to', $user->id)
            ->where('admin_tasks.status', '!=', 'cancelled')
            ->select('admin_tasks.*', 'users.fname', 'users.lname')
            ->orderByRaw("FIELD(admin_tasks.priority, 'urgent', 'high', 'normal', 'low')")
            ->orderByRaw("FIELD(admin_tasks.status, 'pending', 'in_progress', 'completed')")
            ->orderBy('admin_tasks.due_date')
            ->get();

        $pendingTasks    = $tasks->where('status', 'pending');
        $inProgressTasks = $tasks->where('status', 'in_progress');
        $completedTasks  = $tasks->where('status', 'completed');

        // All Found Items with claims
        $allItems = Item::withCount('claims')->latest()->take(50)->get();
        $allClaims = [];
        $itemIds = $allItems->pluck('id')->toArray();
        if (!empty($itemIds)) {
            $claimsData = Claim::whereIn('item_id', $itemIds)
                ->join('users', 'claims.user_id', '=', 'users.id')
                ->select('claims.*', 'users.fname', 'users.lname', 'users.email', 'users.contact_number', 'users.student_id')
                ->orderBy('claims.claimed_at', 'desc')
                ->get();
            foreach ($claimsData as $claim) {
                $allClaims[$claim->item_id][] = $claim;
            }
        }

        // All Lost Reports
        $allLostReports = LostReport::latest()->take(50)->get();

        return view('dashboards.organizer', compact(
            'pendingItems', 'publishedItems', 'unresolvedLost', 'claimingItems',
            'hasEditAccess', 'tasks', 'pendingTasks', 'inProgressTasks', 'completedTasks',
            'allItems', 'allClaims', 'allLostReports'
        ));
    }

    public function guard()
    {
        $totalLost    = LostReport::count();
        $totalFound   = Item::count();
        $totalClaimed = Item::where('status', 'Returned')->count();
        $totalPending = Item::where('status', 'Published')->count();
        $pendingClaims = Item::where('status', 'Claiming')->count();

        $lostReports = LostReport::latest()->get();
        $foundItems  = Item::with('user')->latest()->get();

        // Items being claimed with claimers
        $claimingItems = Item::with('user')->where('status', 'Claiming')->latest()->get();

        $claimsByItem = [];
        if ($claimingItems->count()) {
            $ids = $claimingItems->pluck('id')->toArray();
            $claimers = Claim::with('user')->whereIn('item_id', $ids)->orderBy('claimed_at')->get();
            foreach ($claimers as $cl) {
                $claimsByItem[$cl->item_id][] = $cl;
            }
        }

        $admins = User::where('type_id', 4)->select('fname', 'lname', 'email', 'id')->get();

        return view('dashboards.guard', compact(
            'totalLost', 'totalFound', 'totalClaimed', 'totalPending', 'pendingClaims',
            'lostReports', 'foundItems', 'claimingItems', 'claimsByItem', 'admins'
        ));
    }

    public function landing()
    {
        $totalFound   = Item::count();
        $totalClaimed = Item::where('status', 'Returned')->count();
        $totalLost    = LostReport::count();
        $totalUsers   = User::whereIn('type_id', [1, null])->count();
        $totalResolved = LostReport::where('status', 'Resolved')->count();
        $successRate  = $totalLost > 0 ? round(($totalResolved / $totalLost) * 100) : 0;

        $recentItems = Item::where('status', 'Published')
            ->latest()->take(5)->get();

        $recentLostReports = LostReport::where('status', '!=', 'Resolved')
            ->latest()->take(5)->get();

        return view('landing', compact(
            'totalFound', 'totalClaimed', 'totalLost', 'totalUsers',
            'totalResolved', 'successRate', 'recentItems', 'recentLostReports'
        ));
    }
}
