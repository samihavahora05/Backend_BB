<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\CertificateTemplate;
use Illuminate\Http\Request;

class CertificateTemplateController extends Controller
{
    public function index()
    {
        $templates = CertificateTemplate::all();

        // 1. Basic Template
        if (!CertificateTemplate::where('title', 'Basic Template')->exists()) {
            CertificateTemplate::create([
                'title'                 => 'Basic Template',
                'background_image_path' => 'certificates/templates/default_template.svg',
                'layout_settings'       => [
                    'title' => 'Certificate of Completion',
                    'showTitle' => true,
                    'elements' => [
                        ['id' => 'cert_title', 'name' => 'Certificate Title', 'content' => 'CERTIFICATE OF COMPLETION', 'positionX' => 50, 'positionY' => 22, 'width' => 85, 'fontFamily' => 'Cinzel, Georgia, serif', 'fontSize' => 38, 'fontWeight' => 800, 'fontStyle' => 'normal', 'fontColor' => '#0f172a', 'textAlignment' => 'center', 'letterSpacing' => 3, 'enabled' => true],
                        ['id' => 'header_subtitle', 'name' => 'Header Subtitle', 'content' => 'THIS CERTIFICATE IS PROUDLY PRESENTED TO', 'positionX' => 50, 'positionY' => 34, 'width' => 80, 'fontFamily' => 'Cinzel, serif', 'fontSize' => 15, 'fontWeight' => 700, 'fontStyle' => 'normal', 'fontColor' => '#475569', 'textAlignment' => 'center', 'letterSpacing' => 2, 'textTransform' => 'uppercase', 'enabled' => true],
                        ['id' => 'student_name', 'name' => 'Student Name', 'content' => '{student_name}', 'positionX' => 50, 'positionY' => 46, 'width' => 85, 'fontFamily' => 'Playfair Display, Georgia, serif', 'fontSize' => 52, 'fontWeight' => 'bold', 'fontStyle' => 'normal', 'fontColor' => '#0f172a', 'textAlignment' => 'center', 'letterSpacing' => 1, 'enabled' => true],
                        ['id' => 'course_title', 'name' => 'Course Title', 'content' => 'For successfully completing {course_title}', 'positionX' => 50, 'positionY' => 60, 'width' => 85, 'fontFamily' => 'Montserrat, sans-serif', 'fontSize' => 22, 'fontWeight' => 600, 'fontStyle' => 'normal', 'fontColor' => '#334155', 'textAlignment' => 'center', 'enabled' => true],
                        ['id' => 'issue_date', 'name' => 'Issue Date', 'content' => 'Issued on: {issue_date}', 'positionX' => 28, 'positionY' => 82, 'width' => 40, 'fontFamily' => 'Inter, sans-serif', 'fontSize' => 15, 'fontWeight' => 500, 'fontStyle' => 'normal', 'fontColor' => '#64748b', 'textAlignment' => 'center', 'enabled' => true],
                        ['id' => 'certificate_id', 'name' => 'Certificate ID', 'content' => 'Certificate ID: {certificate_id}', 'positionX' => 72, 'positionY' => 82, 'width' => 40, 'fontFamily' => 'Inter, sans-serif', 'fontSize' => 15, 'fontWeight' => 500, 'fontStyle' => 'normal', 'fontColor' => '#64748b', 'textAlignment' => 'center', 'enabled' => true]
                    ]
                ]
            ]);
        }

        // 2. AI Workshop
        if (!CertificateTemplate::where('title', 'AI Workshop')->exists()) {
            CertificateTemplate::create([
                'title'                 => 'AI Workshop',
                'background_image_path' => 'certificates/templates/default_green.svg',
                'layout_settings'       => [
                    'title'     => 'AI Workshop Certificate',
                    'showTitle' => true,
                ]
            ]);
        }

        // 3. Standard Certificate Template
        if (!CertificateTemplate::where('title', 'Standard Certificate Template')->exists()) {
            CertificateTemplate::create([
                'title'                 => 'Standard Certificate Template',
                'background_image_path' => 'certificates/templates/default_template.svg',
                'layout_settings'       => [
                    'title' => 'Certificate of Completion',
                    'showTitle' => true,
                    'elements' => [
                        ['id' => 'cert_title', 'name' => 'Certificate Title', 'content' => 'CERTIFICATE OF COMPLETION', 'positionX' => 50, 'positionY' => 22, 'width' => 85, 'fontFamily' => 'Cinzel, Georgia, serif', 'fontSize' => 38, 'fontWeight' => 800, 'fontStyle' => 'normal', 'fontColor' => '#0f172a', 'textAlignment' => 'center', 'letterSpacing' => 3, 'enabled' => true],
                        ['id' => 'header_subtitle', 'name' => 'Header Subtitle', 'content' => 'THIS CERTIFICATE IS PROUDLY PRESENTED TO', 'positionX' => 50, 'positionY' => 34, 'width' => 80, 'fontFamily' => 'Cinzel, serif', 'fontSize' => 15, 'fontWeight' => 700, 'fontStyle' => 'normal', 'fontColor' => '#475569', 'textAlignment' => 'center', 'letterSpacing' => 2, 'textTransform' => 'uppercase', 'enabled' => true],
                        ['id' => 'student_name', 'name' => 'Student Name', 'content' => '{student_name}', 'positionX' => 50, 'positionY' => 46, 'width' => 85, 'fontFamily' => 'Playfair Display, Georgia, serif', 'fontSize' => 52, 'fontWeight' => 'bold', 'fontStyle' => 'normal', 'fontColor' => '#0f172a', 'textAlignment' => 'center', 'letterSpacing' => 1, 'enabled' => true],
                        ['id' => 'course_title', 'name' => 'Course Title', 'content' => 'For successfully completing {course_title}', 'positionX' => 50, 'positionY' => 60, 'width' => 85, 'fontFamily' => 'Montserrat, sans-serif', 'fontSize' => 22, 'fontWeight' => 600, 'fontStyle' => 'normal', 'fontColor' => '#334155', 'textAlignment' => 'center', 'enabled' => true],
                        ['id' => 'issue_date', 'name' => 'Issue Date', 'content' => 'Issued on: {issue_date}', 'positionX' => 28, 'positionY' => 82, 'width' => 40, 'fontFamily' => 'Inter, sans-serif', 'fontSize' => 15, 'fontWeight' => 500, 'fontStyle' => 'normal', 'fontColor' => '#64748b', 'textAlignment' => 'center', 'enabled' => true],
                        ['id' => 'certificate_id', 'name' => 'Certificate ID', 'content' => 'Certificate ID: {certificate_id}', 'positionX' => 72, 'positionY' => 82, 'width' => 40, 'fontFamily' => 'Inter, sans-serif', 'fontSize' => 15, 'fontWeight' => 500, 'fontStyle' => 'normal', 'fontColor' => '#64748b', 'textAlignment' => 'center', 'enabled' => true]
                    ]
                ]
            ]);
        }

        $templates = CertificateTemplate::all();

        $templates->transform(function($tpl) {
            $bg = $tpl->background_image_path;
            if ($bg && !str_starts_with($bg, 'http')) {
                $filename = basename($bg);
                $bg = url('api/public/templates/background/' . $filename);
            }
            $tpl->bg_image = $bg;
            $tpl->background_image = $bg;
            return $tpl;
        });

        return response()->json(['success' => true, 'data' => $templates]);
    }

