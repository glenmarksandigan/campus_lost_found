<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LostReport extends Model
{
    protected $fillable = [
        'user_id',
        'item_name',
        'category',
        'description',
        'last_seen_location',
        'image_path',
        'status',
        'owner_name',
        'owner_contact',
        'extra_brand', 'extra_model', 'extra_color', 'extra_case', 'extra_contents',
        'extra_material', 'extra_id_type', 'extra_id_name', 'extra_key_type',
        'extra_keychain', 'extra_type', 'extra_serial', 'extra_size', 'extra_label',
        'extra_title', 'extra_cover_color', 'extra_markings', 'extra_item_type'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function contacts()
    {
        return $this->hasMany(LostContact::class, 'report_id');
    }
}
