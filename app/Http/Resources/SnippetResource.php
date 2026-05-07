<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SnippetResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'code' => $this->code,
            'language' => $this->language,
            'tags' => $this->tags,
            'is_public' => $this->is_public,
            'uuid' => $this->is_public ? $this->uuid : null,
            'public_url' => $this->when($this->is_public, function () {
                return route('snippets.publicOpen', ['uuid' => $this->uuid]);
            }),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
