<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Controller;
use App\Models\Blog;
use App\Models\BlogCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PublicBlogController extends Controller
{
    /**
     * List published blogs with filters, search, and pagination
     * GET /api/public/blogs
     */
    public function index(Request $request)
    {
        $query = Blog::with(['author:id,name,profile_photo_path', 'categories:id,name,slug'])
            ->where('status', 'published')
            ->where(function($q) {
                $q->whereNull('scheduled_at')
                  ->orWhere('scheduled_at', '<=', now());
            });

        if ($s = $request->query('search')) {
            $query->where('title', 'like', "%{$s}%");
        }

        if ($cat = $request->query('category')) {
            $query->whereHas('categories', fn($q) => $q->where('slug', $cat));
        }

        if ($tag = $request->query('tag')) {
            $query->whereHas('tags', fn($q) => $q->where('slug', $tag));
        }

        $sort = $request->query('sort', 'latest');
        if ($sort === 'popular') {
            $query->orderByDesc('views_count');
        } else {
            $query->latest('published_at')->latest();
        }

        $perPage = min((int)$request->query('per_page', 9), 50);
        $blogs = $query->paginate($perPage);

        $data = $blogs->through(fn($b) => [
            'id'           => $b->id,
            'slug'         => $b->slug,
            'title'        => $b->title,
            'excerpt'      => $b->excerpt ?? \Str::limit(strip_tags($b->content), 120),
            'thumbnail'    => $b->thumbnail,
            'author'       => [
                'name'   => $b->author?->name,
                'avatar' => $b->author?->profile_photo_url,
            ],
            'categories'   => $b->categories->pluck('name'),
            'reading_time' => $b->reading_time,
            'views_count'  => $b->views_count,
            'published_at' => $b->created_at->format('M d, Y'),
        ]);

        return response()->json([
            'success' => true,
            'data'    => $data->items(),
            'pagination' => [
                'current_page' => $data->currentPage(),
                'last_page'    => $data->lastPage(),
                'total'        => $data->total(),
            ]
        ]);
    }

    /**
     * Get blog details by slug
     * GET /api/public/blogs/{slug}
     */
    public function show(Request $request, $slug)
    {
        $blog = Blog::with(['author:id,name,profile_photo_path,bio', 'categories:id,name,slug', 'tags:id,name,slug'])
            ->where('status', 'published')
            ->where('slug', $slug)
            ->firstOrFail();

        // High performance async-style view counter using DB direct query to avoid touching the model timestamps
        DB::table('blogs')->where('id', $blog->id)->increment('views_count');
        
        // Track unique views if you wish (optional)
        DB::table('blog_views')->insertOrIgnore([
            'blog_id'    => $blog->id,
            'ip_address' => $request->ip(),
            'user_agent' => substr($request->userAgent() ?? '', 0, 255),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'data'    => [
                'id'               => $blog->id,
                'slug'             => $blog->slug,
                'title'            => $blog->title,
                'content'          => $blog->content,
                'thumbnail'        => $blog->thumbnail,
                'gallery'          => $blog->gallery,
                'video_url'        => $blog->video_url,
                'author'           => [
                    'name'   => $blog->author?->name,
                    'avatar' => $blog->author?->profile_photo_url,
                    'bio'    => $blog->author?->bio,
                ],
                'categories'       => $blog->categories,
                'tags'             => $blog->tags,
                'reading_time'     => $blog->reading_time,
                'views_count'      => $blog->views_count + 1,
                'likes_count'      => $blog->likes_count,
                'comments_count'   => $blog->comments_count,
                'published_at'     => $blog->created_at->format('F j, Y'),
                'meta_title'       => $blog->meta_title ?? $blog->title,
                'meta_description' => $blog->meta_description ?? $blog->excerpt,
                'keywords'         => $blog->keywords,
                'og_image'         => $blog->og_image ?? $blog->thumbnail,
            ]
        ]);
    }

    /**
     * Get all active categories for the sidebar
     * GET /api/public/blog-categories
     */
    public function categories()
    {
        $categories = BlogCategory::where('status', 'active')
            ->withCount(['blogs' => fn($q) => $q->where('status', 'published')])
            ->having('blogs_count', '>', 0)
            ->orderByDesc('blogs_count')
            ->get(['id', 'name', 'slug', 'blogs_count']);

        return response()->json(['success' => true, 'data' => $categories]);
    }
}
