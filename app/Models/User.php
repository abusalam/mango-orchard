<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Modules\MangoOrchard\Models\Listing;
use App\Modules\MangoOrchard\Models\MangoVariety;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

#[Fillable([
    'name',
    'email',
    'password',
    'region',
    'expertise',
    'favorite_variety_id',
    'notify_seasonal',
    'subscribe_newsletter',
    'onboarding_completed_at',
])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable;

    public const EXPERTISE_LEVELS = [
        'beginner' => 'Curious beginner',
        'enthusiast' => 'Enthusiast',
        'grower' => 'Backyard grower',
        'professional' => 'Professional / trade',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'notify_seasonal' => 'boolean',
            'subscribe_newsletter' => 'boolean',
            'onboarding_completed_at' => 'datetime',
        ];
    }

    public function favoriteVariety(): BelongsTo
    {
        return $this->belongsTo(MangoVariety::class, 'favorite_variety_id');
    }

    public function listings(): HasMany
    {
        return $this->hasMany(Listing::class);
    }

    public function roleApplications(): HasMany
    {
        return $this->hasMany(RoleApplication::class);
    }

    public function designations(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(
            \App\Modules\SchemeMonitoring\Models\Designation::class,
            'monitoring_user_designations',
        )->withTimestamps();
    }

    public function monitoringProfile(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(\App\Modules\SchemeMonitoring\Models\MonitorProfile::class);
    }

    public function hasCompletedOnboarding(): bool
    {
        return $this->onboarding_completed_at !== null;
    }

    public function currentOnboardingStep(): string
    {
        if (blank($this->region) || blank($this->expertise)) {
            return 'profile';
        }

        return 'preferences';
    }
}
