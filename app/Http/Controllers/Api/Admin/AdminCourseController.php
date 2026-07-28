<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Services\CourseService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;

class AdminCourseController extends Controller
{
    protected $courseService;

    public function __construct(CourseService $courseService)
    {
        $this->courseService = $courseService;
    }

    public function index(Request $request)
    {
        $courses = $this->courseService->getCourses($request->all());
        
        // Match the frontend SWR format expectations
        return response()->json([
            'success' => true,
            'data' => $courses->items(),
            'pagination' => [
                'current_page' => $courses->currentPage(),
                'last_page' => $courses->lastPage(),
                'per_page' => $courses->perPage(),
                'total' => $courses->total()
            ]
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'category_id' => 'required|exists:course_categories,id',
            'level_id' => 'nullable|exists:course_levels,id',
            'expert_id' => 'nullable|exists:users,id',
            'title' => 'required|string|max:255',
            'short_description' => 'nullable|string',
            'description' => 'nullable|string',
            'thumbnail' => 'nullable|image|max:2048',
            'preview_video_url' => 'nullable|string|max:255',
            'demo_pdf_url' => 'nullable|string|max:255',
            'landing_page_url' => 'nullable|url',
            'price' => 'nullable|numeric|min:0',
            'discount_price' => 'nullable|numeric|min:0',
            'course_type' => ['nullable', Rule::in(['Free', 'Paid'])],
            'language' => 'nullable|string|max:100',
            'duration' => 'nullable|string|max:255',
            'duration_hours' => 'nullable|integer',
            'status' => ['nullable', Rule::in(['Draft', 'Published', 'Private', 'Pending Approval', 'Rejected'])],
            'is_featured' => 'nullable|boolean',
            'is_archived' => 'nullable|boolean',
            'is_published' => 'nullable|boolean',
        ]);

        if (empty($data['expert_id'])) {
            $data['expert_id'] = auth()->id() ?? 1;
        }
        
        $data['price'] = $data['price'] ?? 0;
        $data['discount_price'] = $data['discount_price'] ?? 0;

        $course = $this->courseService->createCourse($data);
        return response()->json(['success' => true, 'message' => 'Course created successfully', 'data' => $course], 201);
    }

    public function show($id)
    {
        $course = Course::with(['category', 'level', 'expert'])->findOrFail($id);
        return response()->json(['success' => true, 'data' => $course]);
    }

    public function update(Request $request, $id)
    {
        $course = Course::findOrFail($id);

        $data = $request->validate([
            'category_id' => 'nullable|exists:course_categories,id',
            'level_id' => 'nullable|exists:course_levels,id',
            'expert_id' => 'nullable|exists:users,id',
            'title' => 'nullable|string|max:255',
            'short_description' => 'nullable|string',
            'description' => 'nullable|string',
            'thumbnail' => 'nullable|image|max:2048',
            'preview_video_url' => 'nullable|string|max:255',
            'demo_pdf_url' => 'nullable|string|max:255',
            'landing_page_url' => 'nullable|url',
            'price' => 'nullable|numeric|min:0',
            'discount_price' => 'nullable|numeric|min:0',
            'course_type' => ['nullable', Rule::in(['Free', 'Paid'])],
            'language' => 'nullable|string|max:100',
            'duration' => 'nullable|string|max:255',
            'duration_hours' => 'nullable|integer',
            'status' => ['nullable', Rule::in(['Draft', 'Published', 'Private', 'Pending Approval', 'Rejected'])],
            'is_featured' => 'nullable|boolean',
            'is_archived' => 'nullable|boolean',
            'is_published' => 'nullable|boolean',
        ]);

        $course = $this->courseService->updateCourse($course, $data);
        return response()->json(['success' => true, 'message' => 'Course updated successfully', 'data' => $course]);
    }

    public function destroy($id)
    {
        $course = Course::findOrFail($id);
        $this->courseService->deleteCourse($course);
        return response()->json(['status' => 'success', 'message' => 'Course deleted successfully']);
    }

    public function bulkDelete(Request $request)
    {
        $request->validate(['ids' => 'required|array']);
        $this->courseService->bulkDelete($request->ids);
        return response()->json(['status' => 'success', 'message' => 'Courses deleted successfully']);
    }

    public function bulkStatus(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'status' => ['required', Rule::in(['Draft', 'Published', 'Private', 'Pending Approval', 'Rejected'])]
        ]);
        $this->courseService->bulkStatus($request->ids, $request->status);
        return response()->json(['status' => 'success', 'message' => 'Status updated successfully']);
    }

    public function duplicate($id)
    {
        $course = $this->courseService->duplicateCourse($id);
        return response()->json(['status' => 'success', 'message' => 'Course duplicated successfully', 'data' => $course]);
    }

    public function updateStatus(Request $request, $id)
    {
        $request->validate(['status' => ['required', Rule::in(['Draft', 'Published', 'Private', 'Pending Approval', 'Rejected'])]]);
        $course = Course::findOrFail($id);
        $this->courseService->updateStatus($course, $request->status);
        return response()->json(['status' => 'success', 'message' => 'Status updated']);
    }

    public function toggleArchive($id)
    {
        $course = Course::findOrFail($id);
        $this->courseService->toggleArchive($course);
        return response()->json(['status' => 'success', 'message' => $course->is_archived ? 'Course archived' : 'Course unarchived']);
    }

    public function export(Request $request)
    {
        $format = $request->query('format', 'csv');
        $export = new \App\Exports\CoursesExport();
        
        if ($format === 'excel') {
            return \Maatwebsite\Excel\Facades\Excel::download($export, 'courses.xlsx', \Maatwebsite\Excel\Excel::XLSX);
        }
        
        if ($format === 'pdf') {
            $courses = Course::with(['category', 'expert'])->latest()->get();
            $html = '<html><head><title>Courses Export</title><style>body { font-family: sans-serif; } table {width:100%; border-collapse: collapse; margin-top: 20px;} th, td {border:1px solid #ddd; padding:8px; text-align:left; font-size: 12px;} th {background:#f4f4f4;} @media print { button { display: none; } }</style></head><body onload="window.print()">';
            $html .= '<div style="display: flex; justify-content: space-between; align-items: center;"><h2>Courses Export</h2><button onclick="window.print()" style="padding: 8px 16px; background: #1B2A6B; color: white; border: none; border-radius: 4px; cursor: pointer;">Print to PDF</button></div>';
            $html .= '<table><tr><th>ID</th><th>Title</th><th>Category</th><th>Instructor</th><th>Type</th><th>Price</th><th>Status</th></tr>';
            foreach($courses as $c) {
                $html .= "<tr><td>{$c->id}</td><td>{$c->title}</td><td>".($c->category->name ?? 'N/A')."</td><td>".trim(($c->expert->first_name ?? '').' '.($c->expert->last_name ?? ''))."</td><td>{$c->course_type}</td><td>{$c->price}</td><td>{$c->status}</td></tr>";
            }
            $html .= '</table></body></html>';
            return response($html)->header('Content-Type', 'text/html');
        }

        return \Maatwebsite\Excel\Facades\Excel::download($export, 'courses.csv', \Maatwebsite\Excel\Excel::CSV);
    }
}
