<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MpcpSection extends Model
{
    protected $fillable = [
        'slug',
        'title_en',
        'title_bn',
        'intro_md_en',
        'intro_md_bn',
        'layout',
        'columns',
        'display_order',
        'published',
        'created_by',
    ];

    protected $casts = [
        'columns' => 'array',
        'published' => 'boolean',
        'display_order' => 'integer',
    ];

    public function entries(): HasMany
    {
        return $this->hasMany(MpcpEntry::class)->orderBy('position');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
