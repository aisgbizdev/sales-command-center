<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalesOrder extends Model
{
    public const STATUSES = ['draft', 'submitted', 'approved', 'rejected', 'fulfilled'];

    /**
     * @var list<string>
     */
    protected $fillable = [
        'order_number',
        'order_date',
        'status',
        'total_amount',
        'notes',
        'customer_id',
        'sales_user_id',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'order_date' => 'date',
            'total_amount' => 'decimal:2',
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
