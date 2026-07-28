<?php

namespace App\Services;

use App\Repositories\Contracts\StudentRepositoryInterface;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Models\User;
use App\Models\StudentActivityLog;

class StudentService
{
    protected StudentRepositoryInterface $repository;

    public function __construct(StudentRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    public function logActivity(int $userId, string $action, ?string $details = null)
    {
        StudentActivityLog::create([
            'user_id' => $userId,
            'action' => $action,
            'details' => $details,
            'ip_address' => request()->ip()
        ]);
    }

    public function toggleStatus(int $userId, string $status, int $adminId)
    {
        $student = $this->repository->updateStudent($userId, ['status' => $status]);
        $this->logActivity($userId, "Status changed to $status by Admin #$adminId");
        return $student;
    }

    public function resetPassword(int $userId, int $adminId)
    {
        $newPassword = Str::random(10);
        
        $student = $this->repository->updateStudent($userId, [
            'password' => $newPassword
        ]);

        $this->logActivity($userId, "Password reset by Admin #$adminId");
        
        // TODO: Dispatch Email Job
        // \Illuminate\Support\Facades\Mail::to($student->email)->send(new \App\Mail\StudentPasswordReset($newPassword));

        return $newPassword; // Return so admin can copy/paste it or verify
    }

    public function importFromExcel($file, int $adminId)
    {
        // Simple CSV parser
        $path = $file->getRealPath();
        $fileHandle = fopen($path, 'r');
        
        if ($fileHandle === false) {
            throw new \Exception("Could not open the file.");
        }
        
        $headers = fgetcsv($fileHandle);
        if (!$headers) {
            throw new \Exception("File is empty or invalid format.");
        }
        
        // Normalize headers
        $headers = array_map(function($header) {
            return strtolower(trim(str_replace(' ', '_', $header)));
        }, $headers);
        
        $successCount = 0;
        $failedCount = 0;
        
        while (($row = fgetcsv($fileHandle)) !== false) {
            if (count($headers) !== count($row)) {
                $failedCount++;
                continue;
            }
            
            $data = array_combine($headers, $row);
            
            // Expected columns from template: Name, Email, Phone, Gender, Date of Birth, Student Type, Institute
            // We map these to our required fields
            
            if (empty($data['email']) || empty($data['name'])) {
                $failedCount++;
                continue;
            }
            
            // Skip if email exists
            if (User::where('email', $data['email'])->exists()) {
                $failedCount++;
                continue;
            }
            
            // Split name
            $nameParts = explode(' ', trim($data['name']), 2);
            $firstName = $nameParts[0];
            $lastName = $nameParts[1] ?? '';
            
            $studentData = [
                'first_name' => $firstName,
                'last_name' => $lastName,
                'email' => $data['email'],
                'phone' => $data['phone'] ?? null,
                'password' => Str::random(10), // auto generate password
                'status' => 'active',
                'gender' => isset($data['gender']) ? strtolower(trim($data['gender'])) : null,
                'date_of_birth' => $data['date_of_birth'] ?? null,
                'student_type' => $data['student_type'] ?? null,
                'college_name' => $data['institute'] ?? null,
            ];
            
            try {
                $this->repository->createStudent($studentData);
                $successCount++;
            } catch (\Exception $e) {
                $failedCount++;
            }
        }
        
        fclose($fileHandle);
        
        $this->logActivity($adminId, "Imported $successCount students. Failed: $failedCount");
        
        return [
            'success_count' => $successCount,
            'failed_count' => $failedCount
        ];
    }
}
