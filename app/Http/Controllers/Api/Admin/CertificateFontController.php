<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\CertificateFont;
use Illuminate\Http\Request;

class CertificateFontController extends Controller
{
    public function index()
    {
        return response()->json(['success' => true, 'data' => CertificateFont::all()]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'font_file' => 'required|file|max:10240',
        ]);

        $path = $request->file('font_file')->store('certificates/fonts', 'public');

        $font = CertificateFont::create([
            'name' => $request->name,
            'file_path' => $path,
            'is_active' => true,
        ]);

        return response()->json(['success' => true, 'data' => $font]);
    }

    public function destroy($id)
    {
        CertificateFont::findOrFail($id)->delete();
        return response()->json(['success' => true, 'message' => 'Font deleted']);
    }
}
