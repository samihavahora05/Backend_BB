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
        if ($days === $standard || (count(array_diff($standard, $days)) === 0 && count(array_diff($days, $standard)) === 0)) {
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
     * Format dates consistently (e.g., 15-07-2026 or 15 July 2026).
     */
    public function formatDate($date, $format = 'd-m-Y'): string
    {
        if (empty($date)) {
            return now()->format($format);
        }
        try {
            return \Carbon\Carbon::parse($date)->format($format);
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
            return $curr . number_format((float)$amount);
        }
        return (string)$amount;
    }

    /**
     * Get domain-specific responsibilities based on role title or department.
     */
    public function getDefaultResponsibilities(string $designation, string $department): array
    {
        $text = strtolower($designation . ' ' . $department);

        if (str_contains($text, 'marketing') || str_contains($text, 'seo') || str_contains($text, 'social media') || str_contains($text, 'digital')) {
            return [
                'Assist in planning and executing digital marketing campaigns.',
                'Create and publish content on Facebook, Instagram, LinkedIn, and YouTube.',
                'Manage social media calendars and maintain brand consistency.',
                'Support Meta Ads and Google Ads campaign setup, monitoring, and optimization.',
                'Perform keyword research and assist with on-page and off-page SEO.',
                'Create captions, creatives, reels, and promotional content using Canva and AI tools.',
                'Conduct competitor analysis and market research.',
                'Generate and manage leads through digital marketing channels.',
                'Support email marketing campaigns and automation.',
                'Prepare daily, weekly, and monthly marketing reports.',
                'Monitor campaign performance using Google Analytics and Meta Business Suite.',
                'Coordinate with design, sales, and development teams.',
                'Participate in meetings, complete assigned tasks, and maintain professional communication.',
            ];
        }

        if (str_contains($text, 'developer') || str_contains($text, 'software') || str_contains($text, 'backend') || str_contains($text, 'frontend') || str_contains($text, 'full stack') || str_contains($text, 'web')) {
            return [
                'Design, build, and maintain efficient, reusable, and reliable software components.',
                'Develop and integrate RESTful APIs and modern database schemas as per technical specs.',
                'Troubleshoot, debug, and optimize application speed, scalability, and security.',
                'Collaborate closely with UI/UX designers, product managers, and senior engineers.',
                'Write modular, clean code and maintain standard Git version control best practices.',
                'Perform unit testing and participate in peer code review cycles.',
                'Log daily progress, tasks, and milestone updates on the internal BlueBoxx tracking system.',
                'Participate in agile standups, complete assigned tasks, and maintain professional communication.',
            ];
        }

        if (str_contains($text, 'design') || str_contains($text, 'ui') || str_contains($text, 'ux') || str_contains($text, 'graphic') || str_contains($text, 'animat')) {
            return [
                'Create engaging graphic assets, wireframes, prototypes, and user interface designs.',
                'Collaborate with product and development teams to translate ideas into high-fidelity visuals.',
                'Maintain brand consistency across all marketing, web, and social media creative collateral.',
                'Incorporate feedback from senior mentors and iterate rapidly on visual designs.',
                'Utilize Figma, Adobe Creative Suite, and modern design tools efficiently.',
                'Organize and document design system components, typography scales, and asset libraries.',
                'Log daily progress, tasks, and milestone updates on the internal BlueBoxx tracking system.',
                'Participate in reviews, complete assigned tasks, and maintain professional communication.',
            ];
        }

        return [
            'Assist in planning and executing domain-specific project milestones and operational deliverables.',
            'Collaborate with mentors, project leads, and team members to meet quality benchmarks.',
            'Conduct research, data compilation, documentation, and reporting for assigned initiatives.',
            'Maintain quality assurance, confidentiality, and data security across all workflows.',
            'Actively participate in daily reviews, workshops, and milestone evaluations.',
            'Complete assigned tasks on time, update logs, and maintain professional communication.',
        ];
    }

    /**
     * Ensure required tables and columns exist automatically on MySQL without failing.
     */
    public function ensureSchema(): void
    {
        try {
            if (\Illuminate\Support\Facades\Schema::hasTable('internship_applications')) {
                if (\Illuminate\Support\Facades\DB::connection()->getDriverName() === 'mysql') {
                    \Illuminate\Support\Facades\DB::statement("ALTER TABLE `internship_applications` MODIFY COLUMN `status` VARCHAR(50) NOT NULL DEFAULT 'applied'");
                }
            }
        } catch (\Throwable $e) {}

        try {
            if (!\Illuminate\Support\Facades\Schema::hasTable('appointment_letters')) {
                \Illuminate\Support\Facades\Schema::create('appointment_letters', function ($table) {
                    $table->id();
                    $table->unsignedBigInteger('application_id');
                    $table->unsignedBigInteger('user_id')->nullable();
                    $table->string('reference_number', 100)->unique();
                    $table->string('file_path');
                    $table->string('document_version', 50)->default('v1.0');
                    $table->unsignedBigInteger('generated_by')->nullable();
                    $table->timestamp('generated_at')->nullable();
                    $table->timestamp('downloaded_at')->nullable();
                    $table->json('metadata')->nullable();
                    $table->timestamps();
                });
            }
        } catch (\Throwable $e) {}
    }

    /**
     * Generate an official Appointment Letter PDF matching reference layout with candidate-only signature.
     */
    public function generate(InternshipApplication $application, ?int $generatedBy = null, array $options = []): AppointmentLetter
    {
        $this->ensureSchema();

        $application->load(['user', 'internship.company.companyProfile']);

        // Reference number
        $referenceNumber = !empty($options['reference_number']) 
            ? $options['reference_number'] 
            : ('BB-AL-' . date('Y') . '-' . str_pad((string)$application->id, 4, '0', STR_PAD_LEFT) . '-' . strtoupper(Str::random(4)));

        $issueDate = $this->formatDate($options['issue_date'] ?? now());

        // Candidate details
        $applicantName = $application->applicant_name ?: ($application->first_name . ' ' . $application->last_name);
        $applicantEmail = $application->applicant_email ?: $application->email;
        $applicantPhone = $application->applicant_phone ?: $application->phone;
        $college = $application->user?->college ?? $application->college ?? null;
        $degree = $application->degree ?? null;
        $location = $options['work_location'] ?? ($application->internship?->location ?? 'Vadodara, Gujarat');
        $mode = $options['work_mode'] ?? ($application->internship?->mode ?? 'Fully Remote');

        // Role & department details
        $designation = $options['designation'] ?? ($application->internship?->title ?? $application->application_type ?? 'Associate – L1');
        $department = $options['department'] ?? ($application->internship?->department ?? 'Digital Marketing');
        
        $startDate = $this->formatDate($options['start_date'] ?? ($application->internship?->start_date ?? now()));
        $endDate = !empty($options['end_date']) 
            ? $this->formatDate($options['end_date']) 
            : $this->formatDate(now()->addMonths(2));
        
        $duration = $options['duration'] ?? ($application->internship?->duration ?? 'Fifty Days');
        $workingDays = $this->formatWorkingDays($options['working_days'] ?? 'Monday to Friday');
        $workingHours = $options['working_hours'] ?? '09:30 AM - 06:30 PM';
        
        $stipendAmount = $options['stipend_amount'] ?? ($application->internship?->stipend ?? 6000);
        $stipendCurrency = $options['stipend_currency'] ?? '₹';
        $formattedStipend = $this->formatCompensation($stipendAmount, $stipendCurrency);

        $companyName = 'Blueboxx DA Pvt. Ltd.';
        $signedAt = $this->formatDate($application->signed_at ?? $application->created_at ?? now());

        // Responsibilities
        $responsibilities = !empty($options['responsibilities']) && is_array($options['responsibilities'])
            ? $options['responsibilities']
            : $this->getDefaultResponsibilities($designation, $department);

        // Candidate signature base64 data URI
        $candidateSigData = null;
        if (!empty($application->signature_path)) {
            $sigPath = $application->signature_path;
            $rawContent = null;
            if (Storage::disk('local')->exists($sigPath)) {
                $rawContent = Storage::disk('local')->get($sigPath);
            } elseif (Storage::disk('public')->exists($sigPath)) {
                $rawContent = Storage::disk('public')->get($sigPath);
            } elseif (file_exists(storage_path('app/' . $sigPath))) {
                $rawContent = file_get_contents(storage_path('app/' . $sigPath));
            } elseif (file_exists(public_path($sigPath))) {
                $rawContent = file_get_contents(public_path($sigPath));
            }

            if ($rawContent) {
                $mime = (str_ends_with(strtolower($sigPath), '.jpg') || str_ends_with(strtolower($sigPath), '.jpeg')) ? 'image/jpeg' : 'image/png';
                $candidateSigData = 'data:' . $mime . ';base64,' . base64_encode($rawContent);
            }
        }

        // Company Logo & Letterhead Background Base64 Data URI
        $logoBase64 = null;
        $possibleLogoPaths = [
            public_path('images/Boxxlogo.png'),
            public_path('Boxxlogo.png'),
            public_path('images/logoblue.png'),
            public_path('logoblue.png'),
        ];
        foreach ($possibleLogoPaths as $logoPath) {
            if (file_exists($logoPath)) {
                $logoBase64 = 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath));
                break;
            }
        }

        $letterheadBgBase64 = null;
        $possibleBgPaths = [
            public_path('images/letterhead_bg.png'),
            public_path('letterhead_bg.png'),
            resource_path('images/letterhead_bg.png'),
        ];
        foreach ($possibleBgPaths as $bgPath) {
            if (file_exists($bgPath)) {
                $letterheadBgBase64 = 'data:image/png;base64,' . base64_encode(file_get_contents($bgPath));
                break;
            }
        }

        $data = [
            'letterhead_bg_base64'     => $letterheadBgBase64,
            'reference_number'         => $referenceNumber,
            'issue_date'               => $issueDate,
            'applicant_name'           => $applicantName,
            'applicant_email'          => $applicantEmail,
            'applicant_phone'          => $applicantPhone,
            'college'                  => $college,
            'degree'                   => $degree,
            'designation'              => $designation,
            'department'               => $department,
            'employment_type'          => 'Internship / Trainee Appointment',
            'start_date'               => $startDate,
            'end_date'                 => $endDate,
            'duration'                 => $duration,
            'duration_text'            => $duration,
            'working_days'             => $workingDays,
            'working_hours'            => $workingHours,
            'stipend'                  => $formattedStipend,
            'stipend_amount'           => $stipendAmount,
            'stipend_currency'         => $stipendCurrency,
            'location'                 => $location,
            'mode'                     => $mode,
            'company_name'             => $companyName,
            'signed_at'                => $signedAt,
            'responsibilities'         => $responsibilities,
            'candidate_signature_data' => $candidateSigData,
            'logo_base64'              => $logoBase64,
            'application_id'           => $application->id,
        ];

        // Render DomPDF with DejaVu Sans for native UTF-8 Unicode glyphs (Rupee ₹, dashes, bullets)
        $pdf = Pdf::loadView('pdf.appointment_letter', $data)
            ->setPaper('a4', 'portrait')
            ->setOptions([
                'isHtml5ParserEnabled'    => true,
                'isRemoteEnabled'         => true,
                'defaultFont'             => 'DejaVu Sans',
                'isFontSubsettingEnabled' => true,
            ]);

        $fileName = 'appointment_' . $application->id . '_' . Str::slug($applicantName) . '.pdf';
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
                'document_version' => 'v3.0',
                'generated_by'     => $generatedBy,
                'generated_at'     => now(),
                'metadata'         => [
                    'designation'       => $designation,
                    'department'        => $department,
                    'start_date'        => $startDate,
                    'end_date'          => $endDate,
                    'duration'          => $duration,
                    'stipend_amount'    => $stipendAmount,
                    'formatted_stipend' => $formattedStipend,
                    'work_location'     => $location,
                    'work_mode'         => $mode,
                    'issue_date'        => $issueDate,
                    'has_candidate_sig' => !empty($candidateSigData),
                ],
            ]
        );

        return $record;
    }

    /**
     * Generate or fetch the official 9-section Terms & Conditions PDF.
     */
    public function getTermsAndConditionsPdf(): string
    {
        $directory = storage_path('app/documents');
        if (!file_exists($directory)) {
            mkdir($directory, 0755, true);
        }

        $filePath = $directory . '/BlueBoxx_DA_Official_Consent_Declaration_Terms_and_Conditions.pdf';

        // Re-generate if not exists or if source view was updated
        $viewPath = resource_path('views/pdf/terms_and_conditions.blade.php');
        $needsRegen = true; // Always regenerate with updated layout

        if ($needsRegen) {
            $logoBase64 = null;
            $possibleLogoPaths = [
                public_path('images/Boxxlogo.png'),
                public_path('Boxxlogo.png'),
                public_path('images/logoblue.png'),
                public_path('logoblue.png'),
            ];
            foreach ($possibleLogoPaths as $logoPath) {
                if (file_exists($logoPath)) {
                    $logoBase64 = 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath));
                    break;
                }
            }

            $data = [
                'company_name'   => 'BLUEBOXX DA PVT. LTD.',
                'version'        => 'v3.2',
                'effective_date' => date('d F Y'),
                'logo_base64'    => $logoBase64,
            ];

            $pdf = Pdf::loadView('pdf.terms_and_conditions', $data)
                ->setPaper('a4', 'portrait')
                ->setOptions([
                    'isHtml5ParserEnabled'    => true,
                    'isRemoteEnabled'         => true,
                    'defaultFont'             => 'DejaVu Sans',
                    'isFontSubsettingEnabled' => true,
                ]);

            file_put_contents($filePath, $pdf->output());
        }

        return $filePath;
    }
}
