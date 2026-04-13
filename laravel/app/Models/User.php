<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $fillable = [
        'fname',
        'lname',
        'email',
        'password',
        'type_id',
        'contact_number',
        'student_id',
        'address',
        'zipcode',
        'organizer_role',
        'can_edit',
        'status',
    ];

    /* ── Relationships ─────────────────────────────── */

    public function items()
    {
        return $this->hasMany(Item::class);
    }

    public function lostReports()
    {
        return $this->hasMany(LostReport::class);
    }

    public function claims()
    {
        return $this->hasMany(Claim::class);
    }

    public function assignedTasks()
    {
        return $this->hasMany(AdminTask::class, 'assigned_to');
    }

    public function createdTasks()
    {
        return $this->hasMany(AdminTask::class, 'assigned_by');
    }

    public function sentMessages()
    {
        return $this->hasMany(Message::class, 'sender_id');
    }

    public function receivedMessages()
    {
        return $this->hasMany(Message::class, 'receiver_id');
    }

    /* ── Role Helpers ──────────────────────────────── */

    public function isStudent(): bool   { return (int) $this->type_id === 1; }
    public function isGuard(): bool     { return (int) $this->type_id === 2; }
    public function isStaff(): bool     { return (int) $this->type_id === 3; }
    public function isAdmin(): bool     { return (int) $this->type_id === 4; }
    public function isSuperAdmin(): bool { return (int) $this->type_id === 5; }
    public function isOrganizer(): bool { return (int) $this->type_id === 6; }

    public function roleName(): string
    {
        return match ((int) $this->type_id) {
            1 => 'Student',
            2 => 'Guard',
            3 => 'Staff',
            4 => 'Admin',
            5 => 'Super Admin',
            6 => 'Organizer',
            default => 'Unknown',
        };
    }

    public function hasEditAccess(): bool
    {
        if ($this->isOrganizer()) {
            return $this->organizer_role === 'president' || (bool) $this->can_edit;
        }
        return in_array((int) $this->type_id, [4, 5]);
    }

    /* ── Hidden & Casts ────────────────────────────── */

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'can_edit' => 'boolean',
        ];
    }
}
