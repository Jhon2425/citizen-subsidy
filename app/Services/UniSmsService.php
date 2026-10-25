<?php

namespace App\Services;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class UniSmsService
{
    protected string $baseUrl;
    protected string $secretKey;
    protected string $senderId;
    protected string $fallbackSenderId;

    public function __construct()
    {
        $this->baseUrl          = rtrim(config('services.unisms.base_url', 'https://unismsapi.com/api'), '/');
        $this->secretKey        = (string) config('services.unisms.secret_key');
        $this->senderId         = trim((string) config('services.unisms.sender_id'));
        $this->fallbackSenderId = trim((string) config('services.unisms.fallback_sender_id'));
    }

    /**
     * Send the same message to many numbers (one API request per recipient,
     * run in concurrent batches).
     *
     * Numbers the primary sender ID can't reach (e.g. "can't send to Smart,
     * TNT or Sun numbers") are retried once with the fallback sender ID, if one
     * is configured.
     *
     * @param  array<int, string>  $numbers  PH numbers, 09XXXXXXXXX or +639XXXXXXXXX
     * @return array{sent: int, failed: int, errors: array<int, string>}
     */
    public function sendBulk(array $numbers, string $message): array
    {
        $result  = ['sent' => 0, 'failed' => 0, 'errors' => []];
        $numbers = array_values($numbers);

        if ($this->secretKey === '') {
            $result['failed'] = count($numbers);
            $result['errors'][] = 'UNISMS_SECRET_KEY is not set in .env.';
            return $result;
        }

        if ($this->senderId === '') {
            $result['failed'] = count($numbers);
            $result['errors'][] = 'UNISMS_SENDER_ID is not set in .env (UniSMS requires a sender ID).';
            return $result;
        }

        // Pass 1: primary sender ID.
        $outcomes = $this->sendBatch($numbers, $message, $this->senderId);

        // Pass 2: retry only the numbers rejected because of the sender ID.
        if ($this->fallbackSenderId !== '' && $this->fallbackSenderId !== $this->senderId) {
            $retry = array_keys(array_filter($outcomes, fn ($o) => ! $o['ok'] && $o['sender_issue']));

            if ($retry) {
                Log::info('UniSMS retrying with fallback sender ID', [
                    'count'    => count($retry),
                    'fallback' => $this->fallbackSenderId,
                ]);

                foreach ($this->sendBatch($retry, $message, $this->fallbackSenderId) as $number => $outcome) {
                    $outcomes[$number] = $outcome;
                }
            }
        }

        $failedNumbers = [];

        foreach ($outcomes as $number => $outcome) {
            if ($outcome['ok']) {
                $result['sent']++;
                continue;
            }

            $result['failed']++;
            $result['errors'][]   = $outcome['reason'];
            $failedNumbers[]      = $this->mask((string) $number);
        }

        // Collapse duplicate reasons (e.g. 200 identical errors).
        $result['errors'] = array_values(array_unique($result['errors']));

        if ($result['failed'] > 0) {
            Log::warning('UniSMS bulk send had failures', [
                'sent'           => $result['sent'],
                'failed'         => $result['failed'],
                'errors'         => $result['errors'],
                'failed_numbers' => $failedNumbers,
            ]);
        }

        return $result;
    }

    /**
     * Send to the given numbers with one sender ID.
     *
     * @param  array<int, string>  $numbers
     * @return array<string, array{ok: bool, reason: string, sender_issue: bool}>  keyed by number
     */
    protected function sendBatch(array $numbers, string $message, string $senderId): array
    {
        $outcomes = [];

        foreach (array_chunk(array_values($numbers), 20) as $chunk) {
            $responses = Http::pool(function ($pool) use ($chunk, $message, $senderId) {
                foreach ($chunk as $i => $number) {
                    $pool->as((string) $i)
                        ->withBasicAuth($this->secretKey, '')
                        ->acceptJson()
                        ->asJson()
                        ->timeout(30)
                        ->post("{$this->baseUrl}/sms", [
                            'recipient' => $this->toE164($number),
                            'content'   => $message,
                            'sender_id' => $senderId,
                        ]);
                }
            });

            foreach ($chunk as $i => $number) {
                $res = $responses[(string) $i] ?? null;

                if ($res instanceof Response && $res->successful()) {
                    $outcomes[$number] = ['ok' => true, 'reason' => '', 'sender_issue' => false];
                    continue;
                }

                if ($res instanceof Response) {
                    $detail = $this->extractReason($res);

                    $outcomes[$number] = [
                        'ok'           => false,
                        'reason'       => 'HTTP ' . $res->status() . ': ' . $detail,
                        // e.g. "This Sender ID can't send to Smart, TNT or Sun numbers..."
                        'sender_issue' => $res->status() === 422 && stripos($detail, 'sender id') !== false,
                    ];
                    continue;
                }

                $outcomes[$number] = [
                    'ok'           => false,
                    'reason'       => $res instanceof \Throwable ? $res->getMessage() : 'No response',
                    'sender_issue' => false,
                ];
            }
        }

        return $outcomes;
    }

    /** Pull a readable message out of UniSMS's error JSON. */
    protected function extractReason(Response $res): string
    {
        $json = $res->json();

        if (is_array($json)) {
            if (! empty($json['errors']) && is_array($json['errors'])) {
                $flat = [];
                array_walk_recursive($json['errors'], function ($v) use (&$flat) {
                    if (is_string($v)) {
                        $flat[] = $v;
                    }
                });

                if ($flat) {
                    return implode(' ', $flat);
                }
            }

            if (! empty($json['message']) && is_string($json['message'])) {
                return $json['message'];
            }
        }

        return $res->body();
    }

    /** 09XXXXXXXXX -> 0917***4567 (keeps logs free of full phone numbers) */
    protected function mask(string $number): string
    {
        return strlen($number) >= 8
            ? substr($number, 0, 4) . '***' . substr($number, -4)
            : $number;
    }

    /** 09XXXXXXXXX -> +639XXXXXXXXX */
    protected function toE164(string $number): string
    {
        $n = preg_replace('/\D+/', '', $number);

        if (str_starts_with($n, '0')) {
            $n = '63' . substr($n, 1);
        }

        return '+' . $n;
    }
}