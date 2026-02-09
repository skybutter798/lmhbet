<?php

namespace App\Services\DBOX;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Support\DBOX\DBOXHash;

class DBOXClient
{
    public function __construct(
        private readonly string $baseUrl,
        private readonly string $mkey,
        private readonly ?string $mscrt,
        private readonly int $timeoutSeconds = 20,
    ) {}

    public static function makeFromConfig(): self
    {
        return new self(
            baseUrl: rtrim((string) config('services.dbox.base_url'), '/'),
            mkey: (string) config('services.dbox.mkey'),
            mscrt: config('services.dbox.mscrt') ? (string) config('services.dbox.mscrt') : null,
            timeoutSeconds: (int) config('services.dbox.timeout', 20),
        );
    }

    public function get(string $path, array $query = []): Response
    {
        $ts = $this->tsMs();
        $fullUrl = $this->baseUrl . $path;

        Log::info('DBOX GET request', [
            'url' => $fullUrl,
            'headers' => [
                'mkey' => $this->mkey,
                'ts'   => $ts,
            ],
            'query' => $query,
        ]);

        $res = Http::timeout($this->timeoutSeconds)
            ->baseUrl($this->baseUrl)
            ->acceptJson()
            ->withUserAgent('LMHBet/1.0')
            ->withHeaders([
                'mkey' => $this->mkey,
                'ts'   => $ts,
            ])
            ->get($path, $query);

        Log::info('DBOX GET response', [
            'url' => $fullUrl,
            'http_status' => $res->status(),
            'body_first_500' => mb_substr((string) $res->body(), 0, 500),
        ]);

        return $res;
    }

    public function post(string $path, array $body): Response
    {
        $ts = $this->tsMs();
        $fullUrl = $this->baseUrl . $path;
    
        // Build the exact JSON string we will send
        $minified = DBOXHash::minifyJson($body);
    
        Log::info('DBOX hash input debug', [
            'minified' => $minified,
            'ts' => $ts,
            'message_len' => strlen($minified . $ts),
        ]);
    
        Log::error('DBOX POST pre-hash debug', [
            'url' => $fullUrl,
            'timeout' => $this->timeoutSeconds,
            'mkey_prefix' => substr($this->mkey, 0, 8) . '...',
            'ts' => $ts,
            'mscrt_present' => !empty($this->mscrt),
            'mscrt_len' => $this->mscrt ? strlen($this->mscrt) : 0,
            'mscrt_preview' => $this->mscrt ? substr($this->mscrt, 0, 6) . '...' : null,
            'body_array' => $body,
            'body_json' => $minified,
        ]);
    
        if (empty($this->mscrt)) {
            throw new \RuntimeException('DBOX_MSCRT is empty; cannot generate POST hash.');
        }
    
        try {
            $hash = DBOXHash::generate($this->mscrt, $minified, $ts);
        } catch (\Throwable $e) {
            Log::error('DBOX POST hash generation failed', [
                'url' => $fullUrl,
                'ts' => $ts,
                'err' => $e->getMessage(),
            ]);
            throw $e;
        }
    
        Log::info('DBOX POST request', [
            'url' => $fullUrl,
            'headers' => [
                'mkey' => $this->mkey,
                'ts' => $ts,
                'hash_prefix' => substr($hash, 0, 10) . '...',
            ],
            'body_json' => $minified,
        ]);
    
        // ✅ IMPORTANT: send the exact JSON string (byte-for-byte) you hashed
        $res = Http::timeout($this->timeoutSeconds)
            ->baseUrl($this->baseUrl)
            ->acceptJson()
            ->withUserAgent('LMHBet/1.0')
            ->withHeaders([
                'mkey' => $this->mkey,
                'ts'   => $ts,
                'hash' => $hash,
                'Content-Type' => 'application/json',
            ])
            ->withBody($minified, 'application/json')
            ->post($path);
    
        Log::info('DBOX POST response', [
            'url' => $fullUrl,
            'http_status' => $res->status(),
            'body_first_800' => mb_substr((string) $res->body(), 0, 800),
        ]);
    
        return $res;
    }


    private function tsMs(): string
    {
        return (string) (int) round(microtime(true) * 1000);
    }
}
