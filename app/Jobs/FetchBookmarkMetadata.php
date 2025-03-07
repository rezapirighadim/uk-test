<?php

namespace App\Jobs;

use App\Models\Bookmark;
use App\Services\MetadataFetcherService;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class FetchBookmarkMetadata implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public int $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     *
     * @var int
     */
    public int $backoff = 60;

    /**
     * The bookmark instance.
     *
     * @var Bookmark
     */
    protected Bookmark $bookmark;

    /**
     * Create a new job instance.
     *
     * @param Bookmark $bookmark
     * @return void
     */
    public function __construct(Bookmark $bookmark)
    {
        $this->bookmark = $bookmark;
    }

    /**
     * Execute the job.
     *
     * @param MetadataFetcherService $metadataFetcher
     * @return void
     * @throws Exception
     */
    public function handle(MetadataFetcherService $metadataFetcher): void
    {
        $bookmark = $this->bookmark->fresh();

        try {
            $metadata = $metadataFetcher->fetch($bookmark->url);

            $bookmark->update([
                'title' => $metadata['title'] ?? null,
                'description' => $metadata['description'] ?? null,
                'metadata_fetched_at' => now(),
                'fetch_failed' => false,
                'fetch_error' => null,
            ]);

            Log::info('Bookmark metadata fetched successfully', [
                'bookmark_id' => $bookmark->id,
                'url' => $bookmark->url,
            ]);
        } catch (Exception $e) {
            $bookmark->update([
                'fetch_failed' => true,
                'fetch_error' => $e->getMessage(),
            ]);

            Log::error('Failed to fetch bookmark metadata', [
                'bookmark_id' => $bookmark->id,
                'url' => $bookmark->url,
                'error' => $e->getMessage(),
            ]);

            // Re-throwing the exception will cause the job to be retried
            // based on the retry configuration
            throw $e;
        }
    }

    /**
     * The job failed to process.
     *
     * @param  Exception  $exception
     * @return void
     */
    public function failed(Exception $exception): void
    {
        Log::error('Bookmark metadata job failed after all retries', [
            'bookmark_id' => $this->bookmark->id,
            'url' => $this->bookmark->url,
            'error' => $exception->getMessage(),
        ]);
    }
}
