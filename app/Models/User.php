<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Modules\MangoOrchard\Models\Listing;
use App\Modules\MangoOrchard\Models\MangoVariety;
use App\Modules\SchemeMonitoring\Models\Designation;
use App\Modules\SchemeMonitoring\Models\MonitorProfile;
use App\Modules\SchemeMonitoring\Models\Scheme;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
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
    'avatar_path',
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
            'deactivated_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        // Hard-deleting a user (admin action) must clean owned disk state.
        // Listings cascade at the DB level, which would BYPASS each
        // listing's own deleting hook (the one that wipes its image blob) —
        // so iterate them through Eloquent first. The avatar is wiped here
        // directly.
        static::deleting(function (User $user): void {
            $user->listings->each->delete();

            // Owned monitoring schemes would otherwise die via DB cascade,
            // skipping the Scheme/Task hooks that wipe attachment blobs.
            Scheme::query()
                ->where('owner_id', $user->id)
                ->get()
                ->each
                ->delete();

            if ($user->avatar_path && Storage::disk('public')->exists($user->avatar_path)) {
                Storage::disk('public')->delete($user->avatar_path);
            }
        });
    }

    public function isDeactivated(): bool
    {
        return $this->deactivated_at !== null;
    }

    public function getAvatarUrlAttribute(): ?string
    {
        return $this->avatar_path
            ? Storage::disk('public')->url($this->avatar_path)
            : null;
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

    public function designations(): BelongsToMany
    {
        return $this->belongsToMany(
            Designation::class,
            'monitoring_user_designations',
        )->withTimestamps();
    }

    public function monitoringProfile(): HasOne
    {
        return $this->hasOne(MonitorProfile::class);
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
