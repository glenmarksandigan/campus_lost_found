<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\Claim;
use App\Services\ActivityLogger;
use App\Services\GeminiService;
use Illuminate\Http\Request;

class ItemController extends Controller
{
    public function index()
    {
        $items = Item::with('user')->where('status', 'Published')->latest()->get();
        return view('items.index', compact('items'));
    }

    public function create()
    {
        return view('items.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'item_name'        => 'required|string|max:255',
            'category'         => 'nullable|string|max:100',
            'description'      => 'required|string',
            'found_location'   => 'required|string|max:255',
            'storage_location' => 'required|string|max:255',
            'found_date'       => 'required|date',
            'image'            => 'nullable|image|max:5120',
        ]);

        // Append extra fields to description
        $extras = [];
        foreach ($request->all() as $key => $val) {
            if (str_starts_with($key, 'extra_') && !empty($val)) {
                $label = ucwords(str_replace('_', ' ', substr($key, 6)));
                $extras[] = "$label: $val";
            }
        }
        if (!empty($extras)) {
            $validated['description'] .= "\n\nDetails:\n" . implode("\n", $extras);
        }

        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('items', 'public');
            $validated['image_path'] = $path;
        }

        $validated['user_id'] = auth()->id();
        $validated['status']  = 'Pending';

        $item = Item::create($validated);

        ActivityLogger::log('create', 'item', $item->id, "Reported found item: {$item->item_name}");

        return redirect()->route('dashboard')->with('success', 'Item reported successfully. It will be reviewed by an admin.');
    }

    public function show(Item $item)
    {
        $item->load(['user', 'claims.user']);
        return view('items.show', compact('item'));
    }

    /** Admin manage page — tabbed by status */
    public function manage()
    {
        $statuses     = ['Pending', 'Published', 'Claiming', 'Returned'];
        $statusLabels = [
            'Pending'   => 'Pending',
            'Published' => 'Published',
            'Claiming'  => 'Verifying',
            'Returned'  => 'Returned',
        ];

        $data = [];
        $statusCounts = [];

        foreach ($statuses as $status) {
            $items = Item::with(['user', 'claims.user'])
                ->where('status', $status)
                ->latest()
                ->get();
            $data[$status]         = $items;
            $statusCounts[$status] = $items->count();
        }

        $totalCount = array_sum($statusCounts);

        return view('items.manage', compact('statuses', 'statusLabels', 'data', 'statusCounts', 'totalCount'));
    }

    public function update(Request $request, Item $item)
    {
        $item->update($request->only(['item_name', 'description', 'status', 'category']));
        ActivityLogger::log('update', 'item', $item->id, "Updated item: {$item->item_name}");
        return redirect()->back()->with('success', 'Item updated successfully.');
    }

    public function destroy(Item $item)
    {
        $name = $item->item_name;
        $item->delete();
        ActivityLogger::log('delete', 'item', $item->id ?? 0, "Deleted item: {$name}");

        if (request()->expectsJson()) {
            return response()->json(['success' => true]);
        }
        return redirect()->back()->with('success', 'Item deleted.');
    }

    /** AJAX — update status */
    public function updateStatus(Request $request, Item $item)
    {
        $request->validate(['status' => 'required|in:Pending,Published,Claiming,Returned']);
        $item->update(['status' => $request->status]);
        ActivityLogger::log('update', 'item', $item->id, "Changed status to {$request->status}");

        if ($request->expectsJson()) {
            return response()->json(['success' => true]);
        }
        return redirect()->back()->with('success', 'Status updated.');
    }

    /** AJAX — update storage location */
    public function updateStorage(Request $request, Item $item)
    {
        $request->validate(['storage_location' => 'required|string|max:255']);
        $item->update(['storage_location' => $request->storage_location]);

        if ($request->expectsJson()) {
            return response()->json(['success' => true]);
        }
        return redirect()->back()->with('success', 'Storage location updated.');
    }
}
