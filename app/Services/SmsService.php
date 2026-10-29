<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class SmsService
{
    /**
     * The Semaphore API URL.
     *
     * Per the official Semaphore API documentation the working base URL is
     * https://api.semaphore.co/api/v4/messages
     */
    protected string $apiUrl;

    /**
     * The Semaphore API key.
     */
    protected string $apiKey;

    /**
     * The sender name shown on SMS messages.
     */
    protected string $senderName;

    /**
     * Create a new SmsService instance.
     *
     * Reads Semaphore credentials from config/services.php so the values
     * survive `php artisan config:cache`.
     */
    public function __construct()
    {
        $this->apiUrl     = config('services.semaphore.api_url', 'https://api.semaphore.co/api/v4/messages');
        $this->apiKey     = config('services.semaphore.api_key', '');
        $this->senderName = config('services.semaphore.sender_name', 'BontocSB');
    }

    /**
     * Send an SMS message via the Semaphore API.
     *
     * Implementation follows the official Semaphore PHP code example exactly:
     * https://semaphore.co/docs#code_examples
     *
     * @param  string  $phoneNumber  Philippine mobile number (09XX or +639XX format)
     * @param  string  $message      The text message body (max 160 chars recommended)
     * @return bool  True if the message was accepted by Semaphore, false otherwise.
     */
    public function send(string $phoneNumber, string $message): bool
    {
        if (empty($this->apiKey)) {
            Log::error('SMS Failed: SEMAPHORE_API_KEY is not set. Please add it to your .env file.');
            return false;
        }

        $phoneNumber = $this->normalizePhoneNumber($phoneNumber);

        try {
            $ch = curl_init();

            $parameters = [
                'apikey'     => $this->apiKey,
                'number'     => $phoneNumber,
                'message'    => $message,
                'sendername' => $this->senderName,
            ];

            curl_setopt($ch, CURLOPT_URL, $this->apiUrl);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($parameters));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);

            $output = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);

            if ($curlError) {
                Log::error('SMS Failed: cURL error.', [
                    'number' => $phoneNumber,
                    'error'  => $curlError,
                ]);
                return false;
            }

            $response = json_decode($output, true);

            // A successful Semaphore response is an array of message objects.
            if ($httpCode >= 200 && $httpCode < 300 && is_array($response)) {
                Log::info('SMS Sent', [
                    'number'   => $phoneNumber,
                    'message'  => $message,
                    'status'   => $httpCode,
                    'response' => $output,
                ]);
                return true;
            }

            Log::error('SMS Failed: Semaphore returned a non-success response.', [
                'number'   => $phoneNumber,
                'status'   => $httpCode,
                'response' => $output,
            ]);
            return false;
        } catch (\Exception $e) {
            Log::error('SMS Failed: ' . $e->getMessage(), [
                'number'  => $phoneNumber,
                'message' => $message,
            ]);
            return false;
        }
    }

    /**
     * Normalize a Philippine phone number to the 09XXXXXXXXX format.
     *
     * Handles common input formats:
     *  - +639XXXXXXXXX  → 09XXXXXXXXX
     *  - 639XXXXXXXXX   → 09XXXXXXXXX
     *  - 09XXXXXXXXX    → 09XXXXXXXXX (no change)
     *
     * @param  string  $number  The raw phone number input
     * @return string  The normalized phone number
     */
    protected function normalizePhoneNumber(string $number): string
    {
        // Remove spaces, dashes, and parentheses
        $number = preg_replace('/[\s\-\(\)]+/', '', $number);

        // Convert +639 or 639 prefix to 09
        if (str_starts_with($number, '+63')) {
            $number = '0' . substr($number, 3);
        } elseif (str_starts_with($number, '63') && strlen($number) === 12) {
            $number = '0' . substr($number, 2);
        }

        return $number;
    }
}
