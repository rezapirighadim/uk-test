<?php

use App\Services\MetadataFetcherService;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    $this->metadataFetcher = new MetadataFetcherService();
});

test('can extract metadata from html', function () {
    // Mock HTTP response
    Http::fake([
        'example.com' => Http::response(
            <<<HTML
            <!DOCTYPE html>
            <html>
            <head>
                <title>Example Website</title>
                <meta name="description" content="This is an example website for testing">
            </head>
            <body>
                <h1>Example Website</h1>
                <p>This is an example website.</p>
            </body>
            </html>
            HTML,
            200
        ),
    ]);

    $metadata = $this->metadataFetcher->fetch('https://example.com');

    expect($metadata)->toBeArray();
    expect($metadata)->toHaveKeys(['title', 'description']);
    expect($metadata['title'])->toBe('Example Website');
    expect($metadata['description'])->toBe('This is an example website for testing');
});

test('can extract open graph metadata', function () {
    // Mock HTTP response with Open Graph tags
    Http::fake([
        'example.com' => Http::response(
            <<<HTML
            <!DOCTYPE html>
            <html>
            <head>
                <title>Regular Title</title>
                <meta property="og:title" content="OG Title">
                <meta property="og:description" content="OG Description">
                <meta name="description" content="Regular Description">
            </head>
            <body>
                <h1>Example Website</h1>
            </body>
            </html>
            HTML,
            200
        ),
    ]);

    $metadata = $this->metadataFetcher->fetch('https://example.com');

    expect($metadata['title'])->toBe('OG Title');
    expect($metadata['description'])->toBe('OG Description');
});

test('can extract twitter card metadata', function () {
    // Mock HTTP response with Twitter Card tags
    Http::fake([
        'example.com' => Http::response(
            <<<HTML
            <!DOCTYPE html>
            <html>
            <head>
                <title>Regular Title</title>
                <meta name="twitter:title" content="Twitter Title">
                <meta name="twitter:description" content="Twitter Description">
            </head>
            <body>
                <h1>Example Website</h1>
            </body>
            </html>
            HTML,
            200
        ),
    ]);

    $metadata = $this->metadataFetcher->fetch('https://example.com');

    expect($metadata['description'])->toBe('Twitter Description');
});

test('handles missing metadata gracefully', function () {
    // Mock HTTP response with no metadata
    Http::fake([
        'example.com' => Http::response(
            <<<HTML
            <!DOCTYPE html>
            <html>
            <body>
                <h1>Example Website</h1>
            </body>
            </html>
            HTML,
            200
        ),
    ]);

    $metadata = $this->metadataFetcher->fetch('https://example.com');

    expect($metadata['title'])->toBeNull();
    expect($metadata['description'])->toBeNull();
});

test('handles http errors', function () {
    // Mock HTTP response with 404 error
    Http::fake([
        'example.com' => Http::response(null, 404),
    ]);

    $this->expectException(\Exception::class);
    $this->expectExceptionMessage('Failed to fetch URL: HTTP status 404');

    $this->metadataFetcher->fetch('https://example.com');
});

test('handles connection errors', function () {
    // Mock HTTP response with connection error
    Http::fake(function () {
        throw new \Illuminate\Http\Client\ConnectionException('Connection failed');
    });

    $this->expectException(\Exception::class);
    $this->expectExceptionMessage('Error fetching metadata: Connection failed');

    $this->metadataFetcher->fetch('https://example.com');
});
