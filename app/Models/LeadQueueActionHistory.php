<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeadQueueActionHistory extends Model
{
    protected $fillable = [
        'lead_id',
        'snapshot_id',
        'action_fingerprint',
        'action_type',
        'reason_tag',
        'reason_note',
        'payload',
        'acted_by_user_id',
        'acted_at',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'acted_at' => 'datetime',
        ];
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Prospect::class, 'lead_id');
    }
}

