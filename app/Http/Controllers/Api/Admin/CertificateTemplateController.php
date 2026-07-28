<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\CertificateTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CertificateTemplateController extends Controller
{
    public function index()
    {
        return response()->json(['success' => true, 'data' => CertificateTemplate::all()]);
    }

    public function show($id)
    {
        return response()->json(['success' => true, 'data' => CertificateTemplate::findOrFail($id)]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'background_image' => 'required|image|max:10240',
        ]);

        $path = $request->file('background_image')->store('certificates/templates', 'public');

        $template = CertificateTemplate::create([
            'title' => $request->title,
            'background_image_path' => $path,
            'layout_settings' => json_decode($request->layout_settings, true) ?? [],
        ]);

        return response()->json(['success' => true, 'data' => $template]);
    }

    public function update(Request $request, $id)
    {
        $template = CertificateTemplate::findOrFail($id);
        
        $data = ['title' => $request->title ?? $template->title];
        
        if ($request->hasFile('background_image')) {
            $data['background_image_path'] = $request->file('background_image')->store('certificates/templates', 'public');
        }

        if ($request->has('layout_settings')) {
            $data['layout_settings'] = json_decode($request->layout_settings, true);
        }

        $template->update($data);

        return response()->json(['success' => true, 'data' => $template]);
    }

    public function destroy($id)
    {
        CertificateTemplate::findOrFail($id)->delete();
        return response()->json(['success' => true, 'message' => 'Template deleted']);
    }
}
