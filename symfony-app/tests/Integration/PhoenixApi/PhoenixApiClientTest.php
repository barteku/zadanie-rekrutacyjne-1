<?php

declare(strict_types=1);

namespace App\Tests\Integration\PhoenixApi;

use App\Integration\PhoenixApi\InvalidTokenException;
use App\Integration\PhoenixApi\PhoenixApiClient;
use App\Integration\PhoenixApi\PhoenixApiException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

class PhoenixApiClientTest extends TestCase
{
    public function testFetchPhotosReturnsPayload(): void
    {
        $client = new PhoenixApiClient(
            new MockHttpClient([
                new MockResponse(json_encode([
                    'photos' => [
                        ['id' => 1, 'photo_url' => 'https://example.com/photo.jpg'],
                    ],
                ], JSON_THROW_ON_ERROR)),
            ]),
            'http://phoenix:4000'
        );

        $photos = $client->fetchPhotos('token');

        self::assertCount(1, $photos);
        self::assertSame('https://example.com/photo.jpg', $photos[0]['photo_url']);
    }

    public function testFetchPhotosThrowsOnInvalidToken(): void
    {
        $client = new PhoenixApiClient(
            new MockHttpClient([new MockResponse('', ['http_code' => 401])]),
            'http://phoenix:4000'
        );

        $this->expectException(InvalidTokenException::class);
        $client->fetchPhotos('bad-token');
    }

    public function testFetchPhotosThrowsOnRateLimit(): void
    {
        $client = new PhoenixApiClient(
            new MockHttpClient([new MockResponse('', ['http_code' => 429])]),
            'http://phoenix:4000'
        );

        $this->expectException(PhoenixApiException::class);
        $this->expectExceptionMessage('rate limit');
        $client->fetchPhotos('token');
    }
}
