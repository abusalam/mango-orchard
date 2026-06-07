<?php

declare(strict_types=1);

namespace App\Modules\SchemeMonitoring\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Designation extends Model
{
    use HasFactory;

    protected $table = 'monitoring_designations';

    protected $fillable = ['name', 'description', 'level', 'parent_id'];

    protected $casts = [
        'level' => 'integer',
        'parent_id' => 'integer',
    ];

    protected static function newFactory(): \Database\Factories\SchemeMonitoring\DesignationFactory
    {
        return \Database\Factories\SchemeMonitoring\DesignationFactory::new();
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'monitoring_user_designations')
            ->withTimestamps();
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    /**
     * Collect this designation's id plus every transitive descendant via
     * `parent_id`. Cycle-safe via a seen map.
     *
     * @return list<int>
     */
    public function descendantIds(): array
    {
        $byParent = self::query()
            ->whereNotNull('parent_id')
            ->get(['id', 'parent_id'])
            ->groupBy('parent_id');

        $stack = [$this->id];
        $seen = [$this->id => true];
        $out = [$this->id];

        while ($stack !== []) {
            $current = array_pop($stack);
            foreach ($byParent->get($current, collect()) as $row) {
                $childId = (int) $row->id;
                if (isset($seen[$childId])) {
                    continue;
                }
                $seen[$childId] = true;
                $out[] = $childId;
                $stack[] = $childId;
            }
        }

        return $out;
    }
}
