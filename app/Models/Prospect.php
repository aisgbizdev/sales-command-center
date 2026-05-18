<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Prospect extends Model
{
    public const STATUS_BARU = 'baru';
    public const STATUS_DIHUBUNGI = 'dihubungi';
    public const STATUS_DIBALAS = 'dibalas';
    public const STATUS_SEDANG_BERJALAN = 'sedang_berjalan';
    public const STATUS_TINDAK_LANJUT = 'tindak_lanjut';
    public const STATUS_PENUTUPAN = 'penutupan';
    public const STATUS_HILANG = 'hilang';

    public const STATUSES = [
        self::STATUS_BARU,
        self::STATUS_DIHUBUNGI,
        self::STATUS_DIBALAS,
        self::STATUS_SEDANG_BERJALAN,
        self::STATUS_TINDAK_LANJUT,
        self::STATUS_PENUTUPAN,
        self::STATUS_HILANG,
    ];

    public const STATUS_LABELS = [
        self::STATUS_BARU => 'Baru',
        self::STATUS_DIHUBUNGI => 'Dihubungi',
        self::STATUS_DIBALAS => 'Dibalas',
        self::STATUS_SEDANG_BERJALAN => 'Sedang Berjalan',
        self::STATUS_TINDAK_LANJUT => 'Tindak Lanjut',
        self::STATUS_PENUTUPAN => 'Penutupan',
        self::STATUS_HILANG => 'Hilang',
    ];
    public const ACCOUNT_CATEGORIES = ['mini', 'reguler'];
    public const ACCOUNT_CATEGORY_LABELS = [
        'mini' => 'Mini',
        'reguler' => 'Reguler',
    ];
    public const USER_TEMPERATURES = ['cold', 'warm', 'hot'];
    public const USER_TEMPERATURE_LABELS = [
        'cold' => 'Cold',
        'warm' => 'Warm',
        'hot' => 'Hot',
    ];
    public const DOMINANT_EMOTIONS = ['takut', 'ragu', 'kritis', 'marah', 'tertarik', 'siap', 'netral'];
    public const DOMINANT_EMOTION_LABELS = [
        'takut' => 'Takut',
        'ragu' => 'Ragu',
        'kritis' => 'Kritis',
        'marah' => 'Marah',
        'tertarik' => 'Tertarik',
        'siap' => 'Siap',
        'netral' => 'Netral',
    ];
    public const GPT_MODES = ['mini', 'regular'];
    public const GPT_MODE_LABELS = [
        'mini' => 'Mini',
        'regular' => 'Regular',
    ];
    public const BRIDGE_STATUSES = ['none', 'identified', 'offered', 'moved'];
    public const BRIDGE_STATUS_LABELS = [
        'none' => 'None',
        'identified' => 'Identified',
        'offered' => 'Offered',
        'moved' => 'Moved',
    ];
    public const LOST_REASONS = [
        'tidak_respon',
        'tidak_tertarik',
        'takut_risiko',
        'tidak_percaya',
        'tidak_siap_modal',
        'membandingkan_tempat_lain',
        'follow_up_gagal',
        'salah_target',
        'alasan_lain',
    ];
    public const LOST_REASON_LABELS = [
        'tidak_respon' => 'Tidak Respon',
        'tidak_tertarik' => 'Tidak Tertarik',
        'takut_risiko' => 'Takut Risiko',
        'tidak_percaya' => 'Tidak Percaya',
        'tidak_siap_modal' => 'Tidak Siap Modal',
        'membandingkan_tempat_lain' => 'Membandingkan dengan Tempat Lain',
        'follow_up_gagal' => 'Follow Up Gagal',
        'salah_target' => 'Salah Target',
        'alasan_lain' => 'Alasan Lain',
    ];
    public const SOURCES = ['referensi', 'iklan', 'walkin', 'sosial_media', 'lainnya'];
    public const FOLLOW_UP_STATE_OVERDUE = 'overdue';
    public const FOLLOW_UP_STATE_TODAY = 'today';
    public const FOLLOW_UP_STATE_SOON = 'soon';
    public const FOLLOW_UP_STATE_HEALTHY = 'healthy';
    public const FOLLOW_UP_STATE_NONE = 'none';

    public const PRIORITY_LEVEL_CRITICAL = 'critical';
    public const PRIORITY_LEVEL_HIGH = 'high';
    public const PRIORITY_LEVEL_MEDIUM = 'medium';
    public const PRIORITY_LEVEL_NORMAL = 'normal';

    protected $fillable = [
        'prospect_code',
        'name',
        'company',
        'phone',
        'email',
        'source',
        'account_category',
        'user_temperature',
        'dominant_emotion',
        'main_objection',
        'gpt_mode',
        'bridge_candidate',
        'bridge_status',
        'lost_reason',
        'last_contact_at',
        'status',
        'status_updated_at',
        'last_activity_at',
        'priority',
        'estimation_value',
        'next_follow_up_date',
        'notes',
        'unit_id',
        'team_id',
        'owner_id',
    ];

    protected function casts(): array
    {
        return [
            'next_follow_up_date' => 'date',
            'estimation_value' => 'decimal:2',
            'bridge_candidate' => 'boolean',
            'last_contact_at' => 'datetime',
            'status_updated_at' => 'datetime',
            'last_activity_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Prospect $prospect) {
            $now = now();

            $prospect->status_updated_at ??= $now;
            $prospect->last_activity_at ??= $now;
        });

        static::saving(function (Prospect $prospect) {
            if (! $prospect->exists) {
                return;
            }

            $now = now();

            if ($prospect->isDirty('status')) {
                $prospect->status_updated_at = $now;
            }

            if ($prospect->isDirty()) {
                $prospect->last_activity_at = $now;
            }
        });
    }

    protected function agingDays(): Attribute
    {
        return Attribute::get(function (): int {
            $statusUpdatedAt = $this->status_updated_at ?? $this->created_at;

            if (! $statusUpdatedAt) {
                return 0;
            }

            return (int) $statusUpdatedAt->copy()->startOfDay()->diffInDays(now()->startOfDay());
        });
    }

    protected function lastActivityDiff(): Attribute
    {
        return Attribute::get(function (): string {
            $lastActivityAt = $this->last_activity_at ?? $this->updated_at;

            if (! $lastActivityAt) {
                return '-';
            }

            return $lastActivityAt->copy()->locale('id')->diffForHumans();
        });
    }

    protected function isStale(): Attribute
    {
        return Attribute::get(function (): bool {
            if (in_array($this->status, [self::STATUS_PENUTUPAN, self::STATUS_HILANG], true)) {
                return false;
            }

            $lastActivityAt = $this->last_activity_at ?? $this->updated_at;

            if (! $lastActivityAt) {
                return false;
            }

            return $lastActivityAt->lt(now()->subDays(3));
        });
    }

    protected function followUpState(): Attribute
    {
        return Attribute::get(function (): string {
            if (! $this->next_follow_up_date || in_array($this->status, [self::STATUS_PENUTUPAN, self::STATUS_HILANG], true)) {
                return self::FOLLOW_UP_STATE_NONE;
            }

            $today = now()->startOfDay();
            $followUpDate = $this->next_follow_up_date->copy()->startOfDay();

            if ($followUpDate->lt($today)) {
                return self::FOLLOW_UP_STATE_OVERDUE;
            }

            if ($followUpDate->isSameDay($today)) {
                return self::FOLLOW_UP_STATE_TODAY;
            }

            if ($followUpDate->between($today->copy()->addDay(), $today->copy()->addDays(2), true)) {
                return self::FOLLOW_UP_STATE_SOON;
            }

            return self::FOLLOW_UP_STATE_HEALTHY;
        });
    }

    protected function overdueDays(): Attribute
    {
        return Attribute::get(function (): int {
            if ($this->follow_up_state !== self::FOLLOW_UP_STATE_OVERDUE || ! $this->next_follow_up_date) {
                return 0;
            }

            return (int) $this->next_follow_up_date->copy()->startOfDay()->diffInDays(now()->startOfDay());
        });
    }

    protected function priorityLevel(): Attribute
    {
        return Attribute::get(function (): string {
            if (
                $this->overdue_days > 2
                || ($this->is_stale && $this->follow_up_state === self::FOLLOW_UP_STATE_OVERDUE)
            ) {
                return self::PRIORITY_LEVEL_CRITICAL;
            }

            if (in_array($this->follow_up_state, [self::FOLLOW_UP_STATE_OVERDUE, self::FOLLOW_UP_STATE_TODAY], true)) {
                return self::PRIORITY_LEVEL_HIGH;
            }

            if ($this->follow_up_state === self::FOLLOW_UP_STATE_SOON) {
                return self::PRIORITY_LEVEL_MEDIUM;
            }

            return self::PRIORITY_LEVEL_NORMAL;
        });
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(ProspectLog::class);
    }

    public function conversation(): HasOne
    {
        return $this->hasOne(Conversation::class)->where('channel', 'whatsapp');
    }

    public function whatsAppConversations(): HasMany
    {
        return $this->hasMany(WhatsAppConversation::class);
    }

    public function whatsAppMessages(): HasMany
    {
        return $this->hasMany(WhatsAppMessage::class);
    }
}
