<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\Lead;
use Carbon\Carbon;

class MigrateLeadsData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'leads:migrate-old-data';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrate old data from consultations, callback_requests, contact_messages, and newsletter_subscribers into leads table.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting data migration to leads table...');

        // 1. Migrate Consultations
        $consultations = DB::table('consultations')->get();
        foreach ($consultations as $item) {
            // Check if already migrated
            $exists = Lead::where('email', $item->email)->where('type', 'Book Consultation')->where('created_at', $item->created_at)->exists();
            if (!$exists) {
                $lead = new Lead([
                    'type' => 'Book Consultation',
                    'name' => $item->name,
                    'email' => $item->email,
                    'phone' => $item->phone,
                    'message' => $item->query ?? null,
                    'status' => $item->status === 'pending' ? 'new' : ($item->status === 'resolved' ? 'closed' : $item->status),
                    'source' => 'Website',
                    'internal_notes' => $item->preferred_date ? 'Preferred Date: ' . $item->preferred_date : null,
                ]);
                $lead->created_at = $item->created_at;
                $lead->updated_at = $item->updated_at;
                $lead->save();
            }
        }
        $this->info('Migrated consultations: ' . count($consultations));

        // 2. Migrate Callback Requests
        $callbacks = DB::table('callback_requests')->get();
        foreach ($callbacks as $item) {
            $exists = Lead::where('email', $item->email)->where('type', 'Callback Request')->where('created_at', $item->created_at)->exists();
            if (!$exists) {
                $lead = new Lead([
                    'type' => 'Callback Request',
                    'name' => $item->name,
                    'email' => $item->email,
                    'phone' => $item->phone,
                    'message' => $item->query ?? null,
                    'status' => $item->status === 'pending' ? 'new' : ($item->status === 'resolved' ? 'closed' : $item->status),
                    'source' => 'Website',
                ]);
                $lead->created_at = $item->created_at;
                $lead->updated_at = $item->updated_at;
                $lead->save();
            }
        }
        $this->info('Migrated callback_requests: ' . count($callbacks));

        // 3. Migrate Contact Messages
        $messages = DB::table('contact_messages')->get();
        foreach ($messages as $item) {
            $exists = Lead::where('email', $item->email)->where('type', 'Contact Inquiry')->where('created_at', $item->created_at)->exists();
            if (!$exists) {
                $lead = new Lead([
                    'type' => 'Contact Inquiry',
                    'name' => $item->name,
                    'email' => $item->email,
                    'subject' => $item->subject,
                    'message' => $item->message,
                    'status' => $item->status === 'unread' ? 'new' : 'contacted',
                    'source' => 'Website',
                ]);
                $lead->created_at = $item->created_at;
                $lead->updated_at = $item->updated_at;
                $lead->save();
            }
        }
        $this->info('Migrated contact_messages: ' . count($messages));

        // 4. Migrate Newsletter Subscribers (Only active ones, or all)
        $subscribers = DB::table('newsletter_subscribers')->get();
        foreach ($subscribers as $item) {
            $exists = Lead::where('email', $item->email)->where('type', 'Newsletter Subscriber')->exists();
            if (!$exists) {
                $lead = new Lead([
                    'type' => 'Newsletter Subscriber',
                    'name' => 'Subscriber',
                    'email' => $item->email,
                    'status' => $item->is_active ? 'new' : 'closed',
                    'source' => 'Website Footer',
                    'ip_address' => $item->ip_address,
                ]);
                $lead->created_at = $item->created_at;
                $lead->updated_at = $item->updated_at;
                $lead->save();
            }
        }
        $this->info('Migrated newsletter_subscribers: ' . count($subscribers));

        $this->info('Data migration completed successfully!');
    }
}
