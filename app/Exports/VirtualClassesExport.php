<?php

namespace App\Exports;

use App\Models\VirtualClass;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class VirtualClassesExport implements FromCollection, WithHeadings, WithMapping
{
    protected $status;

    public function __construct($status = null)
    {
        $this->status = $status;
    }

    public function collection()
    {
        $query = VirtualClass::with(['instructor', 'category']);
        
        if ($this->status && $this->status !== 'All') {
            $query->where('status', strtolower($this->status));
        }

        return $query->latest('start_datetime')->get();
    }

    public function headings(): array
    {
        return [
            'ID',
            'Title',
            'Instructor',
            'Category',
            'Language',
            'Duration (min)',
            'Max Students',
            'Enrolled',
            'Start Date',
            'Status',
            'Platform',
        ];
    }

    public function map($c): array
    {
        return [
            $c->id,
            $c->title,
            $c->instructor ? "{$c->instructor->first_name} {$c->instructor->last_name}" : '',
            $c->category?->name ?? '',
            $c->language,
            $c->duration_minutes,
            $c->max_students,
            $c->enrolled_count,
            $c->start_datetime ? $c->start_datetime->format('Y-m-d H:i') : '',
            $c->status,
            $c->platform,
        ];
    }
}
