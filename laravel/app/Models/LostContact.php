<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LostContact extends Model
{
    protected $fillable = [
        'report_id',
        'finder_name',
        'finder_contact',
        'message',
    ];

    public function report()
    {
        return $this->belongsTo(LostReport::class, 'report_id');
    }
}
