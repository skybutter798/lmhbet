<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Support\DBOX\DBOXHash;
use Illuminate\Support\Facades\Log;

class VerifyDBOXSignature
{
    private function fail(string $msg, int $code = -1): Response
    {
        // Providers often expect HTTP 200 + {code,msg,data}
        return response()->json([
            'code' => $code,
            'msg'  => $msg,
            'data' => null,
        ], 200);
    }

    private function tsToMs(string $ts): ?int
    {
        if ($ts === '' || !ctype_digit($ts)) return null;

        // if seconds (10 digits or less), convert to ms
        if (strlen($ts) <= 10) return (int) $ts * 1000;

        // assume ms
        return (int) $ts;
    }

    public function handle(Request $request, Closure $next): Response
    {
        $mkey = (string) $request->header('mkey', '');
        $ts   = (string) $request->header('ts', '');
        $hash = (string) $request->header('hash', '');

        $expectedMkey = (string) config('services.dbox.mkey');
        $mscrt        = (string) config('services.dbox.mscrt');

        if ($mkey === '' || $ts === '' || $hash === '') {
            Log::warning('DBOX sig missing headers', ['path' => $request->path(), 'ip' => $request->ip()]);
            return $this->fail('Failed');
        }

        if ($expectedMkey !== '' && !hash_equals($expectedMkey, $mkey)) {
            Log::warning('DBOX sig invalid mkey', ['path' => $request->path(), 'ip' => $request->ip()]);
            return $this->fail('Failed');
        }

        if (trim($mscrt) === '') {
            Log::error('DBOX sig server missing mscrt', ['path' => $request->path()]);
            return $this->fail('Failed', 999999);
        }

        // timestamp sanity check (default ±300s)
        $windowSeconds = (int) config('services.dbox.ts_window', 300);
        $tsMs = $this->tsToMs($ts);

        if ($tsMs === null) {
            Log::warning('DBOX sig invalid ts', ['path' => $request->path(), 'ip' => $request->ip(), 'ts' => $ts]);
            return $this->fail('Failed');
        }

        $nowMs = (int) round(microtime(true) * 1000);
        $skew  = abs($nowMs - $tsMs);

        if ($skew > ($windowSeconds * 1000)) {
            Log::warning('DBOX sig ts outside window', [
                'path' => $request->path(),
                'ip' => $request->ip(),
                'ts' => $ts,
                'skew_ms' => $skew,
                'window_s' => $windowSeconds,
            ]);
            return $this->fail('Failed');
        }

        $raw = (string) $request->getContent();
        if ($raw === '') {
            Log::warning('DBOX sig empty body', ['path' => $request->path(), 'ip' => $request->ip()]);
            return $this->fail('Failed');
        }

        // 1) raw
        try {
            $calc1 = DBOXHash::generate($mscrt, $raw, $ts);
            if (hash_equals($calc1, $hash)) return $next($request);
        } catch (\Throwable $e) {
            Log::warning('DBOX sig calc1 error', ['err' => $e->getMessage(), 'path' => $request->path()]);
        }

        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            Log::warning('DBOX sig invalid json', ['path' => $request->path(), 'ip' => $request->ip()]);
            return $this->fail('Failed');
        }

        // 2) minify (keep order from incoming JSON)
        $minified = DBOXHash::minifyJson($decoded);
        $calc2 = DBOXHash::generate($mscrt, $minified, $ts);
        if (hash_equals($calc2, $hash)) return $next($request);

        // 3) minify + sort associative keys only (safe)
        $minSorted = DBOXHash::minifyAndSortJson($decoded);
        $calc3 = DBOXHash::generate($mscrt, $minSorted, $ts);
        if (hash_equals($calc3, $hash)) return $next($request);

        Log::warning('DBOX sig mismatch', [
            'path' => $request->path(),
            'ip' => $request->ip(),
            'ts' => $ts,
            'raw_first_200' => mb_substr($raw, 0, 200),
        ]);

        return $this->fail('Failed');
    }
}
