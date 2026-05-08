<?php

namespace App\Services;

use App\Repositories\Contracts\FolderRepositoryInterface;
use App\Repositories\Contracts\SmartCollectionRepositoryInterface;
use App\Repositories\Contracts\SnippetRepositoryInterface;
use App\Repositories\Contracts\TagRepositoryInterface;

readonly class DashboardService
{
    public function __construct(
        private SnippetRepositoryInterface $snippets,
        private FolderRepositoryInterface $folders,
        private SmartCollectionRepositoryInterface $smartCollections,
        private TagRepositoryInterface $tags
    ) {}

    /**
     * @return array{
     *   stats: array<string, int>,
     *   recentSnippets: \Illuminate\Support\Collection,
     *   topTags: \Illuminate\Support\Collection
     * }
     */
    public function buildForUser(int $userId): array
    {
        return [
            'stats' => [
                'snippets_total' => $this->snippets->countByUser($userId),
                'snippets_public' => $this->snippets->countPublicByUser($userId),
                'folders_total' => $this->folders->countByUser($userId),
                'smart_collections_total' => $this->smartCollections->countByUser($userId),
                'snippets_without_folder' => $this->snippets->countWithoutFoldersByUser($userId),
            ],
            'recentSnippets' => $this->snippets->getRecentByUser($userId, 6),
            'topTags' => $this->tags->forUserSnippets($userId)->take(8)->values(),
        ];
    }
}
