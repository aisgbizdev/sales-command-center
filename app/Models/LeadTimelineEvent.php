<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeadTimelineEvent extends Model
{
    protected $fillable = [
        'lead_id',
        'event_type',
        'event_at',
        'actor_type',
        'actor_id',
        'source',
        'ref_type',
        'ref_id',
        'payload',
        'dedupe_key',
    ];

    protected function casts(): array
    {
        return [
            'event_at' => 'datetime',
            'payload' => 'array',
        ];
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Prospect::class, 'lead_id');
    }
}

