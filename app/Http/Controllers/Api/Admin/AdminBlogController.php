<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Blog;
use App\Models\BlogCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;

class AdminBlogController extends Controller
{
    public function index(Request $request)
    {
        $query = Blog::with(['author', 'categories', 'tags']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('title', 'like', "%{$search}%")
                  ->orWhereHas('author', function($q) use ($search) {
                      $q->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%");
                  });
        }

        if ($request->filled('status') && $request->status !== 'All Statuses') {
            $query->where('status', strtolower($request->status));
        }

        if ($request->filled('category_id')) {
            $query->whereHas('categories', function($q) use ($request) {
                $q->where('blog_categories.id', $request->category_id);
            });
        }

        $blogs = $query->latest()->paginate($request->get('per_page', 15));

        return response()->json($blogs);
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'status' => 'required|string|in:draft,published,archived',
        ]);

        $blog = new Blog();
        $blog->title = $request->title;
        // slug is handled by observer
        $blog->content = $request->content;
        $blog->status = $request->status;
        $blog->author_id = $request->user()->id;
        $blog->excerpt = $request->excerpt;
        $blog->video_url = $request->video_url;
        $blog->allow_comments = filter_var($request->allow_comments ?? true, FILTER_VALIDATE_BOOLEAN);
        $blog->is_featured = filter_var($request->is_featured ?? false, FILTER_VALIDATE_BOOLEAN);
        $blog->is_trending = filter_var($request->is_trending ?? false, FILTER_VALIDATE_BOOLEAN);
        $blog->scheduled_at = $request->scheduled_at;
        
        if ($request->hasFile('thumbnail')) {
            $blog->thumbnail = $request->file('thumbnail')->store('blogs/thumbnails', 'public');
        }

        if ($request->has('gallery')) {
            $galleryPaths = [];
            foreach ($request->file('gallery', []) as $file) {
                $galleryPaths[] = $file->store('blogs/gallery', 'public');
            }
            $blog->gallery = $galleryPaths;
        }

        $blog->meta_title = $request->meta_title;
        $blog->meta_description = $request->meta_description;
        $blog->keywords = $request->keywords;
        $blog->canonical_url = $request->canonical_url;
        $blog->og_image = $request->og_image;
        
        $blog->save();

        if ($request->filled('categories')) {
            $cats = is_array($request->categories) ? $request->categories : explode(',', $request->categories);
            $blog->categories()->sync($cats);
        }

        if ($request->filled('tags')) {
            $tags = is_array($request->tags) ? $request->tags : explode(',', $request->tags);
            $blog->tags()->sync($tags);
        }

        return response()->json(['success' => true, 'data' => $blog]);
    }

    public function show($id)
    {
        $blog = Blog::with(['categories', 'tags'])->findOrFail($id);
        return response()->json(['success' => true, 'data' => $blog]);
    }

    public function update(Request $request, $id)
    {
        $blog = Blog::findOrFail($id);
        
        $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'status' => 'required|string|in:draft,published,archived',
        ]);

        $blog->title = $request->title;
        if ($request->filled('slug')) {
            $blog->slug = $request->slug;
        }
        $blog->content = $request->content;
        $blog->status = $request->status;
        if ($request->has('excerpt')) $blog->excerpt = $request->excerpt;
        if ($request->has('video_url')) $blog->video_url = $request->video_url;
        if ($request->has('allow_comments')) $blog->allow_comments = filter_var($request->allow_comments, FILTER_VALIDATE_BOOLEAN);
        if ($request->has('is_featured')) $blog->is_featured = filter_var($request->is_featured, FILTER_VALIDATE_BOOLEAN);
        if ($request->has('is_trending')) $blog->is_trending = filter_var($request->is_trending, FILTER_VALIDATE_BOOLEAN);
        if ($request->has('scheduled_at')) $blog->scheduled_at = $request->scheduled_at;
        
        if ($request->hasFile('thumbnail')) {
            if ($blog->thumbnail) Storage::disk('public')->delete($blog->thumbnail);
            $blog->thumbnail = $request->file('thumbnail')->store('blogs/thumbnails', 'public');
        }

        if ($request->hasFile('gallery')) {
            $galleryPaths = $blog->gallery ?? [];
            foreach ($request->file('gallery', []) as $file) {
                $galleryPaths[] = $file->store('blogs/gallery', 'public');
            }
            $blog->gallery = $galleryPaths;
        }

        if ($request->has('meta_title')) $blog->meta_title = $request->meta_title;
        if ($request->has('meta_description')) $blog->meta_description = $request->meta_description;
        if ($request->has('keywords')) $blog->keywords = $request->keywords;
        if ($request->has('canonical_url')) $blog->canonical_url = $request->canonical_url;
        if ($request->has('og_image')) $blog->og_image = $request->og_image;
        
        $blog->save();

        if ($request->has('categories')) {
            $cats = is_array($request->categories) ? $request->categories : explode(',', $request->categories);
            $blog->categories()->sync($cats);
        }

        if ($request->has('tags')) {
            $tags = is_array($request->tags) ? $request->tags : explode(',', $request->tags);
            $blog->tags()->sync($tags);
        }

        return response()->json(['success' => true, 'data' => $blog]);
    }

    public function destroy($id)
    {
        $blog = Blog::findOrFail($id);
        $blog->delete();
        return response()->json(['success' => true]);
    }

    public function dashboardMetrics()
    {
        return response()->json([
            'total_posts' => Blog::count(),
            'published' => Blog::where('status', 'published')->count(),
            'drafts' => Blog::where('status', 'draft')->count(),
            'total_views' => Blog::sum('views_count'),
        ]);
    }

    public function uploadImage(Request $request)
    {
        $request->validate([
            'image' => 'required|image|max:10240', // 10MB
        ]);

        $path = $request->file('image')->store('blogs/content', 'public');
        
        return response()->json([
            'url' => asset('storage/' . $path)
        ]);
    }

    public function action(Request $request, $id)
    {
        $blog = clone Blog::withTrashed()->findOrFail($id);
        $action = $request->input('action'); // publish, draft, archive, duplicate, restore, forceDelete

        switch ($action) {
            case 'publish':
                $blog->status = 'published';
                $blog->save();
                break;
            case 'draft':
                $blog->status = 'draft';
                $blog->save();
                break;
            case 'archive':
                $blog->status = 'archived';
                $blog->save();
                break;
            case 'duplicate':
                $newBlog = $blog->replicate();
                $newBlog->title = $newBlog->title . ' (Copy)';
                $newBlog->slug = null; // Let observer generate new slug
                $newBlog->status = 'draft';
                $newBlog->save();
                
                // Copy relationships
                if($blog->categories) $newBlog->categories()->sync($blog->categories->pluck('id'));
                if($blog->tags) $newBlog->tags()->sync($blog->tags->pluck('id'));
                
                return response()->json(['success' => true, 'data' => $newBlog]);
            case 'restore':
                $blog->restore();
                break;
            case 'forceDelete':
                $blog->forceDelete();
                break;
        }

        return response()->json(['success' => true]);
    }
}
