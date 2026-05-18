<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProspectLog extends Model
{
    public const TYPES = ['call', 'visit', 'follow_up', 'presentation', 'lainnya'];
    public const OBJECTION_TYPES = [
        'takut_risiko',
        'tidak_percaya',
        'belum_siap_modal',
        'membandingkan',
        'tidak_paham',
        'tidak_respon',
        'ingin_bukti',
        'terlalu_mahal',
        'salah_target',
        'lainnya',
    ];
    public const OBJECTION_TYPE_LABELS = [
        'takut_risiko' => 'Takut Risiko',
        'tidak_percaya' => 'Tidak Percaya',
        'belum_siap_modal' => 'Belum Siap Modal',
        'membandingkan' => 'Membandingkan',
        'tidak_paham' => 'Tidak Paham',
        'tidak_respon' => 'Tidak Respon',
        'ingin_bukti' => 'Ingin Bukti',
        'terlalu_mahal' => 'Terlalu Mahal',
        'salah_target' => 'Salah Target',
        'lainnya' => 'Lainnya',
    ];
    public const EMOTIONAL_STATES = ['takut', 'ragu', 'kritis', 'marah', 'tertarik', 'siap'];
    public const EMOTIONAL_STATE_LABELS = [
        'takut' => 'Takut',
        'ragu' => 'Ragu',
        'kritis' => 'Kritis',
        'marah' => 'Marah',
        'tertarik' => 'Tertarik',
        'siap' => 'Siap',
    ];
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
        'objection_type',
        'objection_detail',
        'emotional_state',
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

    protected static function booted(): void
    {
        static::created(function (ProspectLog $log) {
            $log->prospect?->updateQuietly([
                'last_activity_at' => now(),
            ]);
        });
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
