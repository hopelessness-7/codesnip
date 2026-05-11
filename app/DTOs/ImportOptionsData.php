<?php

namespace App\DTOs;

use App\DTOs\BaseDTO;

class ImportOptionsData extends BaseDTO
{
    public function __construct(
        public string $onDuplicate = 'skip',
        public bool $dryRun = false,
        public bool $preserveUuid = true,
        public ?bool $defaultIsPublic = null
    ) {}

    public static function fromArray(array $data): static
    {
        $onDuplicate = (string) ($data['on_duplicate'] ?? $data['onDuplicate'] ?? 'skip');

        return new self(
            onDuplicate: self::validateOnDuplicate($onDuplicate),
            dryRun: (bool) ($data['dry_run'] ?? $data['dryRun'] ?? false),
            preserveUuid: (bool) ($data['preserve_uuid'] ?? $data['preserveUuid'] ?? true),
            defaultIsPublic: array_key_exists('default_is_public', $data)
                ? (bool) $data['default_is_public']
                : (array_key_exists('defaultIsPublic', $data) ? (bool) $data['defaultIsPublic'] : null),
        );
    }

    public function toArray(): array
    {
        return [
            'on_duplicate' => $this->onDuplicate,
            'dry_run' => $this->dryRun,
            'preserve_uuid' => $this->preserveUuid,
            'default_is_public' => $this->defaultIsPublic,
        ];
    }

    private static function validateOnDuplicate(string $onDuplicate): string
    {
        if (! in_array($onDuplicate, ['skip', 'update', 'create_copy'], true)) {
            throw new \InvalidArgumentException(
                "Invalid on_duplicate value: {$onDuplicate}. Allowed: skip, update, create_copy"
            );
        }

        return $onDuplicate;
    }
}
