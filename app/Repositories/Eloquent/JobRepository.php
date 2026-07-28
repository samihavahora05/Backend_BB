<?php

namespace App\Repositories\Eloquent;

use App\Models\Job;
use App\Models\JobApplication;
use App\Repositories\Contracts\JobRepositoryInterface;
use Illuminate\Support\Facades\DB;

class JobRepository implements JobRepositoryInterface
{
    public function getAllJobs(array $filters = [], int $perPage = 15)
    {
        $query = Job::with(['company'])->latest();

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where('title', 'like', "%{$search}%")
                  ->orWhere('department', 'like', "%{$search}%")
                  ->orWhere('job_id_prefix', 'like', "%{$search}%");
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        
        if (!empty($filters['company_id'])) {
            $query->where('company_id', $filters['company_id']);
        }

        return $query->paginate($perPage);
    }

    public function getJobById(int $jobId)
    {
        return Job::with(['company', 'documents'])->findOrFail($jobId);
    }

    public function createJob(array $data)
    {
        // Auto-generate job prefix if not provided
        if (empty($data['job_id_prefix'])) {
            $data['job_id_prefix'] = 'JOB-' . date('Y') . '-' . strtoupper(substr(uniqid(), -5));
        }

        if (empty($data['company_id'])) {
            $data['company_id'] = auth()->id() ?? 1;
        }

        return Job::create($data);
    }

    public function updateJob(int $jobId, array $data)
    {
        $job = Job::findOrFail($jobId);
        $job->update($data);
        return $job;
    }

    public function deleteJob(int $jobId)
    {
        return Job::findOrFail($jobId)->delete();
    }

    public function getJobApplications(int $jobId, array $filters = [], int $perPage = 15)
    {
        $query = JobApplication::with(['user.studentProfile', 'interviews', 'offer'])
            ->where('job_id', $jobId)
            ->latest();

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->paginate($perPage);
    }

    public function getApplicationById(int $applicationId)
    {
        return JobApplication::with(['user.studentProfile', 'job', 'interviews', 'offer'])
            ->findOrFail($applicationId);
    }

    public function getDashboardMetrics()
    {
        return [
            'total_jobs' => Job::count(),
            'active_jobs' => Job::where('status', 'active')->count(),
            'pending_jobs' => Job::where('status', 'pending_approval')->count(),
            'total_applications' => JobApplication::count(),
            'offers_sent' => \App\Models\JobOffer::count(),
            'hiring_rate' => $this->calculateHiringRate(),
        ];
    }
    
    private function calculateHiringRate()
    {
        $total = JobApplication::count();
        if ($total === 0) return 0;
        
        $hired = JobApplication::whereIn('status', ['accepted', 'joined', 'completed'])->count();
        return round(($hired / $total) * 100, 2);
    }
}
