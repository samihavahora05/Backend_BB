<?php

namespace App\Observers;

use App\Models\Blog;
use App\Models\BlogActivityLog;
use App\Models\BlogRevision;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;

class BlogObserver
{
    /**
     * Handle the Blog "creating" event.
     */
    public function creating(Blog $blog): void
    {
        // Auto-slug
        if (empty($blog->slug)) {
            $blog->slug = Str::slug($blog->title);
        }

        // Reading time (approx 200 words per min)
        if (!empty($blog->content)) {
            $wordCount = str_word_count(strip_tags($blog->content));
            $blog->reading_time = max(1, ceil($wordCount / 200));
        }
    }

    /**
     * Handle the Blog "created" event.
     */
    public function created(Blog $blog): void
    {
        $this->logActivity($blog, 'created');
    }

    /**
     * Handle the Blog "updating" event.
     */
    public function updating(Blog $blog): void
    {
        // Update Reading time
        if ($blog->isDirty('content')) {
            $wordCount = str_word_count(strip_tags($blog->content));
            $blog->reading_time = max(1, ceil($wordCount / 200));

            // Create revision
            BlogRevision::create([
                'blog_id' => $blog->id,
                'user_id' => Auth::id(),
                'content' => $blog->getOriginal('content'),
            ]);
        }
    }

    /**
     * Handle the Blog "updated" event.
     */
    public function updated(Blog $blog): void
    {
        $this->logActivity($blog, 'updated');
    }

    /**
     * Handle the Blog "deleted" event.
     */
    public function deleted(Blog $blog): void
    {
        $this->logActivity($blog, 'deleted');
    }

    /**
     * Handle the Blog "restored" event.
     */
    public function restored(Blog $blog): void
    {
        $this->logActivity($blog, 'restored');
    }

    /**
     * Handle the Blog "force deleted" event.
     */
    public function forceDeleted(Blog $blog): void
    {
        $this->logActivity($blog, 'force_deleted');
    }

    private function logActivity(Blog $blog, string $action)
    {
        BlogActivityLog::create([
            'blog_id' => $blog->id,
            'user_id' => Auth::id(),
            'action' => $action,
            'details' => json_encode($blog->getChanges()),
        ]);
    }
}
