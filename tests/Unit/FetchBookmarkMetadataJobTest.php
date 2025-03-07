<?php

use App\Jobs\FetchBookmarkMetadata;
use App\Models\Bookmark;
use App\Services\MetadataFetcherService;
use Illuminate\Support\Facades\Log;

beforeEach(function () {
    $this->bookmark = Bookmark::factory()->create([
        'url' => 'https://example.com',
        'title' => null,
        'description' => null,
        'metadata_fetched_at' => null,
        'fetch_failed' => false,
        'fetch_error' => null,
    ]);
});

test('job fetches and updates bookmark metadata', function () {
    // Mock the MetadataFetcherService
    $metadataFetcher = Mockery::mock(MetadataFetcherService::class);
    $metadataFetcher->shouldReceive('fetch')
        ->once()
        ->with('https://example.com')
        ->andReturn([
            'title' => 'Example Website',
            'description' => 'This is an example website',
        ]);

    $this->app->instance(MetadataFetcherService::class, $metadataFetcher);

    // Execute the job
    $job = new FetchBookmarkMetadata($this->bookmark);
    $job->handle($metadataFetcher);

    // Check the bookmark was updated
    $this->bookmark->refresh();

    expect($this->bookmark->title)->toBe('Example Website')
        ->and($this->bookmark->description)->toBe('This is an example website')
        ->and($this->bookmark->metadata_fetched_at)->not->toBeNull()
        ->and($this->bookmark->fetch_failed)->toBeFalse()
        ->and($this->bookmark->fetch_error)->toBeNull();
});

test('job handles missing metadata gracefully', function () {
    // Mock the MetadataFetcherService
    $metadataFetcher = Mockery::mock(MetadataFetcherService::class);
    $metadataFetcher->shouldReceive('fetch')
        ->once()
        ->with('https://example.com')
        ->andReturn([
            'title' => null,
            'description' => null,
        ]);

    $this->app->instance(MetadataFetcherService::class, $metadataFetcher);

    // Execute the job
    $job = new FetchBookmarkMetadata($this->bookmark);
    $job->handle($metadataFetcher);

    // Check the bookmark was updated
    $this->bookmark->refresh();

    expect($this->bookmark->title)->toBeNull()
        ->and($this->bookmark->description)->toBeNull()
        ->and($this->bookmark->metadata_fetched_at)->not->toBeNull()
        ->and($this->bookmark->fetch_failed)->toBeFalse()
        ->and($this->bookmark->fetch_error)->toBeNull();
});

test('job handles fetch errors', function () {
    // Mock the MetadataFetcherService
    $metadataFetcher = Mockery::mock(MetadataFetcherService::class);
    $metadataFetcher->shouldReceive('fetch')
        ->once()
        ->with('https://example.com')
        ->andThrow(new \Exception('Failed to fetch metadata'));

    $this->app->instance(MetadataFetcherService::class, $metadataFetcher);

    // Mock the Log facade
    Log::shouldReceive('error')
        ->once()
        ->with('Failed to fetch bookmark metadata', \Mockery::any());

    // Execute the job (it will throw an exception which is expected)
    try {
        $job = new FetchBookmarkMetadata($this->bookmark);
        $job->handle($metadataFetcher);
    } catch (\Exception $e) {
        // Expected exception
    }

    // Check the bookmark was updated with error info
    $this->bookmark->refresh();

    expect($this->bookmark->fetch_failed)->toBeTrue()
        ->and($this->bookmark->fetch_error)->toBe('Failed to fetch metadata')
        ->and($this->bookmark->metadata_fetched_at)->toBeNull();
});

test('job has correct retry configuration', function () {
    $job = new FetchBookmarkMetadata($this->bookmark);

    expect($job->tries)->toBe(3)
        ->and($job->backoff)->toBe(60);
});
