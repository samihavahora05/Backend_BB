<?php

namespace App\Exports;

use App\Models\CourseLevel;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class CourseLevelsExport implements FromCollection, WithHeadings, WithMapping
{
    public function collection()
    {
        return CourseLevel::orderBy('position', 'asc')->get();
    }

    public function headings(): array
    {
        return [
            'ID',
            'Title',
            'Slug',
            'Position',
            'Status',
            'Created At',
        ];
    }

    public function map($level): array
    {
        return [
            $level->id,
            $level->title,
            $level->slug,
            $level->position,
            $level->status,
            $level->created_at->format('Y-m-d H:i:s'),
        ];
    }
}
