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
            $blog->slug = Blog::generateUniqueSlug($blog->title ?? 'untitled-post');
        } else {
            $blog->slug = Blog::generateUniqueSlug($blog->slug);
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
        // Ensure slug uniqueness if changed
        if ($blog->isDirty('slug')) {
            if (empty($blog->slug)) {
                $blog->slug = Blog::generateUniqueSlug($blog->title ?? 'untitled-post', $blog->id);
            } else {
                // Ensure the provided slug is sanitized and unique
                $baseSlug = Str::slug($blog->slug);
                if (Blog::withTrashed()->where('slug', $baseSlug)->where('id', '!=', $blog->id)->exists()) {
                    $blog->slug = Blog::generateUniqueSlug($baseSlug, $blog->id);
                } else {
                    $blog->slug = $baseSlug;
                }
            }
        }

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
        if (!$blog->isForceDeleting()) {
            $this->logActivity($blog, 'deleted');
        }
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
        // Activity log cannot reference force-deleted blog row
    }

    private function logActivity(Blog $blog, string $action)
    {
        $userId = Auth::id() ?: $blog->author_id;
        if ($userId && $blog->exists) {
            try {
                BlogActivityLog::create([
                    'blog_id' => $blog->id,
                    'user_id' => $userId,
                    'action' => $action,
                    'details' => json_encode($blog->getChanges()),
                ]);
            } catch (\Throwable $e) {
                // Silently ignore activity log failure to prevent breaking main blog operations
            }
        }
    }
}
