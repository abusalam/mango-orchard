<?php

declare(strict_types=1);

namespace App\Modules\MangoOrchard\Models;

use App\Modules\MangoOrchard\Observers\MangoVarietyTelemetryObserver;
use Database\Factories\MangoVarietyFactory;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

#[ObservedBy([MangoVarietyTelemetryObserver::class])]
class MangoVariety extends Model
{
    /** @use HasFactory<MangoVarietyFactory> */
    use HasFactory;

    protected static function newFactory(): \Database\Factories\MangoVarietyFactory
    {
        return \Database\Factories\MangoVarietyFactory::new();
    }

    protected $fillable = [
        'name',
        'slug',
        'origin',
        'season',
        'season_start',
        'season_end',
        'flavor',
        'tags',
        'theme',
        'image_path',
    ];

    protected $casts = [
        'tags' => 'array',
        'season_start' => 'integer',
        'season_end' => 'integer',
    ];

    public const THEMES = [
        'sunrise' => [
            'label' => 'Sunrise',
            'gradient' => 'from-yellow-300 via-orange-500 to-rose-600',
            'accent' => 'bg-rose-100 text-rose-900',
        ],
        'amber' => [
            'label' => 'Amber',
            'gradient' => 'from-amber-200 via-orange-400 to-amber-700',
            'accent' => 'bg-amber-100 text-amber-900',
        ],
        'honey' => [
            'label' => 'Honey',
            'gradient' => 'from-yellow-200 via-yellow-400 to-orange-500',
            'accent' => 'bg-yellow-100 text-yellow-900',
        ],
        'lime' => [
            'label' => 'Lime',
            'gradient' => 'from-lime-400 via-orange-500 to-rose-600',
            'accent' => 'bg-lime-100 text-lime-900',
        ],
        'rose' => [
            'label' => 'Rose',
            'gradient' => 'from-amber-300 via-rose-400 to-rose-700',
            'accent' => 'bg-rose-100 text-rose-900',
        ],
        'emerald' => [
            'label' => 'Emerald',
            'gradient' => 'from-lime-300 via-green-500 to-emerald-700',
            'accent' => 'bg-emerald-100 text-emerald-900',
        ],
        'kent' => [
            'label' => 'Kent',
            'gradient' => 'from-amber-300 via-orange-500 to-rose-500',
            'accent' => 'bg-orange-100 text-orange-900',
        ],
        'carabao' => [
            'label' => 'Carabao',
            'gradient' => 'from-yellow-200 via-amber-400 to-orange-600',
            'accent' => 'bg-yellow-100 text-yellow-900',
        ],
        'green' => [
            'label' => 'Green',
            'gradient' => 'from-lime-300 via-green-400 to-amber-500',
            'accent' => 'bg-green-100 text-green-900',
        ],
        'dasheri' => [
            'label' => 'Dasheri',
            'gradient' => 'from-yellow-200 via-lime-400 to-amber-500',
            'accent' => 'bg-lime-100 text-lime-900',
        ],
    ];

    protected static function booted(): void
    {
        static::saving(function (self $variety): void {
            if (blank($variety->slug) && filled($variety->name)) {
                $variety->slug = Str::slug($variety->name);
            }
        });

        // Wipe the uploaded image from disk when the variety is deleted.
        static::deleting(function (self $variety): void {
            if ($variety->image_path && Storage::disk('public')->exists($variety->image_path)) {
                Storage::disk('public')->delete($variety->image_path);
            }
        });
    }

    public function getImageUrlAttribute(): ?string
    {
        return $this->image_path
            ? Storage::disk('public')->url($this->image_path)
            : null;
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function getThemeStylesAttribute(): array
    {
        return self::THEMES[$this->theme] ?? self::THEMES['sunrise'];
    }

    public function getGradientClassesAttribute(): string
    {
        return $this->theme_styles['gradient'];
    }

    public function getAccentClassesAttribute(): string
    {
        return $this->theme_styles['accent'];
    }
}
