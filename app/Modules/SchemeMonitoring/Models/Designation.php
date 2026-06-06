<?php

declare(strict_types=1);

namespace App\Modules\SchemeMonitoring\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Designation extends Model
{
    use HasFactory;

    protected $table = 'monitoring_designations';

    protected $fillable = ['name', 'description', 'level'];

    protected $casts = [
        'level' => 'integer',
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
}
