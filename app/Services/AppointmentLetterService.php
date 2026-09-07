<?php

namespace App\Services;

use App\Models\AppointmentLetter;
use App\Models\InternshipApplication;
use App\Models\User;
use App\Models\AuditLog;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AppointmentLetterService
{
    /**
     * Format working days array/string to professional text.
     */
    public function formatWorkingDays($days): string
    {
        if (is_string($days) && !empty($days)) {
            return $days;
        }
        if (!is_array($days) || empty($days)) {
            return 'Monday to Friday';
        }

        $standard = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];
        if ($days === $standard || count(array_diff($standard, $days)) === 0 && count(array_diff($days, $standard)) === 0) {
            return 'Monday to Friday';
        }
        if (count($days) === 1) {
            return $days[0];
        }
        if (count($days) === 2) {
            return $days[0] . ' and ' . $days[1];
        }
        $last = array_pop($days);
        return implode(', ', $days) . ' and ' . $last;
    }

    /**
     * Format dates consistently (e.g., 07 September 2026).
     */
    public function formatDate($date): string
    {
        if (empty($date)) {
            return now()->format('d F Y');
        }
        try {
            return \Carbon\Carbon::parse($date)->format('d F Y');
        } catch (\Throwable $e) {
            return (string)$date;
        }
    }

    /**
     * Format compensation with proper currency symbol.
     */
    public function formatCompensation($amount, $currency = '₹', $frequency = 'month'): string
    {
        if ($amount === null || $amount === '' || $amount === 0 || $amount === '0') {
            return 'Fixed Allowance / Performance Based';
        }
        if (is_numeric($amount)) {
            $curr = !empty($currency) ? $currency : '₹';
            return $curr . number_format((float)$amount) . ' per ' . ($frequency ?: 'month');
        }
        return (string)$amount;
    }

    /**
     * Generate an official Appointment Letter PDF with dynamic details & DejaVu Sans Unicode support.
     */
    public function generate(InternshipApplication $application, ?int $generatedBy = null, array $options = []): AppointmentLetter
    {
        $application->load(['user', 'internship.company.companyProfile']);

        // Reference number: use custom admin ref or generate unique
        $referenceNumber = !empty($options['reference_number']) 
            ? $options['reference_number'] 
            : ('BB-AL-' . date('Y') . '-' . str_pad((string)$application->id, 4, '0', STR_PAD_LEFT) . '-' . strtoupper(Str::random(4)));

        $issueDate = $this->formatDate($options['issue_date'] ?? now());

        // Candidate details (auto-fetched)
        $applicantName = $application->applicant_name;
        $applicantEmail = $application->applicant_email;
        $applicantPhone = $application->applicant_phone;
        $college = $application->user?->college ?? $application->college ?? null;
        $degree = $application->degree ?? null;
        $location = $options['work_location'] ?? ($application->internship?->location ?? 'Vadodara, Gujarat');
        $mode = $options['work_mode'] ?? ($application->internship?->mode ?? 'Onsite');

        // Appointment specific details (from Admin options or application fallback)
        $designation = $options['designation'] ?? ($application->internship?->title ?? $application->application_type ?? 'Backend Developer Intern');
        $department = $options['department'] ?? ($application->internship?->department ?? 'Engineering & Development');
        
        $startDate = $this->formatDate($options['start_date'] ?? ($application->internship?->start_date ?? now()));
        $endDate = !empty($options['end_date']) ? $this->formatDate($options['end_date']) : null;
        
        $duration = $options['duration'] ?? ($application->internship?->duration ?? '6 Months');
        $workingDays = $this->formatWorkingDays($options['working_days'] ?? 'Monday to Friday');
        $workingHours = $options['working_hours'] ?? '09:30 AM - 06:30 PM';
        $breakTime = $options['break_time'] ?? '01:00 PM - 02:00 PM';
        $reportingTime = $options['reporting_time'] ?? '09:30 AM';
        
        $stipendAmount = $options['stipend_amount'] ?? ($application->internship?->stipend ?? 18000);
        $stipendCurrency = $options['stipend_currency'] ?? '₹';
        $paymentFrequency = $options['payment_frequency'] ?? 'month';
        $formattedStipend = $this->formatCompensation($stipendAmount, $stipendCurrency, $paymentFrequency);

        $reportingTo = $options['reporting_to'] ?? 'Team Lead / Project Manager';
        $reportingPersonName = $options['reporting_person_name'] ?? null;
        
        $companyName = 'BLUEBOXX DA PVT. LTD.';
        $signedAt = $this->formatDate($application->signed_at ?? now());

        // Candidate signature base64 data URI
        $candidateSigData = null;
        if (!empty($application->signature_path) && Storage::disk('local')->exists($application->signature_path)) {
            $rawContent = Storage::disk('local')->get($application->signature_path);
            $mime = (str_ends_with(strtolower($application->signature_path), '.jpg') || str_ends_with(strtolower($application->signature_path), '.jpeg')) ? 'image/jpeg' : 'image/png';
            $candidateSigData = 'data:' . $mime . ';base64,' . base64_encode($rawContent);
        }

        // Admin signature base64 data URI
        $adminSigData = null;
        $adminSigPath = $options['admin_signature_path'] ?? null;
        if (!empty($adminSigPath) && Storage::disk('local')->exists($adminSigPath)) {
            $rawContent = Storage::disk('local')->get($adminSigPath);
            $mime = (str_ends_with(strtolower($adminSigPath), '.jpg') || str_ends_with(strtolower($adminSigPath), '.jpeg')) ? 'image/jpeg' : 'image/png';
            $adminSigData = 'data:' . $mime . ';base64,' . base64_encode($rawContent);
        } elseif (!empty($options['admin_signature_data'])) {
            $adminSigData = $options['admin_signature_data'];
        }

        $signatoryName = $options['signatory_name'] ?? 'Authorized Signatory';
        $signatoryDesignation = $options['signatory_designation'] ?? 'Managing Director / HR Head';

        // Official clean letterhead background
        $letterheadBg = null;
        $bgPath = storage_path('app/letterhead_bg.png');
        if (file_exists($bgPath)) {
            $letterheadBg = 'data:image/png;base64,' . base64_encode(file_get_contents($bgPath));
        } elseif (file_exists(public_path('images/letterhead_bg.png'))) {
            $letterheadBg = 'data:image/png;base64,' . base64_encode(file_get_contents(public_path('images/letterhead_bg.png')));
        }

        $data = [
            'reference_number'         => $referenceNumber,
            'issue_date'               => $issueDate,
            'applicant_name'           => $applicantName,
            'applicant_email'          => $applicantEmail,
            'applicant_phone'          => $applicantPhone,
            'college'                  => $college,
            'degree'                   => $degree,
            'designation'              => $designation,
            'department'               => $department,
            'start_date'               => $startDate,
            'end_date'                 => $endDate,
            'duration'                 => $duration,
            'working_days'             => $workingDays,
            'working_hours'            => $workingHours,
            'break_time'               => $breakTime,
            'reporting_time'           => $reportingTime,
            'stipend'                  => $formattedStipend,
            'stipend_amount'           => $stipendAmount,
            'stipend_currency'         => $stipendCurrency,
            'payment_frequency'        => $paymentFrequency,
            'reporting_to'             => $reportingTo,
            'reporting_person_name'    => $reportingPersonName,
            'location'                 => $location,
            'mode'                     => $mode,
            'company_name'             => $companyName,
            'signed_at'                => $signedAt,
            'candidate_signature_data' => $candidateSigData,
            'admin_signature_data'     => $adminSigData,
            'signatory_name'           => $signatoryName,
            'signatory_designation'    => $signatoryDesignation,
            'letterhead_bg'            => $letterheadBg,
            'application_id'           => $application->id,
        ];

        // Render with DomPDF using DejaVu Sans for native UTF-8 Unicode glyphs (Rupee ₹, dashes, bullets)
        $pdf = Pdf::loadView('pdf.appointment_letter', $data)
            ->setPaper('a4', 'portrait')
            ->setOptions([
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled'      => true,
                'defaultFont'          => 'DejaVu Sans',
                'isFontSubsettingEnabled' => true,
            ]);

        $fileName = $referenceNumber . '.pdf';
        $storageRelativePath = 'appointment_letters/' . $fileName;

        if (!Storage::disk('local')->exists('appointment_letters')) {
            Storage::disk('local')->makeDirectory('appointment_letters');
        }

        Storage::disk('local')->put($storageRelativePath, $pdf->output());

        // Update application state
        $application->update([
            'appointment_letter_path'         => $storageRelativePath,
            'appointment_letter_generated_at' => now(),
        ]);

        // Persist structured metadata into appointment_letters table
        $record = AppointmentLetter::updateOrCreate(
            ['application_id' => $application->id],
            [
                'user_id'          => $application->user_id,
                'reference_number' => $referenceNumber,
                'file_path'        => $storageRelativePath,
                'document_version' => 'v2.1',
                'generated_by'     => $generatedBy,
                'generated_at'     => now(),
                'metadata'         => [
                    'designation'           => $designation,
                    'department'            => $department,
                    'start_date'            => $startDate,
                    'end_date'              => $endDate,
                    'duration'              => $duration,
                    'working_days'          => $workingDays,
                    'working_hours'         => $workingHours,
                    'break_time'            => $breakTime,
                    'reporting_time'        => $reportingTime,
                    'stipend_amount'        => $stipendAmount,
                    'stipend_currency'      => $stipendCurrency,
                    'payment_frequency'     => $paymentFrequency,
                    'formatted_stipend'     => $formattedStipend,
                    'reporting_to'          => $reportingTo,
                    'reporting_person_name' => $reportingPersonName,
                    'work_location'         => $location,
                    'work_mode'             => $mode,
                    'issue_date'            => $issueDate,
                    'signatory_name'        => $signatoryName,
                    'signatory_designation' => $signatoryDesignation,
                    'has_candidate_sig'     => !empty($candidateSigData),
                    'has_admin_sig'         => !empty($adminSigData),
                ],
            ]
        );

        return $record;
    }
}
