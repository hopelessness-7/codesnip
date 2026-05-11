<?php

namespace App\Services;

use App\DTOs\ImportOptionsData;
use App\DTOs\ImportResultData;
use App\Modules\GithubGist\Client as GithubGistClient;
use App\Models\User;

readonly class GithubGistTransferService
{
    public function __construct(
        private GithubGistClient $githubGistClient,
        private SnippetJsonTransferService $snippetJsonTransferService
    ) {}

    public function exportToGist(User $user, array $snippetIds, ?string $description): string
    {
        $token = (string) ($user->github_personal_access_token ?? '');
        if ($token === '') {
            throw new \RuntimeException('GitHub token is not configured for this account.');
        }

        $payload = $this->snippetJsonTransferService->exportToArray($user, $snippetIds);

        $gist = $this->githubGistClient->createGist($token, [
            'description' => $description ?: 'CodeSnip export',
            'public' => false,
            'files' => [
                'codesnip-export.json' => [
                    'content' => json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
                ],
            ],
        ]);

        return (string) ($gist['html_url'] ?? '');
    }

    public function importFromGistUrl(User $user, string $urlOrId, ImportOptionsData $options): ImportResultData
    {
        $token = (string) ($user->github_personal_access_token ?? '');
        if ($token === '') {
            throw new \RuntimeException('GitHub token is not configured for this account.');
        }

        $gistId = $this->extractGistId($urlOrId);
        $gist = $this->githubGistClient->getGist($token, $gistId);
        $files = (array) ($gist['files'] ?? []);

        $content = data_get($files, 'codesnip-export.json.content');
        if (! is_string($content) || trim($content) === '') {
            throw new \RuntimeException('codesnip-export.json not found in gist.');
        }

        $decoded = json_decode($content, true);
        if (! is_array($decoded)) {
            throw new \RuntimeException('Invalid JSON in codesnip-export.json.');
        }

        return $this->snippetJsonTransferService->importFromArray($user, $decoded, $options);
    }

    private function extractGistId(string $urlOrId): string
    {
        $candidate = trim($urlOrId);
        if ($candidate === '') {
            throw new \InvalidArgumentException('Gist URL or ID is required.');
        }

        if (preg_match('~^[a-f0-9]{8,}$~i', $candidate) === 1) {
            return $candidate;
        }

        if (preg_match('~/([a-f0-9]{8,})(?:$|[/?#])~i', $candidate, $matches) === 1) {
            return $matches[1];
        }

        throw new \InvalidArgumentException('Unable to parse gist ID from the provided value.');
    }
}
