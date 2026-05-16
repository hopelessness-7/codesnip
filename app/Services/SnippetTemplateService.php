<?php

namespace App\Services;

use App\Models\SnippetTemplate;
use App\Repositories\Contracts\SnippetTemplateRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class SnippetTemplateService extends BaseService
{
    private const PATTERN_VARIABLE_REGEX = '/\[\[\s*([a-zA-Z_][a-zA-Z0-9_]*)\s*\]\]/';

    public function __construct(SnippetTemplateRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    public function listForUser(int $userId): Collection
    {
        return $this->repository->findByUser($userId);
    }

    public function paginateForUser(int $userId, int $perPage = 12): LengthAwarePaginator
    {
        $paginator = $this->repository->paginateByUser($userId, $perPage);

        $paginator->getCollection()->transform(function (SnippetTemplate $template): SnippetTemplate {
            $template->template_variables = $this->extractVariables(
                (string) $template->title_template,
                (string) $template->code_template
            );

            return $template;
        });

        return $paginator;
    }

    public function findForUser(int $userId, int $templateId): ?SnippetTemplate
    {
        return $this->repository->findForUser($userId, $templateId);
    }

    public function buildPreview(SnippetTemplate $template, array $values = []): array
    {
        $titleTemplate = (string) ($template->title_template ?? '');
        $codeTemplate = (string) ($template->code_template ?? '');

        $variables = $this->extractVariables($titleTemplate, $codeTemplate);

        return [
            'title' => $this->applyVariables($titleTemplate, $values),
            'code' => $this->applyVariables($codeTemplate, $values),
            'language' => (string) ($template->language ?? 'unknown'),
            'tags' => collect($template->default_tags_json ?? [])->map(fn ($tag) => (string) $tag)
                ->filter()->values()->all(),
            'variables' => $variables,
        ];
    }

    public function applyVariables(string $text, array $values): string
    {
        return (string) preg_replace_callback(
            self::PATTERN_VARIABLE_REGEX,
            function (array $match) use ($values): string {
                $key = $match[1];
                if (! array_key_exists($key, $values)) {
                    return $match[0];
                }
                return (string) $values[$key];
            },
            $text
        );
    }

    public function extractVariables(string $titleTemplate, string $codeTemplate): array
    {
        $source = $titleTemplate."\n".$codeTemplate;

        preg_match_all(self::PATTERN_VARIABLE_REGEX, $source, $matches);

        if (! isset($matches[1]) || ! is_array($matches[1])) {
            return [];
        }

        return collect($matches[1])->map(fn ($name) => trim((string) $name))
            ->filter()->unique()->sort()->values()->all();
    }

    /**
     * @return array{description:string,title_template:string,code_template:string,default_tags:array<int,string>}|null
     */
    public function getLanguagePreset(string $language): ?array
    {
        $path = base_path('DataSet/snippet_template_presets.json');
        if (! is_file($path)) {
            return null;
        }

        $raw = file_get_contents($path);
        if ($raw === false) {
            return null;
        }

        $decoded = json_decode($raw, true);
        if (! is_array($decoded) || ! isset($decoded[$language]) || ! is_array($decoded[$language])) {
            return null;
        }

        $preset = $decoded[$language];

        return [
            'description' => (string) ($preset['description'] ?? ''),
            'title_template' => (string) ($preset['title_template'] ?? ''),
            'code_template' => (string) ($preset['code_template'] ?? ''),
            'default_tags' => collect($preset['default_tags'] ?? [])
                ->map(fn ($tag) => (string) $tag)
                ->filter()
                ->values()
                ->all(),
        ];
    }
}
