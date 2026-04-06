<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProspectLog extends Model
{
    public const TYPES = ['call', 'visit', 'follow_up', 'presentation', 'lainnya'];
    public const CHAT_OUTCOME_TYPES = ['reply', 'ongoing', 'closing', 'lost', 'bridge_signal', 'objection_new'];
    public const CHAT_OUTCOME_TYPE_LABELS = [
        'reply' => 'Reply',
        'ongoing' => 'Ongoing',
        'closing' => 'Closing',
        'lost' => 'Lost',
        'bridge_signal' => 'Bridge Signal',
        'objection_new' => 'Objection Baru',
    ];

    protected $fillable = [
        'log_date',
        'activity_type',
        'summary',
        'result',
        'gpt_used',
        'gpt_mode',
        'chat_outcome_type',
        'objection_snapshot',
        'next_follow_up_date',
        'prospect_id',
        'user_id',
    ];

    protected function casts(): array
    {
        return [
            'log_date' => 'date',
            'next_follow_up_date' => 'date',
            'gpt_used' => 'boolean',
        ];
    }

    public function prospect(): BelongsTo
    {
        return $this->belongsTo(Prospect::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
