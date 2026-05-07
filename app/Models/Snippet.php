<?php

namespace App\Models;

use App\Enums\SnippetLanguage;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Laravel\Scout\Searchable;

class Snippet extends Model
{
    use SoftDeletes, Searchable;

    protected $fillable = [
        'user_id',
        'uuid',
        'title',
        'code',
        'language',
        'is_public',
    ];

    protected $casts = [
        'is_public' => 'boolean',
        'language' => 'string',
    ];
    protected function language(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value instanceof SnippetLanguage ? $value->value : $value,
        );
    }

    protected function languageEnum(): SnippetLanguage
    {
        $value = $this->getRawOriginal('language');

        return SnippetLanguage::tryFrom((string) $value) ?? SnippetLanguage::UNKNOWN;
    }


    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (Snippet $snippet) {
            if (empty($snippet->uuid)) {
                $snippet->uuid = (string) Str::uuid();
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class);
    }

    public function revisions(): HasMany
    {
        return $this->hasMany(SnippetRevision::class);
    }

    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }

    public function scopeByUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeByLanguage($query, ?string $language)
    {
        return $language ? $query->where('language', $language) : $query;
    }

    public function scopeByTag($query, string $tag)
    {
        return $query->whereHas('tags', function ($q) use ($tag) {
            $q->where('name', $tag)->orWhere('slug', $tag);
        });
    }

    public function getLanguageLabelAttribute(): string
    {
        return $this->languageEnum()->label();
    }

    public function getLanguageIconAttribute(): string
    {
        return $this->languageEnum()->icon();
    }

    public function getPrismClassAttribute(): string
    {
        return $this->languageEnum()->prismComponent();
    }

    public function getTruncatedCodeAttribute(int $limit = 200): string
    {
        return Str::limit($this->code, $limit);
    }

    public function getPublicUrlAttribute(): string
    {
        return route('snippets.publicOpen', ['uuid' => $this->uuid]);
    }

    public function isOwner(int $userId): bool
    {
        return $this->user_id === $userId;
    }

    /**
     * @return array<string, mixed>
     */
    public function toSearchableArray(): array
    {
        return [
            'title' => $this->title,
            'code' => $this->code,
            'language' => $this->language,
            'user_id' => $this->user_id,
            'is_public' => $this->is_public,
        ];
    }
}
