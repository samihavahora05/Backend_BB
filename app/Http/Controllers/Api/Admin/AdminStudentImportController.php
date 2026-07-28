<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\BulkImportStudentRequest;
use App\Services\StudentService;
use Illuminate\Http\Request;

class AdminStudentImportController extends Controller
{
    protected $service;

    public function __construct(StudentService $service)
    {
        $this->service = $service;
    }

    public function import(BulkImportStudentRequest $request)
    {
        $result = $this->service->importFromExcel($request->file('file'), $request->user()->id);
        
        return response()->json([
            'success' => true,
            'message' => "Imported {$result['success_count']} students. Failed: {$result['failed_count']}",
            'data' => $result
        ]);
    }
}
