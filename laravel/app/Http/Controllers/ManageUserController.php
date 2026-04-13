<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\ActivityLogger;
use App\Services\MailService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class ManageUserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::query();

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('fname', 'like', "%{$search}%")
                  ->orWhere('lname', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('student_id', 'like', "%{$search}%");
            });
        }

        if ($role = $request->input('role')) {
            $query->where('type_id', $role);
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        $users = $query->latest()->paginate(20);

        return view('admin.manage-users', compact('users'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'fname'      => 'required|string|max:100',
            'lname'      => 'required|string|max:100',
            'email'      => 'required|email|unique:users,email',
            'type_id'    => 'required|integer|in:1,2,3,4,5,6',
            'student_id' => 'nullable|string|max:30',
            'contact_number' => 'nullable|string|max:20',
        ]);

        $tempPassword = Str::random(10);
        $validated['password'] = Hash::make($tempPassword);
        $validated['status']   = 'Active';

        $user = User::create($validated);

        // Send credentials via email
        try {
            (new MailService())->sendCredentialEmail($user, $tempPassword, 'new_account');
        } catch (\Exception $e) {
            // Log but don't block creation
        }

        ActivityLogger::log('create', 'user', $user->id, "Created user: {$user->fname} {$user->lname} ({$user->roleName()})");

        return redirect()->back()->with('success', "User created! Temp password: {$tempPassword}");
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'fname'      => 'sometimes|string|max:100',
            'lname'      => 'sometimes|string|max:100',
            'email'      => "sometimes|email|unique:users,email,{$user->id}",
            'type_id'    => 'sometimes|integer|in:1,2,3,4,5,6',
            'status'     => 'sometimes|string|in:Active,Inactive,Pending',
            'student_id' => 'nullable|string|max:30',
            'contact_number' => 'nullable|string|max:20',
            'organizer_role' => 'nullable|string|max:50',
            'can_edit'   => 'nullable|boolean',
        ]);

        $user->update($validated);

        ActivityLogger::log('update', 'user', $user->id, "Updated user: {$user->fname} {$user->lname}");

        if ($request->expectsJson()) {
            return response()->json(['success' => true]);
        }
        return redirect()->back()->with('success', 'User updated.');
    }

    public function destroy(User $user)
    {
        $name = "{$user->fname} {$user->lname}";
        $user->delete();
        ActivityLogger::log('delete', 'user', $user->id ?? 0, "Deleted user: {$name}");

        if (request()->expectsJson()) {
            return response()->json(['success' => true]);
        }
        return redirect()->back()->with('success', 'User deleted.');
    }

    public function resetPassword(User $user)
    {
        $newPassword = Str::random(10);
        $user->update(['password' => Hash::make($newPassword)]);

        try {
            (new MailService())->sendCredentialEmail($user, $newPassword, 'reset');
        } catch (\Exception $e) {
            // Log but don't block
        }

        ActivityLogger::log('update', 'user', $user->id, "Reset password for: {$user->fname} {$user->lname}");

        return redirect()->back()->with('success', "Password reset! New temp password: {$newPassword}");
    }
}
