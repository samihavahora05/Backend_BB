<?php

namespace App\Exports;

use App\Models\StudentProgress;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class MCQResultsExport implements FromCollection, WithHeadings, WithMapping
{
    public function collection()
    {
        return StudentProgress::with(['user', 'course'])
            ->whereNotNull('average_quiz_score')
            ->get();
    }

    public function headings(): array
    {
        return [
            'Student Name',
            'Email',
            'Course',
            'Score (%)',
            'Status',
            'Date',
        ];
    }

    public function map($row): array
    {
        $name   = trim(($row->user->first_name ?? '') . ' ' . ($row->user->last_name ?? ''));
        $email  = $row->user->email ?? '';
        $course = $row->course->title ?? '';
        $score  = round($row->average_quiz_score ?? 0);
        $status = $score >= 50 ? 'Passed' : 'Failed';
        $date   = $row->updated_at ? $row->updated_at->format('Y-m-d') : '';

        return [
            $name,
            $email,
            $course,
            $score,
            $status,
            $date,
        ];
    }
}
