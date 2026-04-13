<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\Claim;
use App\Models\Message;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function edit(Request $request): View
    {
        $user   = $request->user();
        $userId = $user->id;

        $countFound  = Item::where('user_id', $userId)->count();
        $countClaimed = Claim::where('user_id', $userId)->count();
        $countMsgs   = Message::where('sender_id', $userId)->count();

        return view('profile.edit', compact('user', 'countFound', 'countClaimed', 'countMsgs'));
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'fname'          => 'required|string|max:255',
            'mname'          => 'nullable|string|max:255',
            'lname'          => 'required|string|max:255',
            'contact_number' => 'nullable|string|max:50',
        ]);

        $user->update($validated);

        return Redirect::route('profile.edit')->with('success', 'Profile updated successfully!');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'current_password' => 'required|current_password',
            'password'         => 'required|string|min:6|confirmed',
        ]);

        $request->user()->update([
            'password' => Hash::make($validated['password']),
        ]);

        return Redirect::route('profile.edit')->with('success', 'Password updated successfully!');
    }

    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();
        Auth::logout();
        $user->delete();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
