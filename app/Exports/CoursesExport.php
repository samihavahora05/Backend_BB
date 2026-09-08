<?php

namespace App\Exports;

use App\Models\Course;
use App\Support\StorageHelper;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class CoursesExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    public function collection()
    {
        return Course::with(['category', 'expert', 'level'])->latest()->get();
    }

    public function headings(): array
    {
        return [
            'ID',
            'Course Title',
            'Thumbnail Image URL',
            'Category',
            'Level',
            'Instructor',
            'Course Type',
            'Price (INR)',
            'Discount Price (INR)',
            'Duration',
            'Language',
            'Short Description',
            'Full Description',
            'Status',
            'Featured',
            'Created At',
        ];
    }

    public function map($course): array
    {
        $thumbnailUrl = null;
        if (!empty($course->thumbnail)) {
            $thumbnailUrl = StorageHelper::url($course->thumbnail);
            if ($thumbnailUrl && str_starts_with($thumbnailUrl, '/')) {
                $baseUrl = rtrim(config('app.url', env('APP_URL', 'https://backend.blueboxx.in')), '/');
                $thumbnailUrl = $baseUrl . $thumbnailUrl;
            }
        }

        $instructorName = trim(($course->expert->first_name ?? '') . ' ' . ($course->expert->last_name ?? ''));
        if (empty($instructorName)) {
            $instructorName = $course->expert->name ?? ($course->expert->email ?? 'Super Admin');
        }

        return [
            $course->id,
            $course->title,
            $thumbnailUrl,
            $course->category->name ?? 'Development',
            $course->level->title ?? 'All Levels',
            $instructorName,
            $course->course_type ?? 'Paid',
            $course->price ?? 0,
            $course->discount_price,
            $course->duration ?? '24 Hours',
            $course->language ?? 'English',
            $course->short_description ?? '',
            $course->description ?? '',
            $course->status ?? 'Published',
            $course->is_featured ? 'Yes' : 'No',
            $course->created_at ? $course->created_at->format('Y-m-d H:i:s') : '',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 11],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '1B2A6B']
                ],
                'alignment' => [
                    'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                    'vertical'   => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                ]
            ],
        ];
    }
}
