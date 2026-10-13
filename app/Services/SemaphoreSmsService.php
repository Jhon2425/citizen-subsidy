<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SemaphoreSmsService
{
    protected ?string $apiKey;
    protected ?string $senderName;
    protected string $endpoint;

    public function __construct()
    {
        $this->apiKey     = config('services.semaphore.api_key');
        $this->senderName = config('services.semaphore.sender_name');
        $this->endpoint   = config('services.semaphore.endpoint', 'https://semaphore.co/api/v4/messages');
    }

    /**
     * Send one message to many numbers.
     *
     * @param  array<int, string>  $numbers  PH numbers, e.g. 09XXXXXXXXX
     * @return array{sent: int, failed: int, errors: array<int, string>}
     */
    public function sendBulk(array $numbers, string $message): array
    {
        $sent = 0;
        $failed = 0;
        $errors = [];

        $numbers = array_values(array_unique(array_filter($numbers)));

        // Fail gracefully instead of crashing the whole page.
        if (empty($this->apiKey)) {
            Log::warning('Semaphore SMS skipped: SEMAPHORE_API_KEY is not set.');

            return [
                'sent'   => 0,
                'failed' => count($numbers),
                'errors' => ['SMS is not configured. Set SEMAPHORE_API_KEY in .env and run php artisan config:clear.'],
            ];
        }

        foreach (array_chunk($numbers, 100) as $chunk) {
            $payload = [
                'apikey'  => $this->apiKey,
                'number'  => implode(',', $chunk),
                'message' => $message,
            ];

            // Only send a sender name if one is configured (and approved by Semaphore).
            if (!empty($this->senderName)) {
                $payload['sendername'] = $this->senderName;
            }

            try {
                $response = Http::asForm()
                    ->timeout(30)
                    ->post($this->endpoint, $payload);
            } catch (\Throwable $e) {
                $failed += count($chunk);
                $errors[] = $e->getMessage();

                Log::error('Semaphore SMS request threw an exception', [
                    'error' => $e->getMessage(),
                ]);

                continue;
            }

            Log::info('Semaphore SMS response', [
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);

            $body = $response->json();

            // Success = HTTP 2xx AND a list of message objects (each has a message_id).
            $isMessageList = is_array($body) && isset($body[0]['message_id']);

            if ($response->successful() && $isMessageList) {
                foreach ($body as $msg) {
                    $status = strtolower($msg['status'] ?? '');

                    if (in_array($status, ['failed', 'refunded'], true)) {
                        $failed++;
                    } else {
                        $sent++; // pending / queued / sent
                    }
                }
            } else {
                $failed += count($chunk);
                $errors[] = $response->body();

                Log::error('Semaphore SMS batch failed', [
                    'status' => $response->status(),
                    'body'   => $response->body(),
                ]);
            }
        }

        return ['sent' => $sent, 'failed' => $failed, 'errors' => $errors];
    }
}