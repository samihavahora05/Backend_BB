<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ScholarshipProgram;
use App\Models\Contest;

class EventsAndActivitiesSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Scholarships
        $scholarships = [
            [
                'title' => 'Blueboxx Merit Scholarship 2024',
                'description' => 'A full-ride scholarship for outstanding students pursuing a career in technology and management.',
                'amount' => 50000.00,
                'deadline' => now()->addMonths(2)->format('Y-m-d'),
                'status' => 'open',
            ],
            [
                'title' => 'Women in Tech Grant',
                'description' => 'Supporting talented women engineers and developers to achieve their dreams in the tech industry.',
                'amount' => 25000.00,
                'deadline' => now()->addMonths(1)->format('Y-m-d'),
                'status' => 'open',
            ]
        ];

        foreach ($scholarships as $scholarship) {
            ScholarshipProgram::firstOrCreate(['title' => $scholarship['title']], $scholarship);
        }

        // 2. Contests
        $contests = [
            [
                'title' => 'National Coding Hackathon',
                'description' => 'Compete with the best minds in India to solve real-world problems using AI and scalable system design.',
                'category_id' => 1,
                'start_date' => now()->addDays(5),
                'end_date' => now()->addDays(7),
                'status' => 'upcoming',
                'college_id' => null, // Open for all
            ],
            [
                'title' => 'UI/UX Design Challenge',
                'description' => 'Design the next generation learning management system interface.',
                'category_id' => 2,
                'start_date' => now()->subDays(2),
                'end_date' => now()->addDays(10),
                'status' => 'ongoing',
                'college_id' => null,
            ]
        ];

        foreach ($contests as $contest) {
            Contest::firstOrCreate(['title' => $contest['title']], $contest);
        }
    }
}
