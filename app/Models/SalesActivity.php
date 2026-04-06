<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalesActivity extends Model
{
    public const TYPES = ['call', 'visit', 'follow_up', 'closing', 'other'];

    /**
     * @var list<string>
     */
    protected $fillable = [
        'activity_date',
        'activity_type',
        'summary',
        'outcome',
        'next_follow_up_date',
        'customer_id',
        'sales_user_id',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'activity_date' => 'date',
            'next_follow_up_date' => 'date',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function salesUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sales_user_id');
    }
}
