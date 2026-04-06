<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Team extends Model
{
    protected $fillable = ['name', 'code', 'unit_id'];

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function prospects(): HasMany
    {
        return $this->hasMany(Prospect::class);
    }
}
