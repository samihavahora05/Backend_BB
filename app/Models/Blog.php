<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Blog extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'author_id',
        'title',
        'slug',
        'content',
        'thumbnail',
        'gallery',
        'status',
        'scheduled_at',
        'is_featured',
        'is_pinned',
        'is_trending',
        'allow_comments',
        'excerpt',
        'video_url',
        'meta_title',
        'meta_description',
        'canonical_url',
        'og_image',
        'keywords',
        'reading_time',
        'views_count',
        'likes_count',
        'comments_count',
    ];

    protected $casts = [
        'gallery' => 'array',
        'scheduled_at' => 'datetime',
        'is_featured' => 'boolean',
        'is_pinned' => 'boolean',
        'is_trending' => 'boolean',
        'allow_comments' => 'boolean',
    ];

    public function getThumbnailAttribute($value)
    {
        if (!$value) return null;
        if (str_starts_with($value, 'http')) return $value;
        return asset('storage/' . $value);
    }

    public function author()
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function categories()
    {
        return $this->belongsToMany(BlogCategory::class, 'blog_category_blog');
    }

    public function tags()
    {
        return $this->belongsToMany(BlogTag::class, 'blog_blog_tag');
    }

    public function comments()
    {
        return $this->hasMany(BlogComment::class);
    }

    public function likes()
    {
        return $this->hasMany(BlogLike::class);
    }

    public function views()
    {
        return $this->hasMany(BlogView::class);
    }

    public function revisions()
    {
        return $this->hasMany(BlogRevision::class);
    }

    public function activityLogs()
    {
        return $this->hasMany(BlogActivityLog::class);
    }

    /**
     * Generate a unique slug for a blog from a given title or custom slug.
     */
    public static function generateUniqueSlug(string $titleOrSlug, ?int $ignoreId = null): string
    {
        $baseSlug = Str::slug($titleOrSlug);
        if (empty($baseSlug)) {
            $baseSlug = 'untitled-post';
        }

        $slug = $baseSlug;
        $count = 1;

        while (static::withTrashed()
            ->where('slug', $slug)
            ->when($ignoreId, fn($q) => $q->where('id', '!=', $ignoreId))
            ->exists()) {
            $slug = "{$baseSlug}-{$count}";
            $count++;
        }

        return $slug;
    }
}
