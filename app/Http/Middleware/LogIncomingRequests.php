<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class LogIncomingRequests
{
    public function handle(Request $request, Closure $next)
    {
        $cid = 'incoming_' . now()->format('YmdHis') . '_' . substr(bin2hex(random_bytes(6)), 0, 12);

        $raw = (string) $request->getContent();

        Log::channel('winpay_daily')->info('INCOMING request', [
            'cid' => $cid,
            'ip' => $request->ip(),
            'method' => $request->method(),
            'path' => $request->path(),
            'ua' => $request->userAgent(),
            'content_type' => $request->header('Content-Type'),
            'headers' => $this->lightHeaders($request),
            'raw_first_8000' => mb_substr($raw, 0, 8000),
        ]);

        $response = $next($request);

        Log::channel('winpay_daily')->info('INCOMING response', [
            'cid' => $cid,
            'status' => method_exists($response, 'status') ? $response->status() : null,
        ]);

        return $response;
    }

    private function lightHeaders(Request $request): array
    {
        $keep = [
            'x-forwarded-for',
            'x-real-ip',
            'x-request-id',
            'content-type',
            'accept',
            'user-agent',
            'cf-connecting-ip',
            'cf-ray',
        ];

        $out = [];
        foreach ($keep as $k) {
            $v = $request->header($k);
            if ($v !== null && $v !== '') $out[$k] = $v;
        }
        return $out;
    }
}
