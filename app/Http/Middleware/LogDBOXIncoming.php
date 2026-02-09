<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class LogDBOXIncoming
{
    public function handle(Request $request, Closure $next): Response
    {
        $raw = (string) $request->getContent();

        Log::info('DBOX CALLBACK HIT', [
            'method' => $request->method(),
            'path'   => '/' . ltrim($request->path(), '/'),
            'ip'     => $request->ip(),
            'headers'=> [
                'mkey'         => $request->header('mkey'),
                'ts'           => $request->header('ts'),
                'hash_prefix'  => $request->header('hash') ? substr((string)$request->header('hash'), 0, 12) . '...' : null,
                'content_type' => $request->header('content-type'),
                'user_agent'   => $request->header('user-agent'),
            ],
            'body_first_1200' => mb_substr($raw, 0, 1200),
        ]);

        return $next($request);
    }
}
