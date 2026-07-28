<?php

namespace App\Exports;

use App\Models\CourseCategory;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class CourseCategoriesExport implements FromCollection, WithHeadings, WithMapping
{
    public function collection()
    {
        return CourseCategory::with('parent')->withCount('courses')->get();
    }

    public function headings(): array
    {
        return [
            'ID',
            'Name',
            'Slug',
            'Parent',
            'Position',
            'Status',
            'Courses Count',
            'Created At',
        ];
    }

    public function map($category): array
    {
        return [
            $category->id,
            $category->name,
            $category->slug,
            $category->parent ? $category->parent->name : 'None',
            $category->position,
            $category->status,
            $category->courses_count,
            $category->created_at->format('Y-m-d H:i:s'),
        ];
    }
}
