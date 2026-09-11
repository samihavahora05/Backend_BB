<?php

namespace App\Exports;

use App\Models\ExpertProfile;
use App\Models\User;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ExpertsExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles, WithTitle
{
    protected $experts;

    public function __construct($experts = null)
    {
        $this->experts = $experts;
    }

    public function collection()
    {
        if ($this->experts) {
            return $this->experts;
        }

        return ExpertProfile::whereHas('user', function($q) {
            $q->whereNull('deleted_at');
        })->with(['user'])->latest()->get();
    }

    public function title(): string
    {
        return 'Experts';
    }

    public function headings(): array
    {
        return [
            'Expert ID',
            'First Name',
            'Last Name',
            'Email',
            'Phone',
            'Designation',
            'Company',
            'Specialization / Expertise',
            'Hourly Rate (INR)',
            'Average Rating',
            'Total Reviews',
            'Status',
            'Verified',
            'Expert Photo',
            'Created Date',
            'Updated Date',
        ];
    }

    public function map($profile): array
    {
        $user = $profile->user;
        $firstName = $user ? $user->first_name : ($profile->first_name ?? 'Expert');
        $lastName = $user ? $user->last_name : ($profile->last_name ?? '');
        $email = $user ? $user->email : ($profile->email ?? '');
        $phone = $user ? ($user->phone ?? '') : ($profile->phone ?? '');
        $expertId = $user ? $user->id : $profile->id;

        // Determine image filename
        $photoFileName = '';
        $rawPhoto = $profile->profile_photo ?? null;
        if (!empty($rawPhoto)) {
            $photoFileName = static::generateSafeImageName($expertId, $firstName, $lastName, $rawPhoto);
        }

        return [
            $expertId,
            $firstName,
            $lastName,
            $email,
            $phone,
            $profile->designation ?? 'Expert',
            $profile->company ?? 'Independent',
            $profile->specialization ?? 'Career & Technical Mentorship',
            $profile->hourly_rate ?? 1500,
            number_format((float)($profile->average_rating ?? 5.0), 1),
            (int)($profile->total_reviews ?? 0),
            $profile->approval_status ?? ($user ? $user->status : 'active'),
            $profile->is_verified ? 'Yes' : 'No',
            $photoFileName,
            $profile->created_at ? $profile->created_at->format('Y-m-d H:i:s') : date('Y-m-d H:i:s'),
            $profile->updated_at ? $profile->updated_at->format('Y-m-d H:i:s') : date('Y-m-d H:i:s'),
        ];
    }

    public static function generateSafeImageName($id, $firstName, $lastName, $rawPath): string
    {
        $cleanName = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '_', trim($firstName . '_' . $lastName)));
        $cleanName = trim($cleanName, '_');
        if (empty($cleanName)) {
            $cleanName = 'expert';
        }

        // Determine extension
        $pathWithoutQuery = explode('?', (string)$rawPath)[0];
        $ext = strtolower(pathinfo($pathWithoutQuery, PATHINFO_EXTENSION));
        if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
            $ext = 'jpg';
        }

        return "expert_{$id}_{$cleanName}.{$ext}";
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle('A1:P1')->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
                'size' => 11,
            ],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => '1B2A6B'],
            ],
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
            ],
        ]);

        $sheet->getRowDimension(1)->setRowHeight(28);
        return [];
    }
}
