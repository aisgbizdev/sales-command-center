<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiRequest extends Model
{
    protected $fillable = [
        'request_id',
        'lead_id',
        'conversation_id',
        'trigger_type',
        'status',
        'attempt_count',
        'payload_hash',
        'last_error',
        'sent_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'sent_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Prospect::class, 'lead_id');
    }
}

