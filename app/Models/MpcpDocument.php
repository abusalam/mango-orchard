<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MpcpDocument extends Model
{
    protected $fillable = [
        'title_en',
        'title_bn',
        'attribution_md_en',
        'attribution_md_bn',
        'about_md_en',
        'about_md_bn',
        'footer_md_en',
        'footer_md_bn',
        'website_url',
    ];

    /**
     * Single-row helper. Creates a default row if none exists so callers
     * never see null. The first row wins permanently; admin edits it in place.
     */
    public static function current(): self
    {
        return static::firstOrCreate(
            ['id' => 1],
            ['title_en' => 'Mango Promotion Communication Plan (MPCP)'],
        );
    }
}
