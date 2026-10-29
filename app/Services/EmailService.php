<?php

namespace App\Services;

use App\Mail\RequestNotification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class EmailService
{
    /**
     * Send an email notification to a requester.
     *
     * @param  string  $recipientEmail  The requester's email address
     * @param  string  $recipientName   The requester's name
     * @param  string  $subject         Email subject line
     * @param  string  $body            Plain-text message body (also used in the HTML template)
     * @return bool  True if the email was sent successfully, false otherwise.
     */
    public function send(string $recipientEmail, string $recipientName, string $subject, string $body): bool
    {
        if (empty(config('mail.mailers.smtp.host'))) {
            Log::error('Email Failed: MAIL_HOST is not configured. Please add Gmail SMTP settings to your .env file.');
            return false;
        }

        try {
            Mail::to($recipientEmail, $recipientName)
                ->send(new RequestNotification($subject, $body, $recipientName));

            Log::info('Email Sent', [
                'to'      => $recipientEmail,
                'subject' => $subject,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Email Failed: ' . $e->getMessage(), [
                'to'      => $recipientEmail,
                'subject' => $subject,
            ]);

            return false;
        }
    }
}
