<?php

namespace App\DTOs;

use App\DTOs\BaseDTO;
use Illuminate\Support\Collection;

class SnippetArchiveEnvelopeData extends BaseDTO
{
    public function __construct(
        public int $export_version = 1,
        public string $exported_at = '',
        /** @var array<int,array<string,mixed>> */
        public array $items = []
    ) {}

    public static function fromArray(array $data): static
    {
        return new self(
            export_version: $data['export_version'] ?? 1,
            exported_at: $data['exported_at'] ?? now()->toIso8601String(),
            items: $data['items'] ?? []
        );
    }

    public function toArray(): array
    {
        return [
            'export_version' => $this->export_version,
            'exported_at' => $this->exported_at,
            'items' => $this->items,
        ];
    }

    public static function fromItems(Collection|array $items, int $exportVersion = 1): self
    {
        $normalized = $items instanceof Collection ? $items->values()->all() : array_values($items);

        return new self(
            export_version: $exportVersion,
            exported_at: now()->toIso8601String(),
            items: $normalized
        );
    }
}
