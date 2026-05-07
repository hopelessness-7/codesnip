<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

class Tag extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'description',
        'category',
        'snippets_count',
        'is_ai_generated',
    ];

    protected $casts = [
        'snippets_count' => 'integer',
        'is_ai_generated' => 'boolean',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (Tag $tag) {
            if (empty($tag->slug)) {
                $tag->slug = Str::slug($tag->name);
            }
        });
    }

    public function snippets(): BelongsToMany
    {
        return $this->belongsToMany(Snippet::class);
    }

    public function scopePopular($query, int $limit = 20)
    {
        return $query->orderByDesc('snippets_count')->limit($limit);
    }

    public function scopeByName($query, string $name)
    {
        return $query->where('name', 'like', "%{$name}%");
    }

    public function scopeAiGenerated($query)
    {
        return $query->where('is_ai_generated', true);
    }

    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    public function incrementCount(): void
    {
        $this->increment('snippets_count');
    }

    public function decrementCount(): void
    {
        $this->decrement('snippets_count');
    }
}
