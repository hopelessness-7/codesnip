<?php

namespace App\Livewire\Concerns;

trait ParsesSnippetTags
{
    /**
     * @return list<string>
     */
    protected function parseTagsFromInput(string $input): array
    {
        $parts = preg_split('/[,;\n]+/', $input, -1, PREG_SPLIT_NO_EMPTY) ?: [];

        return array_values(array_unique(array_filter(array_map(
            fn (string $t) => mb_strtolower(trim($t)),
            $parts
        ))));
    }
}
