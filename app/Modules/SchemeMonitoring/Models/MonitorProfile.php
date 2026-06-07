<?php

declare(strict_types=1);

namespace App\Modules\SchemeMonitoring\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Enrolment row — one per user inside the monitoring module. Holds nothing
 * but the user reference; visibility flows through the designation
 * hierarchy (see {@see \App\Modules\SchemeMonitoring\Hierarchy}). Kept as
 * a separate row so an admin can revoke module access without dropping the
 * user's designations.
 */
class MonitorProfile extends Model
{
    use HasFactory;

    protected $table = 'monitoring_profiles';

    protected $fillable = ['user_id'];

    protected static function newFactory(): \Database\Factories\SchemeMonitoring\MonitorProfileFactory
    {
        return \Database\Factories\SchemeMonitoring\MonitorProfileFactory::new();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
