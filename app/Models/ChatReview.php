<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ChatReview extends Model
{
    public const OUTCOMES = ['berhasil', 'gagal', 'netral'];
    public const STATUSES = ['draft', 'in_review', 'queued_for_approval', 'approved', 'rejected'];
    public const CHANNELS = ['whatsapp', 'email', 'telepon', 'tatap_muka', 'lainnya'];

    protected $fillable = [
        'title',
        'channel',
        'customer_name',
        'customer_company',
        'outcome',
        'status',
        'chat_summary',
        'chat_excerpt',
        'what_worked',
        'what_failed',
        'suggested_knowledge_update',
        'prospect_id',
        'submitted_by',
    ];

    public function prospect(): BelongsTo
    {
        return $this->belongsTo(Prospect::class);
    }

    public function submitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function managerNotes(): HasMany
    {
        return $this->hasMany(ManagerReviewNote::class);
    }

    public function knowledgeQueues(): HasMany
    {
        return $this->hasMany(KnowledgeUpdateQueue::class);
    }
}
