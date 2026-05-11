<?php

namespace App\Services;

use App\DTOs\ImportOptionsData;
use App\DTOs\ImportResultData;
use App\Modules\Parser\MassSnippetFileParser;
use App\Models\User;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use ZipArchive;

readonly class SnippetZipArchiveService
{
    public function __construct(
        private SnippetJsonTransferService $snippetJsonTransferService,
        private MassSnippetFileParser $massSnippetFileParser
    ) {}

    public function exportZip(User $user, iterable $snippetIds = []): string
    {
        $payload = $this->snippetJsonTransferService->exportToArray($user, $snippetIds);
        $items = is_array($payload['items'] ?? null) ? $payload['items'] : [];

        $tempDir = storage_path('app/tmp');
        if (! File::exists($tempDir)) {
            File::makeDirectory($tempDir, 0755, true);
        }

        $zipPath = $tempDir.'/codesnip-export-'.$user->id.'-'.now()->format('Ymd_His').'.zip';

        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new \RuntimeException('Unable to create zip archive.');
        }

        $manifestItems = [];

        foreach ($items as $item) {
            if (! is_array($item)) {
                continue;
            }

            $code = (string) ($item['code'] ?? '');
            $title = (string) ($item['title'] ?? 'snippet');
            $language = is_string($item['language'] ?? null) ? $item['language'] : 'unknown';

            $ext = $this->massSnippetFileParser->resolveExtensionForLanguage($language);
            $baseName = Str::slug($title);
            if ($baseName === '') {
                $baseName = 'snippet';
            }

            $uuid = is_string($item['uuid'] ?? null) ? $item['uuid'] : (string) Str::uuid();
            $entryPath = 'snippets/'.$baseName.'-'.$uuid.'.'.$ext;

            $zip->addFromString($entryPath, $code);

            $manifestItems[] = [
                'uuid' => $uuid,
                'title' => $title,
                'language' => $language,
                'is_public' => (bool) ($item['is_public'] ?? false),
                'tags' => is_array($item['tags'] ?? null) ? $item['tags'] : [],
                'path' => $entryPath,
            ];
        }

        $manifest = [
            'export_version' => 1,
            'exported_at' => now()->toIso8601String(),
            'items' => $manifestItems,
        ];
        $zip->addFromString('manifest.json', (string) json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        $zip->close();

        return $zipPath;
    }

    public function importZip(User $user, string $zipPath, ImportOptionsData $options): ImportResultData
    {
        if (! File::exists($zipPath)) {
            $result = new ImportResultData();
            $result->addError('Zip file not found.');

            return $result;
        }

        $zip = new ZipArchive();
        if ($zip->open($zipPath) !== true) {
            $result = new ImportResultData();
            $result->addError('Unable to open zip archive.');

            return $result;
        }

        $manifestIndex = $zip->locateName('manifest.json');
        if ($manifestIndex !== false) {
            $manifestRaw = $zip->getFromIndex($manifestIndex);
            $zip->close();

            if (! is_string($manifestRaw) || trim($manifestRaw) === '') {
                $result = new ImportResultData();
                $result->addError('manifest.json is empty.');

                return $result;
            }

            $manifest = json_decode($manifestRaw, true);
            if (! is_array($manifest) || ! is_array($manifest['items'] ?? null)) {
                $result = new ImportResultData();
                $result->addError('Invalid manifest.json format.');

                return $result;
            }

            $zipRead = new ZipArchive();
            if ($zipRead->open($zipPath) !== true) {
                $result = new ImportResultData();
                $result->addError('Unable to reopen zip archive.');

                return $result;
            }

            $payloadItems = [];
            foreach ($manifest['items'] as $item) {
                if (! is_array($item)) {
                    continue;
                }

                $entryPath = (string) ($item['path'] ?? '');
                if ($entryPath === '' || str_contains($entryPath, '..') || str_starts_with($entryPath, '/')) {
                    continue;
                }

                $code = $zipRead->getFromName($entryPath);
                if (! is_string($code)) {
                    $code = '';
                }

                $payloadItems[] = [
                    'uuid' => $item['uuid'] ?? null,
                    'title' => $item['title'] ?? '',
                    'code' => $code,
                    'language' => $item['language'] ?? null,
                    'is_public' => (bool) ($item['is_public'] ?? false),
                    'tags' => is_array($item['tags'] ?? null) ? $item['tags'] : [],
                ];
            }
            $zipRead->close();

            return $this->snippetJsonTransferService->importFromArray($user, ['items' => $payloadItems], $options);
        }

        $payloadItems = [];
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $entryName = (string) $zip->getNameIndex($i);
            if ($entryName === '' || str_ends_with($entryName, '/')) {
                continue;
            }

            $basename = basename($entryName);
            if (strtolower($basename) === 'manifest.json') {
                continue;
            }

            $content = $zip->getFromIndex($i);
            if (! is_string($content)) {
                continue;
            }

            $payloadItems[] = $this->massSnippetFileParser->parseEntry($entryName, $content);
        }
        $zip->close();

        return $this->snippetJsonTransferService->importFromArray($user, ['items' => $payloadItems], $options);
    }
}
