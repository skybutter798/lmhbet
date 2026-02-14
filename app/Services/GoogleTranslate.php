<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class GoogleTranslate
{
    public function translateMany(array $texts, string $target): array
    {
        // Clean inputs
        $texts = array_values(array_filter($texts, fn($t) => is_string($t) && trim($t) !== ''));
        if (!$texts) return [];

        // English = original
        if ($target === 'en') return $texts;

        $key = config('google_translate.key');
        if (!$key) {
            throw new RuntimeException('Missing GOOGLE_TRANSLATE_API_KEY');
        }

        $results = [];
        $batch = [];
        $batchIndex = [];

        foreach ($texts as $i => $text) {
            $ck = $this->cacheKey($text, $target);
            $cached = Cache::get($ck);

            if (is_string($cached) && $cached !== '') {
                $results[$i] = $cached;
                continue;
            }

            $batch[] = $text;
            $batchIndex[] = $i;

            if (count($batch) >= 50) {
                $this->flushBatch($batch, $batchIndex, $target, $key, $results);
                $batch = [];
                $batchIndex = [];
            }
        }

        if ($batch) {
            $this->flushBatch($batch, $batchIndex, $target, $key, $results);
        }

        ksort($results);

        // keep original order
        $final = [];
        foreach ($texts as $i => $orig) {
            $final[] = $results[$i] ?? $orig;
        }

        return $final;
    }

    private function flushBatch(array $batch, array $batchIndex, string $target, string $key, array &$results): void
    {
        $endpoint = 'https://translation.googleapis.com/language/translate/v2';

        try {
            $resp = Http::timeout(10)
                ->retry(2, 200)
                ->asJson()
                ->post($endpoint . '?key=' . rawurlencode($key), [
                    'q'      => array_values($batch),
                    'target' => $target,
                    'format' => 'text',
                    'model'  => 'nmt',
                ]);

            if (!$resp->ok()) {
                // ✅ THIS is why your site looked like "nothing happen"
                // your old code silently fallback. Now we log it.
                Log::warning('GoogleTranslate API non-OK', [
                    'status' => $resp->status(),
                    'target' => $target,
                    'body'   => $resp->body(),
                ]);

                foreach ($batch as $k => $orig) {
                    $results[$batchIndex[$k]] = $orig;
                }
                return;
            }

            $translations = $resp->json('data.translations') ?? [];

            foreach ($batch as $k => $orig) {
                $translated = $translations[$k]['translatedText'] ?? $orig;
                $translated = html_entity_decode((string) $translated, ENT_QUOTES | ENT_HTML5, 'UTF-8');

                $results[$batchIndex[$k]] = $translated;
                Cache::put($this->cacheKey($orig, $target), $translated, now()->addDays(30));
            }

        } catch (\Throwable $e) {
            Log::error('GoogleTranslate exception', [
                'target' => $target,
                'msg'    => $e->getMessage(),
            ]);

            foreach ($batch as $k => $orig) {
                $results[$batchIndex[$k]] = $orig;
            }
        }
    }

    private function cacheKey(string $text, string $target): string
    {
        return 'gt:' . $target . ':' . sha1($text);
    }
}
