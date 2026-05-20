<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeadQueueState extends Model
{
    protected $fillable = [
        'lead_id',
        'action_fingerprint',
        'state',
        'snoozed_until',
        'dismissed_until',
        'reason_tag',
        'reason_note',
        'acted_by_user_id',
        'acted_at',
    ];

    protected function casts(): array
    {
        return [
            'snoozed_until' => 'datetime',
            'dismissed_until' => 'datetime',
            'acted_at' => 'datetime',
        ];
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Prospect::class, 'lead_id');
    }
}

