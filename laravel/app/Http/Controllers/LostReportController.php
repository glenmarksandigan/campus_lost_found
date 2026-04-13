<?php

namespace App\Http\Controllers;

use App\Models\LostReport;
use App\Models\LostContact;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;

class LostReportController extends Controller
{
    public function index()
    {
        $reports = LostReport::with('user')->latest()->get();
        return view('lost-reports.index', compact('reports'));
    }

    public function create()
    {
        return view('lost-reports.create');
    }

    public function store(Request $request)
    {
        $rules = [
            'item_name'          => 'required|string|max:255',
            'category'           => 'required|string|max:100',
            'description'        => 'nullable|string',
            'last_seen_location' => 'required|string|max:255',
            'image_path'         => 'nullable|image|max:5120',
            'owner_name'         => 'nullable|string|max:255',
            'owner_contact'      => 'nullable|string|max:50',
        ];

        // Add dynamic extra fields to validation rules
        foreach ($request->all() as $key => $val) {
            if (str_starts_with($key, 'extra_')) {
                $rules[$key] = 'nullable|string|max:255';
            }
        }

        $validated = $request->validate($rules);

        if ($request->hasFile('image_path')) {
            $path = $request->file('image_path')->store('lost-reports', 'public');
            $validated['image_path'] = $path;
        }

        $validated['user_id'] = auth()->id();
        $report = LostReport::create($validated);

        ActivityLogger::log('create', 'lost_report', $report->id, "Reported lost: {$report->item_name}");

        return redirect()->route('dashboard')->with('success', 'Lost report submitted.');
    }

    public function show(LostReport $lostReport)
    {
        $lostReport->load(['user', 'contacts']);
        return view('lost-reports.show', compact('lostReport'));
    }

    /** Admin manage page */
    public function manage()
    {
        $statuses     = ['Lost', 'Matching', 'Resolved'];
        $statusLabels = [
            'Lost'     => 'Active',
            'Matching' => 'Matching',
            'Resolved' => 'Resolved',
        ];

        $data = [];
        $statusCounts = [];

        foreach ($statuses as $status) {
            $reports = LostReport::with('user')
                ->where('status', $status)
                ->latest()
                ->get();
            $data[$status]         = $reports;
            $statusCounts[$status] = $reports->count();
        }

        $totalCount = array_sum($statusCounts);

        return view('lost-reports.manage', compact('statuses', 'statusLabels', 'data', 'statusCounts', 'totalCount'));
    }

    public function update(Request $request, LostReport $lostReport)
    {
        $lostReport->update($request->only(['item_name', 'description', 'status']));
        ActivityLogger::log('update', 'lost_report', $lostReport->id, "Updated report: {$lostReport->item_name}");
        return redirect()->back()->with('success', 'Report updated.');
    }

    public function destroy(LostReport $lostReport)
    {
        $name = $lostReport->item_name;
        $lostReport->delete();
        ActivityLogger::log('delete', 'lost_report', $lostReport->id ?? 0, "Deleted report: {$name}");

        if (request()->expectsJson()) {
            return response()->json(['success' => true]);
        }
        return redirect()->back()->with('success', 'Report deleted.');
    }

    /** AJAX — update status */
    public function updateStatus(Request $request, LostReport $lostReport)
    {
        $request->validate(['status' => 'required|in:Lost,Matching,Resolved']);
        $lostReport->update(['status' => $request->status]);
        ActivityLogger::log('update', 'lost_report', $lostReport->id, "Changed status to {$request->status}");

        if ($request->expectsJson()) {
            return response()->json(['success' => true]);
        }
        return redirect()->back()->with('success', 'Status updated.');
    }

    /** Contact owner — for finders */
    public function contactOwner(Request $request, LostReport $lostReport)
    {
        $validated = $request->validate([
            'finder_name'    => 'required|string|max:255',
            'finder_contact' => 'nullable|string|max:100',
            'message'        => 'nullable|string',
        ]);

        $validated['report_id'] = $lostReport->id;
        LostContact::create($validated);

        return redirect()->back()->with('success', 'Owner has been notified!');
    }
}
