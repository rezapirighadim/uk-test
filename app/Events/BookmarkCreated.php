<?php

namespace App\Events;

use App\Models\Bookmark;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BookmarkCreated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * The bookmark instance.
     *
     * @var Bookmark
     */
    public Bookmark $bookmark;

    /**
     * Create a new event instance.
     *
     * @param Bookmark $bookmark
     * @return void
     */
    public function __construct(Bookmark $bookmark)
    {
        $this->bookmark = $bookmark;
    }
}
