<?php

namespace App\Exports;

use App\Models\CourseEnrollment;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class CourseEnrollmentsExport implements FromCollection, WithHeadings, WithMapping
{
    protected $status;
    protected $courseId;

    public function __construct($status = 'All', $courseId = '')
    {
        $this->status = $status;
        $this->courseId = $courseId;
    }

    public function collection()
    {
        $query = CourseEnrollment::with(['user', 'course']);
        
        if (!empty($this->status) && $this->status !== 'All') {
            $query->where('status', strtolower($this->status));
        }
        
        if (!empty($this->courseId)) {
            $query->where('course_id', $this->courseId);
        }

        return $query->get();
    }

    public function headings(): array
    {
        return [
            'Enrollment ID',
            'Student Name',
            'Email',
            'Course',
            'Status',
            'Date',
        ];
    }

    public function map($enrollment): array
    {
        $name   = trim(($enrollment->user->first_name ?? '') . ' ' . ($enrollment->user->last_name ?? ''));
        $email  = $enrollment->user->email ?? '';
        $course = $enrollment->course->title ?? '';

        return [
            $enrollment->id,
            $name ?: 'Unknown',
            $email,
            $course,
            $enrollment->status,
            $enrollment->created_at ? $enrollment->created_at->format('Y-m-d H:i:s') : '',
        ];
    }
}
