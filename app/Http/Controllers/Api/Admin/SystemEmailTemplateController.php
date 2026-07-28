<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\SystemEmailTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SystemEmailTemplateController extends Controller
{
    public function index(Request $request)
    {
        $query = SystemEmailTemplate::query();

        if ($request->filled('search')) {
            $query->where('name', 'like', "%{$request->search}%")
                  ->orWhere('subject', 'like', "%{$request->search}%");
        }

        $templates = $query->latest()->get();

        return response()->json(['success' => true, 'data' => $templates]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'subject' => 'required|string|max:255',
            'body' => 'required|string',
        ]);

        $data = $request->all();
        $data['slug'] = Str::slug($request->name);

        $template = SystemEmailTemplate::create($data);

        return response()->json(['success' => true, 'data' => $template]);
    }

    public function show($id)
    {
        $template = SystemEmailTemplate::findOrFail($id);
        return response()->json(['success' => true, 'data' => $template]);
    }

    public function update(Request $request, $id)
    {
        $template = SystemEmailTemplate::findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255',
            'subject' => 'required|string|max:255',
            'body' => 'required|string',
        ]);

        $data = $request->all();
        if ($request->has('name')) {
            $data['slug'] = Str::slug($request->name);
        }

        $template->update($data);

        return response()->json(['success' => true, 'data' => $template]);
    }

    public function destroy($id)
    {
        $template = SystemEmailTemplate::findOrFail($id);
        $template->delete();

        return response()->json(['success' => true]);
    }
}
