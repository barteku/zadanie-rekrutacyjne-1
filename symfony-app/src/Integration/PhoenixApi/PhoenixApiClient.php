<?php

declare(strict_types=1);

namespace App\Integration\PhoenixApi;

use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class PhoenixApiClient
{
    public function __construct(
        private HttpClientInterface $httpClient,
        private string $baseUrl
    ) {
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function fetchPhotos(string $accessToken): array
    {
        try {
            $response = $this->httpClient->request('GET', rtrim($this->baseUrl, '/') . '/api/photos', [
                'headers' => [
                    'access-token' => $accessToken,
                ],
                'timeout' => 8,
            ]);
        } catch (TransportExceptionInterface $exception) {
            throw new PhoenixApiException('PhoenixApi is unavailable right now.', previous: $exception);
        }

        $statusCode = $response->getStatusCode();
        if ($statusCode === 401) {
            throw new InvalidTokenException('Invalid PhoenixApi token.');
        }

        if ($statusCode === 429) {
            throw new PhoenixApiException('PhoenixApi rate limit reached. Please try again later.');
        }

        if ($statusCode >= 400) {
            throw new PhoenixApiException('Failed to fetch photos from PhoenixApi.');
        }

        $payload = $response->toArray(false);
        $photos = $payload['photos'] ?? null;
        if (!is_array($photos)) {
            throw new PhoenixApiException('PhoenixApi response is invalid.');
        }

        return $photos;
    }
}
