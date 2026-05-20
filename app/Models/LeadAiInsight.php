<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeadAiInsight extends Model
{
    protected $fillable = [
        'lead_id',
        'request_id',
        'lead_score',
        'temperature',
        'dominant_emotion',
        'top_objections',
        'next_best_actions',
        'bridge_recommendation',
        'risk_flags',
        'insight_version',
        'confidence',
        'generated_at',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'top_objections' => 'array',
            'next_best_actions' => 'array',
            'bridge_recommendation' => 'array',
            'risk_flags' => 'array',
            'generated_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Prospect::class, 'lead_id');
    }
}

