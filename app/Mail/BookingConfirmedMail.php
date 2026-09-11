<?php

namespace App\Mail;

use App\Models\MentorBooking;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class BookingConfirmedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public MentorBooking $booking,
        public ?string $recipientRole = 'student'
    ) {}

    public function build()
    {
        $sessionTitle = $this->booking->session?->title ?? '1:1 Mentorship Session';
        $bookingDate = $this->booking->booking_date ? $this->booking->booking_date->format('d M Y') : now()->format('d M Y');
        
        $subject = $this->recipientRole === 'expert' 
            ? "New Mentorship Session Booked: {$sessionTitle} ({$bookingDate}) | Blueboxx DA"
            : "Booking Confirmed: {$sessionTitle} on {$bookingDate} | Blueboxx DA";

        return $this->subject($subject)
                    ->view('emails.booking_confirmed', [
                        'booking' => $this->booking,
                        'recipientRole' => $this->recipientRole,
                        'student' => $this->booking->student,
                        'expert' => $this->booking->expert,
                    ]);
    }
}
