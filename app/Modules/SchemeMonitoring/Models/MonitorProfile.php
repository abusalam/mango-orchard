<?php

declare(strict_types=1);

namespace App\Modules\SchemeMonitoring\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One row per user enrolled in the scheme/project monitoring module. The
 * enrolment is the gate for visibility — having the `monitor` role grants
 * access to the module, but `parent_user_id` here decides whose tasks the
 * user can see (own + entire subtree underneath).
 */
class MonitorProfile extends Model
{
    use HasFactory;

    protected $table = 'monitoring_profiles';

    protected $fillable = ['user_id', 'parent_user_id'];

    protected static function newFactory(): \Database\Factories\SchemeMonitoring\MonitorProfileFactory
    {
        return \Database\Factories\SchemeMonitoring\MonitorProfileFactory::new();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'parent_user_id');
    }
}
