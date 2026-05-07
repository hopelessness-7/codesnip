<?php

namespace App\DTOs;

final readonly class TagFilters
{
    public function __construct(
        public string  $query = '',
        public ?string $category = null,
        public ?bool   $isAiGenerated = null,
        public string  $sortBy = 'usage_count',
        public string  $sortDirection = 'desc',
        public int     $perPage = 50,
    ) {}
}
