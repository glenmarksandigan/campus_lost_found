<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'action_type',
        'target_type',
        'target_id',
        'details',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
