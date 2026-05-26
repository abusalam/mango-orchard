<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\RoleDelegationFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Permission\Models\Role;

class RoleDelegation extends Model
{
    /** @use HasFactory<RoleDelegationFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'role_id',
        'delegated_by',
        'delegated_at',
        'revoked_by',
        'revoked_at',
        'revoke_reason',
    ];

    protected $casts = [
        'delegated_at' => 'datetime',
        'revoked_at' => 'datetime',
    ];

    public function recipient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function delegator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'delegated_by');
    }

    public function revoker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'revoked_by');
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNull('revoked_at');
    }

    public function scopeRevoked(Builder $query): Builder
    {
        return $query->whereNotNull('revoked_at');
    }

    public function isActive(): bool
    {
        return $this->revoked_at === null;
    }
}
