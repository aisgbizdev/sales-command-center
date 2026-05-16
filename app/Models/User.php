<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public const ROLE_SUPER_ADMIN = 'super_admin';
    public const ROLE_KEPALA = 'kepala';
    public const ROLE_MANAGER = 'manager';
    public const ROLE_PENJUALAN = 'penjualan';
    public const ROLE_HEAD = self::ROLE_KEPALA;
    public const ROLE_SALES = self::ROLE_PENJUALAN;
    public const ROLES = [
        self::ROLE_SUPER_ADMIN,
        self::ROLE_KEPALA,
        self::ROLE_MANAGER,
        self::ROLE_PENJUALAN,
    ];

    public const ROLE_LABELS = [
        self::ROLE_SUPER_ADMIN => 'Super Admin',
        self::ROLE_KEPALA => 'Head',
        self::ROLE_MANAGER => 'Manager',
        self::ROLE_PENJUALAN => 'Sales',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'role',
        'unit_id',
        'team_id',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
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

    public function prospects(): HasMany
    {
        return $this->hasMany(Prospect::class, 'owner_id');
    }

    public function prospectLogs(): HasMany
    {
        return $this->hasMany(ProspectLog::class);
    }

    public function whatsAppConversations(): HasMany
    {
        return $this->hasMany(WhatsAppConversation::class, 'owner_id');
    }

    public function chatReviews(): HasMany
    {
        return $this->hasMany(ChatReview::class, 'submitted_by');
    }

    public function managerReviewNotes(): HasMany
    {
        return $this->hasMany(ManagerReviewNote::class, 'reviewed_by');
    }

    public function requestedKnowledgeUpdates(): HasMany
    {
        return $this->hasMany(KnowledgeUpdateQueue::class, 'requested_by');
    }

    public function reviewedKnowledgeUpdates(): HasMany
    {
        return $this->hasMany(KnowledgeUpdateQueue::class, 'reviewed_by');
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === self::ROLE_SUPER_ADMIN;
    }

    public function isKepala(): bool
    {
        return $this->role === self::ROLE_KEPALA;
    }

    public function isHead(): bool
    {
        return $this->isKepala();
    }

    public function isManager(): bool
    {
        return $this->role === self::ROLE_MANAGER;
    }

    public function isPenjualan(): bool
    {
        return $this->role === self::ROLE_PENJUALAN;
    }

    public function isSales(): bool
    {
        return $this->isPenjualan();
    }

    public function roleLabel(): string
    {
        static $labels = null;

        if ($labels === null) {
            try {
                $labels = Role::query()->pluck('label', 'code')->all();
            } catch (\Throwable) {
                $labels = [];
            }
        }

        return $labels[$this->role] ?? self::ROLE_LABELS[$this->role] ?? ucfirst(str_replace('_', ' ', $this->role));
    }

    public function canCreateProspect(): bool
    {
        return $this->isSuperAdmin() || $this->isPenjualan();
    }

    public function canEditProspect(?Prospect $prospect = null): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        if ($this->isManager()) {
            return $prospect ? $prospect->team_id === $this->team_id : false;
        }

        if ($this->isPenjualan()) {
            return $prospect ? $prospect->owner_id === $this->id : true;
        }

        return false;
    }

    public function canDeleteProspect(?Prospect $prospect = null): bool
    {
        return $this->isSuperAdmin();
    }

    public function canCreateChatReview(): bool
    {
        return $this->isSuperAdmin() || $this->isManager() || $this->isKepala();
    }

    public function canEditChatReview(?ChatReview $review = null): bool
    {
        return $this->isSuperAdmin();
    }

    public function canAddReviewNotes(): bool
    {
        return $this->isSuperAdmin() || $this->isKepala() || $this->isManager();
    }

    public function canViewKnowledgeQueue(): bool
    {
        return $this->isSuperAdmin() || $this->isKepala();
    }

    public function canApproveKnowledge(): bool
    {
        return $this->isSuperAdmin();
    }
}
