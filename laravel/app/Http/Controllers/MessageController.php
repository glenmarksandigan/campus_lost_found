<?php

namespace App\Http\Controllers;

use App\Models\Message;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class MessageController extends Controller
{
    public function index(Request $request)
    {
        $userId = Auth::id();
        $user   = Auth::user();

        // Get all unique conversations for this user
        $conversations = DB::select("
            SELECT
                u.id AS other_user_id,
                u.fname, u.lname,
                u.type_id, u.organizer_role,
                (SELECT sub.subject FROM messages sub
                 WHERE (sub.sender_id = ? AND sub.receiver_id = u.id)
                    OR (sub.sender_id = u.id AND sub.receiver_id = ?)
                 ORDER BY sub.created_at DESC LIMIT 1) AS subject,
                (SELECT sub2.body FROM messages sub2
                 WHERE (sub2.sender_id = ? AND sub2.receiver_id = u.id)
                    OR (sub2.sender_id = u.id AND sub2.receiver_id = ?)
                 ORDER BY sub2.created_at DESC LIMIT 1) AS last_body,
                MAX(m.created_at) AS last_message_time,
                SUM(CASE WHEN m.receiver_id = ? AND m.is_read = 0 THEN 1 ELSE 0 END) AS unread_count
            FROM messages m
            JOIN users u ON u.id = CASE
                WHEN m.sender_id = ? THEN m.receiver_id
                ELSE m.sender_id
            END
            WHERE m.sender_id = ? OR m.receiver_id = ?
            GROUP BY u.id, u.fname, u.lname, u.type_id, u.organizer_role
            ORDER BY last_message_time DESC
        ", [$userId, $userId, $userId, $userId, $userId, $userId, $userId, $userId]);

        // Pinned contacts: Admin + Organizers (only for students/guards/staff)
        $pinnedContacts = collect([]);
        if (!in_array((int)$user->type_id, [4, 6])) {
            $pinnedContacts = User::whereIn('type_id', [4, 6])
                ->select('id', 'fname', 'lname', 'type_id', 'organizer_role')
                ->orderByRaw("type_id ASC, FIELD(organizer_role, 'president', 'member') ASC")
                ->get();
        }

        $pinnedIds = $pinnedContacts->pluck('id')->toArray();
        $convIds   = array_column($conversations, 'other_user_id');

        // Total unread
        $totalUnread = Message::where('receiver_id', $userId)->where('is_read', 0)->count();

        // Active conversation
        $activeConv   = (int)$request->query('with', 0);
        $otherUser    = null;
        $convMessages = [];
        $convSubject  = '';

        if ($activeConv) {
            // Mark as read
            Message::where('sender_id', $activeConv)
                ->where('receiver_id', $userId)
                ->where('is_read', 0)
                ->update(['is_read' => true]);

            $otherUser = User::find($activeConv);

            // Get subject from first message
            $subRow = Message::where(function ($q) use ($userId, $activeConv) {
                    $q->where('sender_id', $userId)->where('receiver_id', $activeConv);
                })
                ->orWhere(function ($q) use ($userId, $activeConv) {
                    $q->where('sender_id', $activeConv)->where('receiver_id', $userId);
                })
                ->orderBy('created_at', 'asc')
                ->first();
            $convSubject = $subRow->subject ?? 'Re: Lost & Found';

            // Load messages
            $convMessages = Message::where(function ($q) use ($userId, $activeConv) {
                    $q->where('sender_id', $userId)->where('receiver_id', $activeConv);
                })
                ->orWhere(function ($q) use ($userId, $activeConv) {
                    $q->where('sender_id', $activeConv)->where('receiver_id', $userId);
                })
                ->with('sender')
                ->orderBy('created_at', 'asc')
                ->get();
        }

        return view('messages.index', compact(
            'conversations', 'pinnedContacts', 'pinnedIds', 'totalUnread',
            'activeConv', 'otherUser', 'convMessages', 'convSubject'
        ));
    }

    public function show(User $user)
    {
        // Redirect to unified inbox with ?with= parameter
        return redirect()->route('messages.index', ['with' => $user->id]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'receiver_id' => 'required|exists:users,id',
            'body' => 'required|string',
            'subject' => 'nullable|string|max:255',
        ]);

        Message::create([
            'sender_id' => Auth::id(),
            'receiver_id' => $validated['receiver_id'],
            'subject' => $validated['subject'] ?? 'Re: Lost & Found',
            'body' => $validated['body'],
        ]);

        return redirect()->route('messages.index', ['with' => $validated['receiver_id']])->with('success', 'Message sent!');
    }
}
