<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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
        ];
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

    public function whatsAppConversations(): HasMany
    {
        return $this->hasMany(WhatsAppConversation::class);
    }

    public function whatsAppMessages(): HasMany
    {
        return $this->hasMany(WhatsAppMessage::class);
    }
}
