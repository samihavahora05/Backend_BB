<?php

namespace App\Exports;

use App\Models\CourseQuestion;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class CourseQAExport implements FromCollection, WithHeadings, WithMapping
{
    public function collection()
    {
        return CourseQuestion::with(['student', 'course'])->withCount('answers')->get();
    }

    public function headings(): array
    {
        return [
            'ID',
            'Course',
            'Student',
            'Title',
            'Question',
            'Status',
            'Answers Count',
            'Is Pinned',
            'Is Reported',
            'Created At',
        ];
    }

    public function map($qa): array
    {
        return [
            $qa->id,
            $qa->course ? $qa->course->title : 'Unknown',
            $qa->student ? $qa->student->name : 'Unknown',
            $qa->title,
            $qa->question,
            $qa->status,
            $qa->answers_count,
            $qa->is_pinned ? 'Yes' : 'No',
            $qa->is_reported ? 'Yes' : 'No',
            $qa->created_at->format('Y-m-d H:i:s'),
        ];
    }
}
