<?php

namespace App\Modules\Parser;

use App\Enums\SnippetLanguage;
use Illuminate\Support\Str;

class MassSnippetFileParser
{
    /**
     * @return array{uuid:string,title:string,code:string,language:?string,is_public:bool,tags:array<int,string>}
     */
    public function parseEntry(string $entryPath, string $content): array
    {
        $baseName = pathinfo($entryPath, PATHINFO_FILENAME);
        $extension = strtolower((string) pathinfo($entryPath, PATHINFO_EXTENSION));
        $parsed = $this->extractMarkdownFrontMatter($content);

        $metadata = $parsed['meta'];
        $body = $parsed['body'];

        $title = trim((string) ($metadata['title'] ?? $baseName));
        if ($title === '') {
            $title = 'Imported snippet';
        }

        $language = $this->resolveLanguage($extension, (string) ($metadata['language'] ?? ''));
        $tags = $this->resolveTags($metadata['tags'] ?? []);
        $isPublic = (bool) ($metadata['is_public'] ?? false);

        return [
            'uuid' => (string) Str::uuid(),
            'title' => $title,
            'code' => $body,
            'language' => $language,
            'is_public' => $isPublic,
            'tags' => $tags,
        ];
    }

    public function resolveExtensionForLanguage(?string $language): string
    {
        return match ($language) {
            SnippetLanguage::PHP->value => 'php',
            SnippetLanguage::JAVASCRIPT->value => 'js',
            SnippetLanguage::TYPESCRIPT->value => 'ts',
            SnippetLanguage::PYTHON->value => 'py',
            SnippetLanguage::SQL->value => 'sql',
            SnippetLanguage::HTML->value => 'html',
            SnippetLanguage::CSS->value => 'css',
            SnippetLanguage::SHELL->value => 'sh',
            SnippetLanguage::DOCKERFILE->value => 'dockerfile',
            SnippetLanguage::YAML->value => 'yml',
            SnippetLanguage::JSON->value => 'json',
            SnippetLanguage::MARKDOWN->value => 'md',
            default => 'txt',
        };
    }

    private function resolveLanguage(string $extension, string $frontMatterLanguage): ?string
    {
        if ($frontMatterLanguage !== '') {
            $fromFrontMatter = SnippetLanguage::tryFrom(strtolower($frontMatterLanguage));
            if ($fromFrontMatter !== null) {
                return $fromFrontMatter->value;
            }
        }

        return match ($extension) {
            'php' => SnippetLanguage::PHP->value,
            'js', 'mjs', 'cjs' => SnippetLanguage::JAVASCRIPT->value,
            'ts', 'tsx' => SnippetLanguage::TYPESCRIPT->value,
            'py' => SnippetLanguage::PYTHON->value,
            'sql' => SnippetLanguage::SQL->value,
            'html', 'htm' => SnippetLanguage::HTML->value,
            'css' => SnippetLanguage::CSS->value,
            'sh', 'bash', 'zsh' => SnippetLanguage::SHELL->value,
            'dockerfile' => SnippetLanguage::DOCKERFILE->value,
            'yml', 'yaml' => SnippetLanguage::YAML->value,
            'json' => SnippetLanguage::JSON->value,
            'md', 'markdown' => SnippetLanguage::MARKDOWN->value,
            default => SnippetLanguage::UNKNOWN->value,
        };
    }

    /**
     * @param  mixed  $tagsRaw
     * @return array<int,string>
     */
    private function resolveTags(mixed $tagsRaw): array
    {
        if (is_string($tagsRaw)) {
            $tagsRaw = explode(',', $tagsRaw);
        }

        if (! is_array($tagsRaw)) {
            return [];
        }

        return collect($tagsRaw)
            ->map(fn ($tag) => trim((string) $tag))
            ->filter(fn (string $tag) => $tag !== '')
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Minimal frontmatter parser for markdown files.
     *
     * @return array{meta:array<string,mixed>,body:string}
     */
    private function extractMarkdownFrontMatter(string $content): array
    {
        if (! preg_match('/\A---\R(.*?)\R---\R/s', $content, $matches)) {
            return ['meta' => [], 'body' => $content];
        }

        $metaLines = preg_split('/\R/', (string) $matches[1]) ?: [];
        $meta = [];
        foreach ($metaLines as $line) {
            if (! str_contains($line, ':')) {
                continue;
            }
            [$k, $v] = explode(':', $line, 2);
            $key = trim($k);
            $value = trim($v);

            if ($key === 'tags') {
                $meta[$key] = array_map('trim', explode(',', $value));
            } elseif ($key === 'is_public') {
                $meta[$key] = in_array(strtolower($value), ['1', 'true', 'yes'], true);
            } else {
                $meta[$key] = $value;
            }
        }

        $body = substr($content, strlen((string) $matches[0]));

        return ['meta' => $meta, 'body' => $body === false ? $content : $body];
    }
}
