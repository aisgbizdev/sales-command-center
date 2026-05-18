<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WhatsAppWebhookEvent extends Model
{
    protected $table = 'whatsapp_webhook_events';

    protected $fillable = [
        'payload',
        'event_type',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
        ];
    }
}
