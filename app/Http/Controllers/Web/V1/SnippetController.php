<?php

namespace App\Http\Controllers\Web\V1;

use App\DTOs\SearchFilters;
use App\Http\Controllers\BaseController;
use App\Http\Requests\BaseRequest;
use App\Http\Requests\SnippetFilterRequest;
use App\Http\Requests\SnippetRequest;
use App\Http\Resources\SnippetResource;
use App\Models\Snippet;
use App\Services\SnippetService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;

class SnippetController extends BaseController
{
    public function __construct(SnippetService $service)
    {
        $this->service = $service;
    }

    public function publicView(string $uuid)
    {
        try {
            $snippet = $this->service->findByUuid($uuid);

            if (! $snippet) {
                abort(404, 'Snippet not found or link expired');
            }

            return view('snippets.public', compact('snippet'));
        } catch (\Exception $e) {
            abort(404, 'Invalid or expired link');
        }
    }

    public function publicOpen(string $uuid)
    {
        try {
            $snippet = $this->service->findByUuid($uuid);

            if (! $snippet || ! $snippet->is_public) {
                abort(404, 'Snippet not found');
            }

            return view('snippets.public', compact('snippet'));
        } catch (\Exception $e) {
            abort(404, 'Snippet not found');
        }
    }

    protected function authorizeAction(string $action, $model = null): void
    {
        if ($action === 'create' && $model === null) {
            $model = Snippet::class;
        }

        parent::authorizeAction($action, $model);
    }
}