    public function show($id)
    {
        $template = CertificateTemplate::findOrFail($id);
        $bg = $template->background_image_path;
        if ($bg && !str_starts_with($bg, 'http')) {
            $bg = url('storage/' . $bg);
        }
        $template->bg_image = $bg;
        $template->background_image = $bg;
        return response()->json(['success' => true, 'data' => $template]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'title'            => 'required|string|max:255',
            'background_image' => 'required|image|max:10240',
        ]);

        $path = $request->file('background_image')->store('certificates/templates', 'public');

        $layoutSettings = json_decode($request->layout_settings, true);
        if (empty($layoutSettings) || !isset($layoutSettings['elements']) || count($layoutSettings['elements']) === 0) {
            $layoutSettings = [
                'title'     => $request->title,
                'showTitle' => true,
                'elements'  => [
                    ['id' => 'cert_title', 'name' => 'Certificate Title', 'content' => 'CERTIFICATE', 'positionX' => 50, 'positionY' => 20, 'width' => 85, 'fontFamily' => 'Montserrat, sans-serif', 'fontSize' => 48, 'fontWeight' => 800, 'fontStyle' => 'normal', 'fontColor' => '#0f172a', 'textAlignment' => 'center', 'letterSpacing' => 6, 'enabled' => true],
                    ['id' => 'cert_subtitle', 'name' => 'Certificate Subtitle', 'content' => 'OF ACHIEVEMENT', 'positionX' => 50, 'positionY' => 28, 'width' => 85, 'fontFamily' => 'Montserrat, sans-serif', 'fontSize' => 16, 'fontWeight' => 600, 'fontStyle' => 'normal', 'fontColor' => '#b45309', 'textAlignment' => 'center', 'letterSpacing' => 8, 'enabled' => true],
                    ['id' => 'header_subtitle', 'name' => 'Header Subtitle', 'content' => 'PROUDLY PRESENTED TO', 'positionX' => 50, 'positionY' => 37, 'width' => 80, 'fontFamily' => 'Montserrat, sans-serif', 'fontSize' => 13, 'fontWeight' => 600, 'fontStyle' => 'normal', 'fontColor' => '#64748b', 'textAlignment' => 'center', 'letterSpacing' => 4, 'enabled' => true],
                    ['id' => 'student_name', 'name' => 'Student Name', 'content' => '{student_name}', 'positionX' => 50, 'positionY' => 49, 'width' => 85, 'fontFamily' => 'Playfair Display, Georgia, serif', 'fontSize' => 58, 'fontWeight' => 'bold', 'fontStyle' => 'normal', 'fontColor' => '#0f172a', 'textAlignment' => 'center', 'letterSpacing' => 1, 'enabled' => true],
                    ['id' => 'course_title', 'name' => 'Course Title', 'content' => 'For successfully completing the course "{course_title}"', 'positionX' => 50, 'positionY' => 63, 'width' => 85, 'fontFamily' => 'Inter, sans-serif', 'fontSize' => 18, 'fontWeight' => 500, 'fontStyle' => 'normal', 'fontColor' => '#334155', 'textAlignment' => 'center', 'enabled' => true],
                    ['id' => 'issue_date', 'name' => 'Issue Date', 'content' => 'Issued: {issue_date}', 'positionX' => 25, 'positionY' => 83, 'width' => 35, 'fontFamily' => 'Inter, sans-serif', 'fontSize' => 14, 'fontWeight' => 500, 'fontStyle' => 'normal', 'fontColor' => '#64748b', 'textAlignment' => 'center', 'enabled' => true],
                    ['id' => 'certificate_id', 'name' => 'Certificate ID', 'content' => 'Verification ID: {certificate_id}', 'positionX' => 75, 'positionY' => 83, 'width' => 35, 'fontFamily' => 'Inter, sans-serif', 'fontSize' => 14, 'fontWeight' => 500, 'fontStyle' => 'normal', 'fontColor' => '#64748b', 'textAlignment' => 'center', 'enabled' => true]
                ]
            ];
        }

        $template = CertificateTemplate::create([
            'title'                 => $request->title,
            'background_image_path' => $path,
            'layout_settings'       => $layoutSettings,
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
