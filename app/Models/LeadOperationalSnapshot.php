<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class LeadOperationalSnapshot extends Model
{
    protected $fillable = [
        'lead_id',
        'owner_user_id',
        'pipeline_stage',
        'priority_score',
        'priority_band',
        'ghost_risk_score',
        'temperature_score',
        'overdue_minutes',
        'response_delay_minutes',
        'last_inbound_at',
        'last_outbound_at',
        'last_contacted_at',
        'owner_open_tasks',
        'next_action_code',
        'next_action_confidence',
        'next_action_expires_at',
        'stale_after_at',
        'version',
        'computed_at',
    ];

    protected function casts(): array
    {
        return [
            'last_inbound_at' => 'datetime',
            'last_outbound_at' => 'datetime',
            'last_contacted_at' => 'datetime',
            'next_action_confidence' => 'decimal:2',
            'next_action_expires_at' => 'datetime',
            'stale_after_at' => 'datetime',
            'computed_at' => 'datetime',
        ];
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Prospect::class, 'lead_id');
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    public function queueState(): HasOne
    {
        return $this->hasOne(LeadQueueState::class, 'lead_id', 'lead_id');
    }

    public function queueActionHistories(): HasMany
    {
        return $this->hasMany(LeadQueueActionHistory::class, 'snapshot_id');
    }
}
