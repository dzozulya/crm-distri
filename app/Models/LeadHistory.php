<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Enums\LeadStatus;

class LeadHistory extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'lead_id',
        'old_manager_id',
        'new_manager_id',
        'old_status',
        'new_status',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'old_status' => LeadStatus::class,
            'new_status' => LeadStatus::class,
            'created_at' => 'datetime',
        ];
    }
}
