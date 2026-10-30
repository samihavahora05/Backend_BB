<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Assessment;
use App\Models\AssessmentQuestion;
use Illuminate\Support\Facades\File;

class CommonITSkillsMCQSeeder extends Seeder
{
    /**
     * Run the database seeds for 50 Blueboxx Common IT & Digital Skills MCQs.
     */
    public function run(): void
    {
        $jsonPath = base_path('../Frontend_BB/src/data/itSkillsAssessment50.json');
        if (!File::exists($jsonPath)) {
            $jsonPath = 'D:/bb-live-website/Frontend_BB/src/data/itSkillsAssessment50.json';
        }

        if (!File::exists($jsonPath)) {
            $this->command->error("JSON file not found at: {$jsonPath}");
            return;
        }

        $data = json_decode(File::get($jsonPath), true);
        if (!$data || !isset($data['questions'])) {
            $this->command->error("Invalid JSON format in {$jsonPath}");
            return;
        }

        // Category breakdown
        $categories = [];
        foreach ($data['questions'] as $q) {
            $cat = $q['category'];
            $categories[$cat] = ($categories[$cat] ?? 0) + 1;
        }
        $categoryBreakdown = [];
        foreach ($categories as $cat => $count) {
            $categoryBreakdown[] = [
                'category' => $cat,
                'count' => $count,
                'marks' => $count
            ];
        }

        $assessment = Assessment::updateOrCreate(
            ['slug' => 'blueboxx-common-it-digital-skills-50'],
            [
                'title' => 'Common IT & Digital Skills Assessment',
                'description' => 'Comprehensive 50 Multiple-Choice Questions covering Computer Fundamentals, Internet & Networking, Programming, Cybersecurity, UI/UX, Digital Marketing, and AI & Emerging Tech.',
                'total_questions' => 50,
                'total_marks' => 50,
                'passing_percentage' => 50,
                'duration_minutes' => 40,
                'status' => 'active',
                'category_breakdown' => $categoryBreakdown,
            ]
        );

        // Delete existing questions for this assessment to avoid duplicates
        AssessmentQuestion::where('assessment_id', $assessment->id)->delete();

        foreach ($data['questions'] as $index => $q) {
            AssessmentQuestion::create([
                'assessment_id' => $assessment->id,
                'order' => $q['id'] ?? ($index + 1),
                'category' => $q['category'],
                'question' => $q['question'],
                'option_a' => $q['options']['A'] ?? '',
                'option_b' => $q['options']['B'] ?? '',
                'option_c' => $q['options']['C'] ?? '',
                'option_d' => $q['options']['D'] ?? '',
                'correct_answer' => $q['correct_answer'],
                'marks' => 1,
                'explanation' => "Difficulty: " . ($q['difficulty'] ?? 'Medium'),
            ]);
        }

        $this->command->info("✅ Successfully seeded 50 MCQs for Assessment: {$assessment->title} (ID: {$assessment->id})");
    }
}
