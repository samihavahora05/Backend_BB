<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\StudentJobOffer;
use Illuminate\Support\Facades\DB;

class StudentJobOfferSeeder extends Seeder
{
    /**
     * Seed all 44 authentic student showcase items idempotently.
     */
    public function run(): void
    {
        $students = [
            ['student_name' => 'Yuvraj Parmar', 'role' => 'Graphic design', 'company_name' => 'Blueboxx Media', 'avatar_url' => '/students/yuvraj_parmar.png', 'degree' => 'Alumni', 'offered_on' => '15 Jan 2025', 'package' => 'Best in Industry'],
            ['student_name' => 'Vikas', 'role' => 'Graphic design', 'company_name' => 'Creative Labs', 'avatar_url' => '/students/vikas.png', 'degree' => 'Alumni', 'offered_on' => '18 Jan 2025', 'package' => 'Best in Industry'],
            ['student_name' => 'Vaidehi', 'role' => 'Graphic design, digital marketing', 'company_name' => 'Digital Spark', 'avatar_url' => '/students/vaidehi.png', 'degree' => 'Alumni', 'offered_on' => '22 Jan 2025', 'package' => 'Best in Industry'],
            ['student_name' => 'Tushar', 'role' => 'Graphic design', 'company_name' => 'Studio 9', 'avatar_url' => '/students/tushar.png', 'degree' => 'Alumni', 'offered_on' => '25 Jan 2025', 'package' => 'Best in Industry'],
            ['student_name' => 'Tisha Padhiyar', 'role' => 'web development', 'company_name' => 'TechNova Solutions', 'avatar_url' => '/students/tisha_padhiyar.png', 'degree' => 'Alumni', 'offered_on' => '28 Jan 2025', 'package' => 'Best in Industry'],
            ['student_name' => 'Tax Patel', 'role' => 'Digital Marketing', 'company_name' => 'Growth Media', 'avatar_url' => '/students/tax_patel.png', 'degree' => 'Alumni', 'offered_on' => '02 Feb 2025', 'package' => 'Best in Industry'],
            ['student_name' => 'Swapnesh', 'role' => 'web development', 'company_name' => 'Cognizant', 'avatar_url' => '/students/swapnesh.png', 'degree' => 'Alumni', 'offered_on' => '05 Feb 2025', 'package' => 'Best in Industry'],
            ['student_name' => 'Suhani Dhuri', 'role' => 'web development', 'company_name' => 'Infosys', 'avatar_url' => '/students/suhani_dhuri.png', 'degree' => 'Alumni', 'offered_on' => '08 Feb 2025', 'package' => 'Best in Industry'],
            ['student_name' => 'Shruti Jadhav', 'role' => 'Graphic design', 'company_name' => 'DesignHub', 'avatar_url' => '/students/shruti_jadhav.png', 'degree' => 'Alumni', 'offered_on' => '12 Feb 2025', 'package' => 'Best in Industry'],
            ['student_name' => 'Shivam', 'role' => 'Graphic design', 'company_name' => 'Pixel Studio', 'avatar_url' => '/students/shivam.png', 'degree' => 'Alumni', 'offered_on' => '15 Feb 2025', 'package' => 'Best in Industry'],
            ['student_name' => 'Samuel Gabi', 'role' => 'Graphic design, digital marketing', 'company_name' => 'Global Matrix', 'avatar_url' => '/students/samuel_gabi.png', 'degree' => 'Alumni', 'offered_on' => '18 Feb 2025', 'package' => 'Best in Industry'],
            ['student_name' => 'Rehan Bavla', 'role' => 'Graphic design', 'company_name' => 'Design Studio', 'avatar_url' => '/students/rehan_bavla.png', 'degree' => 'Alumni', 'offered_on' => '21 Feb 2025', 'package' => 'Best in Industry'],
            ['student_name' => 'Priyal Chauhan', 'role' => 'web development', 'company_name' => 'WebTech Corp', 'avatar_url' => '/students/priyal_chauhan.png', 'degree' => 'Alumni', 'offered_on' => '24 Feb 2025', 'package' => 'Best in Industry'],
            ['student_name' => 'Prem', 'role' => 'Graphic design, digital marketing', 'company_name' => 'Media Matrix', 'avatar_url' => '/students/prem.png', 'degree' => 'Alumni', 'offered_on' => '27 Feb 2025', 'package' => 'Best in Industry'],
            ['student_name' => 'Pratik Sirsath', 'role' => 'Graphic design, digital marketing', 'company_name' => 'Digital Spark', 'avatar_url' => '/students/pratik_sirsath.png', 'degree' => 'Alumni', 'offered_on' => '02 Mar 2025', 'package' => 'Best in Industry'],
            ['student_name' => 'Parul', 'role' => 'Graphic design', 'company_name' => 'Design Craft', 'avatar_url' => '/students/parul.png', 'degree' => 'Alumni', 'offered_on' => '05 Mar 2025', 'package' => 'Best in Industry'],
            ['student_name' => 'Nishant Prajapati', 'role' => 'web development', 'company_name' => 'Web Sphere', 'avatar_url' => '/students/nishant_prajapati.png', 'degree' => 'Alumni', 'offered_on' => '08 Mar 2025', 'package' => 'Best in Industry'],
            ['student_name' => 'Nancy Shah', 'role' => 'web development', 'company_name' => 'InfoTech Labs', 'avatar_url' => '/students/nancy_shah.png', 'degree' => 'Alumni', 'offered_on' => '11 Mar 2025', 'package' => 'Best in Industry'],
            ['student_name' => 'Namrata Spakal', 'role' => 'Graphic design', 'company_name' => 'Creative Visuals', 'avatar_url' => '/students/namrata_spakal.png', 'degree' => 'Alumni', 'offered_on' => '14 Mar 2025', 'package' => 'Best in Industry'],
            ['student_name' => 'Mitansh Solanki', 'role' => 'Graphic design, digital marketing', 'company_name' => 'Digital Spark', 'avatar_url' => '/students/mitansh_solanki.png', 'degree' => 'Alumni', 'offered_on' => '17 Mar 2025', 'package' => 'Best in Industry'],
            ['student_name' => 'Mayuri Thakre', 'role' => 'Graphic design', 'company_name' => 'Studio Pixel', 'avatar_url' => '/students/mayuri_thakre.png', 'degree' => 'Alumni', 'offered_on' => '20 Mar 2025', 'package' => 'Best in Industry'],
            ['student_name' => 'Manthan Parmar', 'role' => 'Graphic design', 'company_name' => 'Creative Hub', 'avatar_url' => '/students/manthan_parmar.png', 'degree' => 'Alumni', 'offered_on' => '23 Mar 2025', 'package' => 'Best in Industry'],
            ['student_name' => 'Manoj Patil', 'role' => 'Graphic design', 'company_name' => 'Print Works', 'avatar_url' => '/students/manoj_patil.png', 'degree' => 'Alumni', 'offered_on' => '26 Mar 2025', 'package' => 'Best in Industry'],
            ['student_name' => 'Manav Kharva', 'role' => 'Digital Marketing', 'company_name' => 'Growth Pulse', 'avatar_url' => '/students/manav_kharva.png', 'degree' => 'Alumni', 'offered_on' => '29 Mar 2025', 'package' => 'Best in Industry'],
            ['student_name' => 'Manasvi Yadav', 'role' => 'web development', 'company_name' => 'Web Sphere', 'avatar_url' => '/students/manasvi_yadav.png', 'degree' => 'Alumni', 'offered_on' => '01 Apr 2025', 'package' => 'Best in Industry'],
            ['student_name' => 'Mahir Dipoti', 'role' => 'web development', 'company_name' => 'Tech Code Labs', 'avatar_url' => '/students/mahir_dipoti.png', 'degree' => 'Alumni', 'offered_on' => '04 Apr 2025', 'package' => 'Best in Industry'],
            ['student_name' => 'Lata Bhambani', 'role' => 'Graphic design', 'company_name' => 'Creative Pulse', 'avatar_url' => '/students/lata_bhambani.png', 'degree' => 'Alumni', 'offered_on' => '07 Apr 2025', 'package' => 'Best in Industry'],
            ['student_name' => 'Krish Bhuvela', 'role' => 'web development', 'company_name' => 'Web Sphere', 'avatar_url' => '/students/krish_bhuvela.png', 'degree' => 'Alumni', 'offered_on' => '10 Apr 2025', 'package' => 'Best in Industry'],
            ['student_name' => 'Ketan Parmar', 'role' => 'web development', 'company_name' => 'Tech Code Labs', 'avatar_url' => '/students/ketan_parmar.png', 'degree' => 'Alumni', 'offered_on' => '13 Apr 2025', 'package' => 'Best in Industry'],
            ['student_name' => 'Kamlesh Singh', 'role' => 'Digital Marketing', 'company_name' => 'Digital Spark', 'avatar_url' => '/students/kamlesh_singh.png', 'degree' => 'Alumni', 'offered_on' => '16 Apr 2025', 'package' => 'Best in Industry'],
            ['student_name' => 'Kamal', 'role' => 'Graphic design, digital marketing', 'company_name' => 'Creative Labs', 'avatar_url' => '/students/kamal.png', 'degree' => 'Alumni', 'offered_on' => '19 Apr 2025', 'package' => 'Best in Industry'],
            ['student_name' => 'Kailash Rathva', 'role' => 'Graphic design', 'company_name' => 'Design Studio', 'avatar_url' => '/students/kailash_rathva.png', 'degree' => 'Alumni', 'offered_on' => '22 Apr 2025', 'package' => 'Best in Industry'],
            ['student_name' => 'Jaanvi Parmar', 'role' => 'web development', 'company_name' => 'Code Works', 'avatar_url' => '/students/jaanvi_parmar.png', 'degree' => 'Alumni', 'offered_on' => '25 Apr 2025', 'package' => 'Best in Industry'],
            ['student_name' => 'Himanshu Parmar', 'role' => 'Graphic design', 'company_name' => 'Pixel Labs', 'avatar_url' => '/students/himanshu_parmar.png', 'degree' => 'Alumni', 'offered_on' => '28 Apr 2025', 'package' => 'Best in Industry'],
            ['student_name' => 'Hemlata Pahan', 'role' => 'Graphic design', 'company_name' => 'Creative Media', 'avatar_url' => '/students/hemlata_pahan.png', 'degree' => 'Alumni', 'offered_on' => '01 May 2025', 'package' => 'Best in Industry'],
            ['student_name' => 'Heena Prajapati', 'role' => 'Graphic design', 'company_name' => 'Studio Spark', 'avatar_url' => '/students/heena_prajapati.png', 'degree' => 'Alumni', 'offered_on' => '04 May 2025', 'package' => 'Best in Industry'],
            ['student_name' => 'Harsh Padhiyar', 'role' => 'Graphic design', 'company_name' => 'Creative Labs', 'avatar_url' => '/students/harsh_padhiyar.png', 'degree' => 'Alumni', 'offered_on' => '07 May 2025', 'package' => 'Best in Industry'],
            ['student_name' => 'Hardik Patel', 'role' => 'Graphic design', 'company_name' => 'Print Visuals', 'avatar_url' => '/students/hardik_patel.png', 'degree' => 'Alumni', 'offered_on' => '10 May 2025', 'package' => 'Best in Industry'],
            ['student_name' => 'Drashti', 'role' => 'Graphic design', 'company_name' => 'Design Studio', 'avatar_url' => '/students/drashti.png', 'degree' => 'Alumni', 'offered_on' => '13 May 2025', 'package' => 'Best in Industry'],
            ['student_name' => 'Chavda Mayur', 'role' => 'Graphic design', 'company_name' => 'Creative Hub', 'avatar_url' => '/students/chavda_mayur.png', 'degree' => 'Alumni', 'offered_on' => '16 May 2025', 'package' => 'Best in Industry'],
            ['student_name' => 'Arch', 'role' => 'Graphic design', 'company_name' => 'Pixel Labs', 'avatar_url' => '/students/arch.png', 'degree' => 'Alumni', 'offered_on' => '19 May 2025', 'package' => 'Best in Industry'],
            ['student_name' => 'Dipak Rawat', 'role' => 'Graphic design', 'company_name' => 'Design Craft', 'avatar_url' => '/students/dipak_rawat.png', 'degree' => 'Alumni', 'offered_on' => '22 May 2025', 'package' => 'Best in Industry'],
            ['student_name' => 'Dhara', 'role' => 'Graphic design', 'company_name' => 'Print Works', 'avatar_url' => '/students/dhara.png', 'degree' => 'Alumni', 'offered_on' => '25 May 2025', 'package' => 'Best in Industry'],
            ['student_name' => 'Bhumika Rathod', 'role' => 'Digital Marketing', 'company_name' => 'Growth Media', 'avatar_url' => '/students/bhumika_rathod.png', 'degree' => 'Alumni', 'offered_on' => '28 May 2025', 'package' => 'Best in Industry'],
        ];

        // Ensure table is populated with authentic baseline records
        foreach ($students as $st) {
            StudentJobOffer::updateOrCreate(
                ['student_name' => $st['student_name']],
                [
                    'role'         => $st['role'],
                    'company_name' => $st['company_name'],
                    'avatar_url'   => $st['avatar_url'],
                    'degree'       => $st['degree'],
                    'offered_on'   => $st['offered_on'],
                    'package'      => $st['package'],
                    'is_active'    => true,
                ]
            );
        }

        // Clean up any old placeholder migration records (like Vikramaditya Rao if from legacy migration)
        StudentJobOffer::whereIn('student_name', ['Vikramaditya Rao', 'Aman Gupta', 'Sneha Patel', 'Rahul Verma', 'Priya Nair', 'Ananya Sharma'])
            ->whereNotIn('student_name', array_column($students, 'student_name'))
            ->delete();
    }
}
