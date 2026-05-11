<?php

namespace App\Modules\GithubGist;

use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Http\Client\Response;

class Client
{
    private const BASE_URL = 'https://api.github.com';

    public function __construct(
        private readonly HttpFactory $http
    ) {}

    public function createGist(string $token, array $payload): array
    {
        $response = $this->request($token)->post(self::BASE_URL.'/gists', $payload);
        $this->throwIfFailed($response);

        return (array) $response->json();
    }

    public function getGist(string $token, string $gistId): array
    {
        $response = $this->request($token)->get(self::BASE_URL.'/gists/'.$gistId);
        $this->throwIfFailed($response);

        return (array) $response->json();
    }

    private function request(string $token): HttpFactory
    {
        return $this->http
            ->withToken($token)
            ->acceptJson()
            ->asJson()
            ->withHeaders([
                'X-GitHub-Api-Version' => '2022-11-28',
                'User-Agent' => 'codesnip-app',
            ]);
    }

    private function throwIfFailed(Response $response): void
    {
        if ($response->successful()) {
            return;
        }

        $message = (string) data_get($response->json(), 'message', 'GitHub API request failed.');

        throw new \RuntimeException($message);
    }
}
