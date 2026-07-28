<?php

namespace App\Exports;

use App\Models\Course;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class CoursesExport implements FromCollection, WithHeadings, WithMapping
{
    public function collection()
    {
        return Course::with(['category', 'expert'])->latest()->get();
    }

    public function headings(): array
    {
        return [
            'ID',
            'Title',
            'Category',
            'Instructor',
            'Type',
            'Price',
            'Status',
            'Featured',
            'Created At',
        ];
    }

    public function map($course): array
    {
        return [
            $course->id,
            $course->title,
            $course->category->name ?? 'N/A',
            trim(($course->expert->first_name ?? '') . ' ' . ($course->expert->last_name ?? '')),
            $course->course_type,
            $course->price,
            $course->status,
            $course->is_featured ? 'Yes' : 'No',
            $course->created_at->format('Y-m-d H:i:s'),
        ];
    }
}
