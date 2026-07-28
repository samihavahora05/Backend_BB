<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\DeleteRequestService;
use App\Models\DeleteRequest;

class AdminDeleteRequestController extends Controller
{
    protected $service;

    public function __construct(DeleteRequestService $service)
    {
        $this->service = $service;
    }

    public function index()
    {
        $requests = $this->service->getAllRequests();
        return response()->json($requests);
    }

    public function show($id)
    {
        $request = DeleteRequest::with('user', 'logs')->findOrFail($id);
        return response()->json($request);
    }

    public function approve($id)
    {
        $request = DeleteRequest::findOrFail($id);
        
        try {
            $this->service->approveRequest($request);
            return response()->json(['message' => 'Account deletion approved and user purged successfully.']);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function reject(Request $req, $id)
    {
        $validated = $req->validate([
            'notes' => 'required|string',
        ]);

        $request = DeleteRequest::findOrFail($id);
        
        try {
            $this->service->rejectRequest($request, $validated['notes']);
            return response()->json(['message' => 'Account deletion rejected successfully.']);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function export()
    {
        $requests = $this->service->getAllRequests();
        
        $headers = ['ID', 'User ID', 'Name', 'Email', 'Status', 'Reason', 'Admin Notes', 'Requested At'];
        
        $callback = function() use ($requests, $headers) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $headers);
            
            foreach ($requests as $req) {
                $name = $req->user ? $req->user->first_name . ' ' . $req->user->last_name : 'Unknown';
                $email = $req->user ? $req->user->email : 'Unknown';
                fputcsv($file, [
                    $req->id,
                    $req->user_id,
                    $name,
                    $email,
                    strtoupper($req->status),
                    $req->reason,
                    $req->notes,
                    $req->created_at?->format('Y-m-d H:i:s'),
                ]);
            }
            fclose($file);
        };

        return response()->streamDownload($callback, 'delete_requests_export_' . now()->format('Y-m-d_H-i-s') . '.csv', [
            'Content-Type' => 'text/csv',
            'Cache-Control' => 'no-cache, must-revalidate',
        ]);
    }
}
