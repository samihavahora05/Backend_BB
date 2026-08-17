<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('testimonials')) {
            $testimonials = [
                [
                    'name'        => 'Ketan Parmar',
                    'role' => 'DevOps Engineer',
                    'company'     => 'SIAMP India',
                    'content'      => 'The placement support was outstanding. I transitioned from learning to a full-time DevOps role smoothly.',
                    'rating'      => 5,
                ],
                [
                    'name'        => 'Krupa Patel',
                    'role' => 'Software Engineer',
                    'company'     => 'Flammer Technologies',
                    'content'      => 'Blueboxx\'s training and network directly connected me with a top tech firm for my current position.',
                    'rating'      => 5,
                ],
                [
                    'name'        => 'Manav Vithani',
                    'role' => 'Senior Product Designer',
                    'company'     => '3D Studio',
                    'content'      => 'The rigorous practical work and mock interviews prepared me perfectly for my senior design role.',
                    'rating'      => 5,
                ],
                [
                    'name'        => 'Nency Shah',
                    'role' => 'Product Manager',
                    'company'     => 'Anacle Systems',
                    'content'      => 'The product management roadmap they provided was crucial for me clearing all my PM interview rounds.',
                    'rating'      => 5,
                ],
                [
                    'name'        => 'Nishant Prajapati',
                    'role' => 'Senior PM',
                    'company'     => 'Bizpack',
                    'content'      => 'Great mentorship and industry-relevant curriculum helped me land a Senior PM role faster than I expected.',
                    'rating'      => 5,
                ],
                [
                    'name'        => 'Priyal Chauhan',
                    'role' => 'Full-Stack Developer',
                    'company'     => 'ATR-ASAHI',
                    'content'      => 'I went from zero cloud knowledge to a full time Software Engineer thanks to their structured placement program.',
                    'rating'      => 5,
                ],
                [
                    'name'        => 'Aarav Mehta',
                    'role' => 'UI/UX Designer',
                    'company'     => 'Damyaa',
                    'content'      => 'Excellent organization with a professional team and a strong focus on quality. Landed my dream job here.',
                    'rating'      => 5,
                ],
                [
                    'name'        => 'Riddhi Joshi',
                    'role' => 'Data Analyst',
                    'company'     => 'CSD Instruments',
                    'content'      => 'The hands-on learning approach has helped me build confidence and improve my skills, securing a great package.',
                    'rating'      => 5,
                ]
            ];

            foreach ($testimonials as $t) {
                DB::table('testimonials')->updateOrInsert(
                    ['name' => $t['name']],
                    array_merge($t, [
                        'created_at' => now(),
                        'updated_at' => now()
                    ])
                );
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
