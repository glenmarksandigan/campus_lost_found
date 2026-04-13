<?php

namespace App\Http\Controllers;

use App\Models\Claim;
use App\Models\Item;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;

class ClaimController extends Controller
{
    public function store(Request $request, Item $item)
    {
        $validated = $request->validate([
            'claim_message' => 'nullable|string',
            'image_path'    => 'nullable|image|max:2048',
        ]);

        if ($request->hasFile('image_path')) {
            $path = $request->file('image_path')->store('claims', 'public');
            $validated['image_path'] = $path;
        }

        $validated['item_id'] = $item->id;
        $validated['user_id'] = auth()->id();

        Claim::create($validated);

        ActivityLogger::log('create', 'claim', $item->id, auth()->user()->fname . ' claimed item: ' . $item->item_name);

        return redirect()->back()->with('success', 'Claim submitted successfully.');
    }

    public function update(Request $request, Claim $claim)
    {
        $validated = $request->validate([
            'status' => 'required|in:pending,verified,rejected,returned',
        ]);

        $claim->update($validated);

        if ($validated['status'] === 'returned') {
            $claim->item->update(['status' => 'Returned']);
        } elseif ($validated['status'] === 'verified') {
            $claim->item->update(['status' => 'Claiming']);
        }

        return redirect()->back()->with('success', 'Claim status updated.');
    }

    /** Verify a claimer — rejects all other pending claims for the same item */
    public function verify(Claim $claim)
    {
        // Verify this claim
        $claim->update(['status' => 'verified']);

        // Reject all other pending claims for this item
        Claim::where('item_id', $claim->item_id)
            ->where('id', '!=', $claim->id)
            ->where('status', 'pending')
            ->update(['status' => 'rejected']);

        // Update item status
        $claim->item->update(['status' => 'Claiming']);

        ActivityLogger::log('verify', 'claim', $claim->id, "Verified claimer {$claim->user->fname} for item: {$claim->item->item_name}");

        if (request()->expectsJson()) {
            return response()->json(['success' => true, 'message' => 'Claimer verified!']);
        }
        return redirect()->back()->with('success', 'Claimer verified and other claims rejected.');
    }

    /** Reject a single claim */
    public function reject(Claim $claim)
    {
        $claim->update(['status' => 'rejected']);

        ActivityLogger::log('reject', 'claim', $claim->id, "Rejected claim #{$claim->id}");

        if (request()->expectsJson()) {
            return response()->json(['success' => true, 'message' => 'Claim rejected.']);
        }
        return redirect()->back()->with('success', 'Claim rejected.');
    }

    /** Confirm return — mark item as Returned */
    public function confirmReturn(Claim $claim)
    {
        $claim->update(['status' => 'returned']);
        $claim->item->update(['status' => 'Returned']);

        ActivityLogger::log('return', 'item', $claim->item_id, "Confirmed return of: {$claim->item->item_name} to {$claim->user->fname}");

        if (request()->expectsJson()) {
            return response()->json(['success' => true, 'message' => 'Item marked as returned!']);
        }
        return redirect()->back()->with('success', 'Item marked as returned.');
    }
}
