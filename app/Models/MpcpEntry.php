<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MpcpEntry extends Model
{
    protected $fillable = [
        'mpcp_section_id',
        'data',
        'position',
    ];

    protected $casts = [
        'data' => 'array',
        'position' => 'integer',
    ];

    public function section(): BelongsTo
    {
        return $this->belongsTo(MpcpSection::class, 'mpcp_section_id');
    }
}
