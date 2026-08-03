<?php

namespace App\Http\Controllers;

use App\Models\Blog;
use App\Models\BlogComment;
use App\Models\BlogLike;
use Illuminate\Http\Request;

use App\Traits\PaginateQuery;

class BlogController extends Controller
{
    use PaginateQuery;

    /**
     * Public method to view published blogs
     */
    public function index(Request $request)
    {
        $query = Blog::where('status', 'published')
            ->withCount('likes')
            ->withCount('comments')
            ->with('author:id,name');

        $paginated = $this->paginateWithMeta(
            $query,
            $request,
            ['title', 'created_at'],
            ['title', 'content']
        );
            
        return response()->json(array_merge(['success' => true], $paginated));
    }


    /**
     * Public method to view a specific blog with comments
     */
    public function show($slug)
    {
        $blog = Blog::where('slug', $slug)
            ->where('status', 'published')
            ->withCount('likes')
            ->with('author:id,name')
            ->firstOrFail();
            
        // Fetch comments separately for pagination if needed, or just attach them
        $comments = BlogComment::where('blog_id', $blog->id)
            ->with('user:id,name')
            ->latest()
            ->get();
            
        return response()->json([
            'blog' => $blog,
            'comments' => $comments
        ]);
    }

    /**
     * Auth method to toggle like on a blog
     */
    public function toggleLike(Request $request, $id)
    {
        $blog = Blog::findOrFail($id);
        $user_id = $request->user()->id;
        
        $like = BlogLike::where('blog_id', $blog->id)->where('user_id', $user_id)->first();
        
        if ($like) {
            $like->delete();
            $blog->decrement('likes_count');
            return response()->json(['message' => 'Blog unliked successfully', 'likes_count' => $blog->likes_count]);
        } else {
            BlogLike::create([
                'blog_id' => $blog->id,
                'user_id' => $user_id
            ]);
            $blog->increment('likes_count');
            return response()->json(['message' => 'Blog liked successfully', 'likes_count' => $blog->likes_count]);
        }
    }

    /**
     * Auth method to add a comment
     */
    public function addComment(Request $request, $id)
    {
        $request->validate([
            'content' => 'required|string|max:1000'
        ]);

        $blog = Blog::findOrFail($id);
        
        $comment = BlogComment::create([
            'blog_id' => $blog->id,
            'user_id' => $request->user()->id,
            'content' => $request->content
        ]);

        $blog->increment('comments_count');

        return response()->json([
            'message' => 'Comment added successfully',
            'data' => $comment->load('user:id,first_name,last_name')
        ], 201);
    }
}
