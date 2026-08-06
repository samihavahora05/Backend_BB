<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Blog;
use App\Models\BlogCategory;
use App\Models\Testimonial;
use App\Models\Faq;
use App\Models\SuccessStory;
use App\Models\PlacementRecord;
use App\Models\User;
use Illuminate\Support\Str;

class CmsContentSeeder extends Seeder
{
    public function run(): void
    {
        // 1. FAQs
        $faqs = [
            ['question' => 'How does Blueboxx DA work?', 'answer' => 'We offer intensive industry-oriented courses and guaranteed internships to kickstart your career.', 'is_active' => true],
            ['question' => 'Do you provide placement assistance?', 'answer' => 'Yes, all our major programs come with dedicated placement support and mock interviews.', 'is_active' => true],
            ['question' => 'Can I learn at my own pace?', 'answer' => 'Yes, our courses offer flexible learning options with recorded sessions and weekend live classes.', 'is_active' => true],
        ];

        foreach ($faqs as $faq) {
            Faq::firstOrCreate(['question' => $faq['question']], $faq);
        }

        // 2. Testimonials
        $testimonials = [
            [
                'name' => 'Rahul Sharma',
                'role' => 'Software Engineer',
                'company' => 'Tech Mahindra',
                'content' => 'The internship program was incredibly well-structured. I learned full-stack development and got placed within 2 weeks of completion.',
                'rating' => 5,
                'status' => 'published',
                'display_order' => 1,
            ],
            [
                'name' => 'Anjali Desai',
                'role' => 'Data Analyst',
                'company' => 'TCS',
                'content' => 'Blueboxx DA mentors are top-notch! They helped me prepare for interviews and reviewed my resume, which was a game changer.',
                'rating' => 5,
                'status' => 'published',
                'display_order' => 2,
            ],
            [
                'name' => 'Vikram Singh',
                'role' => 'Frontend Developer',
                'company' => 'Cognizant',
                'content' => 'Highly recommend the Web Development Masterclass. The real-world projects helped me build a strong portfolio.',
                'rating' => 4,
                'status' => 'published',
                'display_order' => 3,
            ]
        ];

        foreach ($testimonials as $testimonial) {
            Testimonial::firstOrCreate(['name' => $testimonial['name']], $testimonial);
        }

        // 3. Blog Categories
        $categories = ['Career Advice', 'Tech Trends', 'Web Development', 'Data Science'];
        $catIds = [];
        foreach ($categories as $cat) {
            $model = BlogCategory::firstOrCreate(
                ['slug' => Str::slug($cat)],
                ['name' => $cat, 'status' => 'active']
            );
            $catIds[] = $model->id;
        }

        // 4. Blogs
        $admin = User::role('super_admin')->first() ?? User::first();
        if ($admin) {
            $blogs = [
                [
                    'title' => 'Top 10 High-Paying IT Skills in 2024',
                    'slug' => 'top-10-high-paying-it-skills-in-2024',
                    'content' => '<p>The tech industry is evolving rapidly. To stay ahead, you need to focus on skills like AI, Cloud Computing, and Full-Stack Development...</p>',
                    'excerpt' => 'Discover the most in-demand tech skills that will dominate the job market in 2024.',
                    'status' => 'published',
                    'is_featured' => true,
                    'author_id' => $admin->id,
                    'reading_time' => 5,
                ],
                [
                    'title' => 'How to Crack Frontend Developer Interviews',
                    'slug' => 'how-to-crack-frontend-developer-interviews',
                    'content' => '<p>Frontend interviews can be tricky. Make sure you brush up on your JavaScript fundamentals, React lifecycle methods, and CSS Grid...</p>',
                    'excerpt' => 'A comprehensive guide to acing your next frontend engineering interview.',
                    'status' => 'published',
                    'is_featured' => false,
                    'author_id' => $admin->id,
                    'reading_time' => 8,
                ]
            ];

            foreach ($blogs as $blog) {
                $blogModel = Blog::firstOrCreate(['slug' => $blog['slug']], $blog);
                $blogModel->categories()->syncWithoutDetaching([$catIds[array_rand($catIds)]]);
            }
        }
    }
}
