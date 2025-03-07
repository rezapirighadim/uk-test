<?php

use App\Models\Bookmark;

test('bookmark has uuid as primary key', function () {
    $bookmark = Bookmark::factory()->create();

    expect($bookmark->id)->toBeString();
    expect(Str::isUuid($bookmark->id))->toBeTrue();
});

test('bookmark uses soft deletes', function () {
    $bookmark = Bookmark::factory()->create();

    $bookmark->delete();

    $this->assertSoftDeleted('bookmarks', [
        'id' => $bookmark->id,
    ]);

    // Make sure we can still find it when including soft deletes
    $found = Bookmark::withTrashed()->find($bookmark->id);
    expect($found)->not->toBeNull();
});

test('bookmark has correct attributes', function () {
    $bookmark = Bookmark::factory()->create([
        'url' => 'https://example.com',
        'title' => 'Example Website',
        'description' => 'This is an example website',
        'metadata_fetched_at' => now(),
        'fetch_failed' => false,
        'fetch_error' => null,
    ]);

    expect($bookmark->url)->toBe('https://example.com');
    expect($bookmark->title)->toBe('Example Website');
    expect($bookmark->description)->toBe('This is an example website');
    expect($bookmark->metadata_fetched_at)->not->toBeNull();
    expect($bookmark->fetch_failed)->toBeFalse();
    expect($bookmark->fetch_error)->toBeNull();
});

test('with metadata scope works correctly', function () {
    // Create bookmarks with and without metadata
    $withMetadata = Bookmark::factory()->create([
        'metadata_fetched_at' => now(),
    ]);

    $withoutMetadata = Bookmark::factory()->create([
        'metadata_fetched_at' => null,
    ]);

    $bookmarks = Bookmark::withMetadata()->get();

    expect($bookmarks)->toHaveCount(1);
    expect($bookmarks->first()->id)->toBe($withMetadata->id);
});

test('pending metadata scope works correctly', function () {
    // Create a pending bookmark
    $pendingBookmark = Bookmark::factory()->create([
        'metadata_fetched_at' => null,
        'fetch_failed' => false,
    ]);

    // Create a failed bookmark
    $failedBookmark = Bookmark::factory()->create([
        'metadata_fetched_at' => null,
        'fetch_failed' => true,
    ]);

    // Create a completed bookmark
    $completedBookmark = Bookmark::factory()->create([
        'metadata_fetched_at' => now(),
        'fetch_failed' => false,
    ]);

    $bookmarks = Bookmark::pendingMetadata()->get();

    expect($bookmarks)->toHaveCount(1);
    expect($bookmarks->first()->id)->toBe($pendingBookmark->id);
});

test('failed metadata scope works correctly', function () {
    // Create a failed bookmark
    $failedBookmark = Bookmark::factory()->create([
        'metadata_fetched_at' => null,
        'fetch_failed' => true,
    ]);

    // Create a pending bookmark
    $pendingBookmark = Bookmark::factory()->create([
        'metadata_fetched_at' => null,
        'fetch_failed' => false,
    ]);

    $bookmarks = Bookmark::failedMetadata()->get();

    expect($bookmarks)->toHaveCount(1);
    expect($bookmarks->first()->id)->toBe($failedBookmark->id);
});
