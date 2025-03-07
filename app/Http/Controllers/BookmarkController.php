<?php

namespace App\Http\Controllers;

use App\Events\BookmarkCreated;
use App\Http\Requests\BookmarkRequest;
use App\Jobs\FetchBookmarkMetadata;
use App\Models\Bookmark;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class BookmarkController extends Controller
{
    /**
     * Display a listing of bookmarks.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $bookmarks = Bookmark::query()
            ->when($request->filled('status'), function ($query) use ($request) {
                $status = $request->input('status');
                if ($status === 'pending') {
                    return $query->pendingMetadata();
                } elseif ($status === 'failed') {
                    return $query->failedMetadata();
                } elseif ($status === 'completed') {
                    return $query->withMetadata();
                }
            })
            ->latest()
            ->paginate(15);

        return response()->json([
            'data' => $bookmarks->items(),
            'meta' => [
                'current_page' => $bookmarks->currentPage(),
                'last_page' => $bookmarks->lastPage(),
                'per_page' => $bookmarks->perPage(),
                'total' => $bookmarks->total(),
            ],
        ]);
    }

    /**
     * Store a newly created bookmark.
     *
     * @param BookmarkRequest $request
     * @return JsonResponse
     */
    public function store(BookmarkRequest $request): JsonResponse
    {
        $bookmark = Bookmark::create([
            'url' => $request->url,
        ]);

        // Dispatch the job to fetch metadata asynchronously
        FetchBookmarkMetadata::dispatch($bookmark);

        // Also fire an event for extensibility
        event(new BookmarkCreated($bookmark));

        return response()->json([
            'message' => 'Bookmark created successfully. Metadata will be fetched asynchronously.',
            'data' => $bookmark,
        ], Response::HTTP_CREATED);
    }

    /**
     * Display the specified bookmark.
     *
     * @param Bookmark $bookmark
     * @return JsonResponse
     */
    public function show(Bookmark $bookmark)
    {
        return response()->json([
            'data' => $bookmark,
        ]);
    }

    /**
     * Remove the specified bookmark (soft delete).
     *
     * @param Bookmark $bookmark
     * @return JsonResponse
     */
    public function destroy(Bookmark $bookmark)
    {
        $bookmark->delete();

        return response()->json([
            'message' => 'Bookmark deleted successfully.',
        ]);
    }

    /**
     * Retry fetching metadata for a bookmark.
     *
     * @param Bookmark $bookmark
     * @return JsonResponse
     */
    public function retry(Bookmark $bookmark)
    {
        $bookmark->update([
            'fetch_failed' => false,
            'fetch_error' => null,
        ]);

        FetchBookmarkMetadata::dispatch($bookmark);

        return response()->json([
            'message' => 'Metadata fetch retry initiated.',
            'data' => $bookmark,
        ]);
    }
}
