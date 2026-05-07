<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class SmartCollection extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'filters_json',
        'is_system',
    ];

    protected function casts(): array
    {
        return [
            'filters_json' => 'array',
            'is_system' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function snippets(): BelongsToMany
    {
        return $this->belongsToMany(Snippet::class);
    }
}
