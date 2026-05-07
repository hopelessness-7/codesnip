<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SnippetRevision extends Model
{
    protected $fillable = [
        'snippet_id',
        'version',
        'title',
        'code',
        'language',
        'is_public',
        'tags_json',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'is_public' => 'boolean',
            'tags_json' => 'array',
        ];
    }

    public function snippet(): BelongsTo
    {
        return $this->belongsTo(Snippet::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
