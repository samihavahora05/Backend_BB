<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Lead;
use App\Models\SupportTicket;
use App\Models\NewsletterSubscriber;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\User;
use App\Models\Course;
use Illuminate\Support\Str;

class CRMAndSalesSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Newsletter
        $subscribers = ['john.doe@example.com', 'tech.enthusiast@gmail.com', 'hr.manager@company.com'];
        foreach ($subscribers as $email) {
            NewsletterSubscriber::firstOrCreate(['email' => $email], ['is_active' => true]);
        }

        // 2. CRM Leads (Contact Enquiries)
        $leads = [
            [
                'name' => 'Michael Smith',
                'email' => 'michael.smith@example.com',
                'phone' => '+919876543211',
                'source' => 'Website Contact Form',
                'status' => 'new',
                'message' => 'Interested in corporate training programs for a team of 50 developers.',
            ],
            [
                'name' => 'Sarah Connor',
                'email' => 'sarah.c@example.com',
                'phone' => '+919876543212',
                'source' => 'Landing Page Campaign',
                'status' => 'contacted',
                'message' => 'Looking for career guidance regarding data science internships.',
            ]
        ];

        foreach ($leads as $lead) {
            Lead::firstOrCreate(['email' => $lead['email']], $lead);
        }

        // 3. Support Tickets
        $student = User::role('student')->first() ?? User::factory()->create(['email' => 'ticketstudent@test.com'])->assignRole('student');
        if ($student) {
            SupportTicket::firstOrCreate(
                ['user_id' => $student->id, 'subject' => 'Issue accessing course materials'],
                [
                    'ticket_number' => 'TKT-' . strtoupper(Str::random(8)),
                    'status' => 'open',
                    'priority' => 'high',
                    'description' => 'I am getting a 403 error when trying to download the lesson PDF.',
                ]
            );
        }

        // 4. Dummy Orders & Payments for Revenue Dashboard
        $course = Course::first();
        if ($student && $course) {
            $order = Order::firstOrCreate(
                ['user_id' => $student->id, 'order_number' => 'ORD-' . strtoupper(Str::random(8))],
                [
                    'total_amount' => $course->price ?? 4999.00,
                    'status' => 'completed',
                ]
            );

            OrderItem::firstOrCreate(
                ['order_id' => $order->id],
                [
                    'course_id' => $course->id,
                    'purchasable_type' => Course::class,
                    'purchasable_id' => $course->id,
                    'item_type' => Course::class,
                    'item_id' => $course->id,
                    'price' => $course->price ?? 4999.00,
                    'quantity' => 1,
                ]
            );

            Payment::firstOrCreate(
                ['order_id' => $order->id],
                [
                    'user_id' => $student->id,
                    'transaction_id' => 'TXN-' . strtoupper(Str::random(12)),
                    'amount' => $order->total_amount,
                    'payment_gateway' => 'Razorpay',
                    'payment_method' => 'Razorpay',
                    'status' => 'completed',
                ]
            );
        }
    }
}
