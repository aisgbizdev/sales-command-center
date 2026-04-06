<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ManagerReviewNote extends Model
{
    protected $fillable = [
        'note',
        'tag',
        'chat_review_id',
        'reviewed_by',
    ];

    public function chatReview(): BelongsTo
    {
        return $this->belongsTo(ChatReview::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
