<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InstructorDetailResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $firstName = $this->user?->first_name ?? '';
        $lastName = $this->user?->last_name ?? '';
        $fullName = trim($firstName . ' ' . $lastName);

        $rawPhoto = $this->profile_photo;
        $avatarUrl = null;
        if ($rawPhoto) {
            if (str_starts_with($rawPhoto, 'http://') || str_starts_with($rawPhoto, 'https://') || str_starts_with($rawPhoto, 'data:')) {
                $avatarUrl = $rawPhoto;
            } elseif (str_starts_with($rawPhoto, '/uploads/') || str_starts_with($rawPhoto, 'uploads/')) {
                $avatarUrl = str_starts_with($rawPhoto, '/') ? $rawPhoto : '/' . $rawPhoto;
            } elseif (str_starts_with($rawPhoto, 'storage/') || str_starts_with($rawPhoto, '/storage/')) {
                $avatarUrl = '/' . ltrim($rawPhoto, '/');
            } else {
                $avatarUrl = '/storage/' . ltrim($rawPhoto, '/');
            }
        }

        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'first_name' => $firstName ?: 'Expert',
            'last_name' => $lastName,
            'name' => !empty($fullName) ? $fullName : 'Expert User',
            'email' => $this->user?->email ?? '',
            'phone' => $this->user?->phone ?? '',
            'profile_photo' => $avatarUrl,
            'avatar' => $avatarUrl,
            'designation' => $this->designation ?? 'Expert',
            'company' => $this->company ?? 'Independent',
            'specialization' => $this->specialization ?? 'Career & Technical Mentorship',
            'hourly_rate' => (float)($this->hourly_rate ?? 1500),
            'user' => [
                'first_name' => $firstName,
                'last_name' => $lastName,
                'email' => $this->user?->email ?? '',
                'phone' => $this->user?->phone ?? '',
                'profile_photo' => $avatarUrl,
            ],
            'professional_details' => [
                'designation' => $this->designation,
                'company' => $this->company,
                'bio' => $this->bio,
                'experience_years' => $this->experience_years,
                'highest_qualification' => $this->highest_qualification,
                'specialization' => $this->specialization,
            ],
            'financials' => [
                'hourly_rate' => (float)($this->hourly_rate ?? 1500),
                'is_available' => (bool)($this->is_available ?? true),
            ],
            'social_links' => [
                'linkedin' => $this->linkedin_url,
                'github' => $this->github_url,
                'portfolio' => $this->portfolio_url,
                'website' => $this->website,
            ],
            'metrics' => [
                'total_revenue' => $this->total_revenue,
                'average_rating' => $this->average_rating,
                'total_reviews' => $this->total_reviews,
                'total_courses_sold' => $this->total_courses_sold,
                'total_students' => $this->total_students,
                'total_certificates_issued' => $this->total_certificates_issued,
                'completion_rate' => $this->completion_rate,
                'student_satisfaction' => $this->student_satisfaction,
            ],
            'status' => [
                'approval_status' => $this->approval_status,
                'is_verified' => $this->is_verified,
                'profile_completion_percentage' => $this->profile_completion_percentage,
            ],
            // Relations
            'skills' => $this->user->expertSkills,
            'languages' => $this->user->expertLanguages,
            'certificates' => $this->user->expertCertificates,
            'documents' => $this->user->expertDocuments,
        ];
    }
}
