<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\Claim;
use Illuminate\Http\Request;

class SuccessLogController extends Controller
{
    public function index()
    {
        $returnedItems = Item::with(['user', 'claims' => function ($q) {
                $q->where('status', 'returned')->orWhere('status', 'verified');
            }, 'claims.user'])
            ->where('status', 'Returned')
            ->latest()
            ->get();

        return view('admin.success-log', compact('returnedItems'));
    }

    public function claimSlip(Item $item)
    {
        $item->load(['user', 'claims' => function ($q) {
            $q->whereIn('status', ['returned', 'verified'])->with('user');
        }]);

        $claim = $item->claims->first();
        $claimer = $claim?->user;

        return view('admin.claim-slip', compact('item', 'claim', 'claimer'));
    }
}
