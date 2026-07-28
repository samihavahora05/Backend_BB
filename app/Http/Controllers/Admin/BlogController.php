<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Blog;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Gate;

use App\Traits\PaginateQuery;
use App\Notifications\PlatformNotification;
use App\Models\User;

class BlogController extends Controller
{
    use PaginateQuery;

    public function index(Request $request)
    {
        $query = Blog::with('author:id,name');

        $paginated = $this->paginateWithMeta(
            $query,
            $request,
            ['title', 'created_at'],
            ['title', 'content']
        );

        return response()->json(array_merge(['success' => true], $paginated));
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'status' => 'required|in:published,draft',
            'image' => 'nullable|url'
        ]);

        $blog = Blog::create([
            'author_id' => $request->user()->id,
            'title' => $request->title,
            'slug' => Str::slug($request->title) . '-' . uniqid(),
            'content' => $request->content,
            'status' => $request->status,
            'image' => $request->image,
        ]);

        if ($blog->status === 'published') {
            $users = User::all();
            foreach ($users as $user) {
                $user->notify(new PlatformNotification(
                    "New Blog Published! 📝",
                    "Read our new post: '{$blog->title}' by " . $request->user()->name,
                    'blog_published',
                    ['blog_id' => $blog->id, 'slug' => $blog->slug]
                ));
            }
        }

        return response()->json($blog, 201);
    }


    public function show(string $id)
    {
        return response()->json(Blog::with('author:id,name')->findOrFail($id));
    }

    public function update(Request $request, string $id)
    {
        $blog = Blog::findOrFail($id);
        Gate::authorize('update', $blog);

        $request->validate([
            'title' => 'sometimes|string|max:255',
            'content' => 'sometimes|string',
            'status' => 'sometimes|in:published,draft',
            'image' => 'nullable|url'
        ]);

        if ($request->has('title') && $request->title !== $blog->title) {
            $blog->title = $request->title;
            $blog->slug = Str::slug($request->title) . '-' . uniqid();
        }

        $blog->fill($request->only(['content', 'status', 'image']));
        $blog->save();

        return response()->json($blog);
    }

    public function destroy(string $id)
    {
        $blog = Blog::findOrFail($id);
        Gate::authorize('delete', $blog);
        
        $blog->delete();

        return response()->json(['message' => 'Blog deleted successfully']);
    }
}
