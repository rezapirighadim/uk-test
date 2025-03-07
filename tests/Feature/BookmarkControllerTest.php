<?php

use App\Models\Bookmark;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;

beforeEach(function () {
    // Set up the test environment
    $this->apiToken = 'test-api-token';

    // Set the test API token in environment
    config(['app.api_token' => $this->apiToken]);

    // Create headers with authorization token
    $this->headers = [
        'Authorization' => 'Bearer ' . $this->apiToken,
        'Accept' => 'application/json',
    ];
});

test('unauthenticated users cannot access bookmarks', function () {
    $response = $this->getJson('/api/bookmarks');

    $response->assertStatus(401);
    $response->assertJson([
        'message' => 'Unauthorized: Invalid API token',
    ]);
});

test('can create a bookmark', function () {
    Queue::fake();

    $url = 'https://example.com';

    $response = $this->postJson('/api/bookmarks', [
        'url' => $url,
    ], $this->headers);

    $response->assertStatus(201);
    $response->assertJsonStructure([
        'message',
        'data' => [
            'id',
            'url',
            'created_at',
            'updated_at',
        ],
    ]);

    $this->assertDatabaseHas('bookmarks', [
        'url' => $url,
    ]);

    Queue::assertPushed(\App\Jobs\FetchBookmarkMetadata::class);
});

test('cannot create a bookmark with invalid url', function () {
    $response = $this->postJson('/api/bookmarks', [
        'url' => 'not-a-valid-url',
    ], $this->headers);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['url']);
});

test('can list all bookmarks', function () {
    // Create 3 bookmarks
    Bookmark::factory()->count(3)->create();

    $response = $this->getJson('/api/bookmarks', $this->headers);

    $response->assertStatus(200);
    $response->assertJsonStructure([
        'data',
        'meta' => ['current_page', 'last_page', 'per_page', 'total'],
    ]);
    $response->assertJsonCount(3, 'data');
});

test('can filter bookmarks by status', function () {
    // Create bookmarks with different statuses
    Bookmark::factory()->create([
        'metadata_fetched_at' => now(),
        'fetch_failed' => false,
    ]);

    Bookmark::factory()->create([
        'metadata_fetched_at' => null,
        'fetch_failed' => false,
    ]);

    Bookmark::factory()->create([
        'metadata_fetched_at' => null,
        'fetch_failed' => true,
    ]);

    // Test completed filter
    $response = $this->getJson('/api/bookmarks?status=completed', $this->headers);
    $response->assertStatus(200);
    $response->assertJsonCount(1, 'data');

    // Test pending filter
    $response = $this->getJson('/api/bookmarks?status=pending', $this->headers);
    $response->assertStatus(200);
    $response->assertJsonCount(1, 'data');

    // Test failed filter
    $response = $this->getJson('/api/bookmarks?status=failed', $this->headers);
    $response->assertStatus(200);
    $response->assertJsonCount(1, 'data');
});

test('can get a specific bookmark', function () {
    $bookmark = Bookmark::factory()->create();

    $response = $this->getJson("/api/bookmarks/{$bookmark->id}", $this->headers);

    $response->assertStatus(200);
    $response->assertJson([
        'data' => [
            'id' => $bookmark->id,
            'url' => $bookmark->url,
        ],
    ]);
});

test('returns 404 for non-existent bookmark', function () {
    $uuid = Str::uuid();

    $response = $this->getJson("/api/bookmarks/{$uuid}", $this->headers);

    $response->assertStatus(404);
});

test('can delete a bookmark', function () {
    $bookmark = Bookmark::factory()->create();

    $response = $this->deleteJson("/api/bookmarks/{$bookmark->id}", [], $this->headers);

    $response->assertStatus(200);
    $response->assertJson([
        'message' => 'Bookmark deleted successfully.',
    ]);

    // Check that it's soft deleted
    $this->assertSoftDeleted('bookmarks', [
        'id' => $bookmark->id,
    ]);
});

test('can retry metadata fetch for a bookmark', function () {
    Queue::fake();

    $bookmark = Bookmark::factory()->create([
        'fetch_failed' => true,
        'fetch_error' => 'Previous error',
    ]);

    $response = $this->postJson("/api/bookmarks/{$bookmark->id}/retry", [], $this->headers);

    $response->assertStatus(200);
    $response->assertJson([
        'message' => 'Metadata fetch retry initiated.',
    ]);

    $this->assertDatabaseHas('bookmarks', [
        'id' => $bookmark->id,
        'fetch_failed' => false,
        'fetch_error' => null,
    ]);

    Queue::assertPushed(\App\Jobs\FetchBookmarkMetadata::class);
});
