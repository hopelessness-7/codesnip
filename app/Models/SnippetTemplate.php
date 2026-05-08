<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SnippetTemplate extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'description',
        'title_template',
        'code_template',
        'language',
        'default_tags_json',
        'is_favorite',
    ];

    protected function casts(): array
    {
        return [
            'default_tags_json' => 'array',
            'is_favorite' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
