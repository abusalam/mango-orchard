<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * Editable email-template row. Notifications look up their template by
 * `key` and substitute `{placeholders}` at render time via
 * {@see \App\Mail\EmailTemplateRenderer}.
 *
 * Cached per key so the hot path on every queued notification is a
 * cache hit, not a SELECT. Cache is wiped on save.
 */
class EmailTemplate extends Model
{
    protected $table = 'email_templates';

    protected $fillable = ['key', 'name', 'description', 'subject', 'body'];

    public static function forKey(string $key): ?self
    {
        return Cache::rememberForever('email-template:'.$key, fn () => self::where('key', $key)->first());
    }

    protected static function booted(): void
    {
        static::saved(fn (self $t) => Cache::forget('email-template:'.$t->key));
        static::deleted(fn (self $t) => Cache::forget('email-template:'.$t->key));
    }
}
