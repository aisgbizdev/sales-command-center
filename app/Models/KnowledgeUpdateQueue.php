<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KnowledgeUpdateQueue extends Model
{
    public const PRIORITIES = ['low', 'normal', 'high', 'urgent'];
    public const STATUSES = ['queued', 'in_review', 'approved', 'rejected'];

    protected $fillable = [
        'priority',
        'status',
        'problem_pattern',
        'recommended_update',
        'expected_impact',
        'super_admin_note',
        'reviewed_at',
        'chat_review_id',
        'requested_by',
        'reviewed_by',
    ];

    protected function casts(): array
    {
        return [
            'reviewed_at' => 'datetime',
        ];
    }

    public function chatReview(): BelongsTo
    {
        return $this->belongsTo(ChatReview::class);
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
