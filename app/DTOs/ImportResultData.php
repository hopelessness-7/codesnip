<?php

namespace App\DTOs;

use App\DTOs\BaseDTO;

class ImportResultData extends BaseDTO
{
    public function __construct(
        public int $created = 0,
        public int $updated = 0,
        public int $skipped = 0,
        public int $failed = 0,
        /** @var array<int,array{index?:int,title?:string,reason:string}> */
        public array $errors = [],
        /** @var array<int,int> */
        public array $importedIds = []
    ) {}

    public static function fromArray(array $data): static
    {
        return new self(
            created: $data['created'] ?? 0,
            updated: $data['updated'] ?? 0,
            skipped: $data['skipped'] ?? 0,
            failed: $data['failed'] ?? 0,
            errors: $data['errors'] ?? [],
            importedIds: $data['imported_ids'] ?? []
        );
    }

    public function toArray(): array
    {
        return [
            'created' => $this->created,
            'updated' => $this->updated,
            'skipped' => $this->skipped,
            'failed' => $this->failed,
            'errors' => $this->errors,
            'imported_ids' => $this->importedIds,
        ];
    }

    public function addCreated(int $id): void
    {
        $this->created++;
        $this->importedIds[] = $id;
    }

    public function addUpdated(int $id): void
    {
        $this->updated++;
        $this->importedIds[] = $id;
    }

    public function addSkipped(): void
    {
        $this->skipped++;
    }

    public function addError(string $reason, ?int $index = null, ?string $title = null): void
    {
        $this->failed++;
        $error = ['reason' => $reason];
        if ($index !== null) {
            $error['index'] = $index;
        }
        if ($title !== null && $title !== '') {
            $error['title'] = $title;
        }
        $this->errors[] = $error;
    }
}
