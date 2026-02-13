<?php
// /home/lmh/app/app/Services/Payments/WinPayClient.php

namespace App\Services\Payments;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WinPayClient
{
    protected string $baseUrl = '';
    protected string $payPath = '';
    protected string $queryPath = '';
    protected string $clientId = '';
    protected string $apiKey = '';
    protected string $notifyUrl = '';
    protected string $returnUrl = '';
    protected int $timeout = 20;
    protected bool $debug = false;
    protected string $logChannel = 'winpay_daily';

    // NEW: delimiter between key=value pairs for sign source
    protected string $signDelimiter = '&';

    /**
     * DI-safe: resolving via app()/constructor injection loads config.
     */
    public function __construct(?array $cfg = null)
    {
        $cfg = $cfg ?? (array) config('services.winpay', []);

        $this->baseUrl    = rtrim((string)($cfg['base_url'] ?? ''), '/');
        $this->payPath    = (string)($cfg['pay_path'] ?? '');
        $this->queryPath  = (string)($cfg['query_path'] ?? '');
        $this->clientId   = (string)($cfg['client_id'] ?? '');
        $this->apiKey     = (string)($cfg['api_key'] ?? '');
        $this->notifyUrl  = (string)($cfg['notify_url'] ?? '');
        $this->returnUrl  = (string)($cfg['return_url'] ?? '');
        $this->timeout    = (int)($cfg['timeout'] ?? 20);
        $this->debug      = (bool)($cfg['debug'] ?? false);
        $this->logChannel = (string)($cfg['log_channel'] ?? 'winpay_daily');

        $delim = (string)($cfg['sign_delimiter'] ?? '&');
        $this->signDelimiter = ($delim === '&&') ? '&&' : '&';

        $this->log('info', 'WINPAY client init', [
            'base_url' => $this->baseUrl,
            'pay_path' => $this->payPath,
            'query_path' => $this->queryPath,
            'notify_url_present' => $this->notifyUrl !== '',
            'return_url_present' => $this->returnUrl !== '',
            'client_id_present' => $this->clientId !== '',
            'api_key_present' => $this->apiKey !== '',
            'sign_delimiter' => $this->signDelimiter,
        ]);
    }

    public static function make(?array $cfg = null): self
    {
        return new self($cfg);
    }

    public function createDeposit(array $payload, ?string $cid = null): array
    {
        return $this->createOrder($payload, $cid);
    }

    public function deposit(array $payload, ?string $cid = null): array
    {
        return $this->createOrder($payload, $cid);
    }

    public function createOrder(array $payload, ?string $cid = null): array
    {
        $cid = $cid ?: ('winpay_' . now()->format('YmdHis') . '_' . substr(bin2hex(random_bytes(6)), 0, 12));

        if ($this->payPath === '' || $this->baseUrl === '') {
            $this->log('error', 'WINPAY createOrder: missing config', [
                'cid' => $cid,
                'base_url' => $this->baseUrl,
                'pay_path' => $this->payPath,
                'services_winpay' => array_keys((array) config('services.winpay', [])),
            ]);

            return [
                'code' => -998,
                'message' => 'WINPAY config missing: base_url or pay_path',
            ];
        }

        $url = $this->resolveUrl($this->payPath);

        // Ensure required fields exist (doc says notify_url/return_url are mandatory)
        $data = array_merge([
            'client_id'   => $this->clientId,
            'notify_url'  => $this->notifyUrl,
            'return_url'  => $this->returnUrl,
        ], $payload);

        $data = $this->withSign($data, $cid, 'createOrder');

        $this->log('info', 'WINPAY createOrder: request', [
            'cid' => $cid,
            'url' => $url,
            'payload' => $this->maskArray($data),
        ]);

        try {
            $res = $this->postJson($url, $data);
            return $this->handleResponse($res, $cid, 'createOrder');
        } catch (ConnectionException $e) {
            $this->log('error', 'WINPAY createOrder: connection exception', [
                'cid' => $cid,
                'url' => $url,
                'err' => $e->getMessage(),
            ]);
            throw $e;
        } catch (\Throwable $e) {
            $this->log('error', 'WINPAY createOrder: exception', [
                'cid' => $cid,
                'url' => $url,
                'err' => $e->getMessage(),
                'ex'  => get_class($e),
            ]);
            throw $e;
        }
    }

    // Your controller calls queryDeposit()
    public function queryDeposit(string $billNumber, ?string $cid = null): array
    {
        return $this->queryOrder($billNumber, $cid);
    }

    public function queryOrder(string $billNumber, ?string $cid = null): array
    {
        $cid = $cid ?: ('winpay_' . now()->format('YmdHis') . '_' . substr(bin2hex(random_bytes(6)), 0, 12));

        if ($this->queryPath === '' || $this->baseUrl === '') {
            $this->log('error', 'WINPAY queryOrder: missing config', [
                'cid' => $cid,
                'base_url' => $this->baseUrl,
                'query_path' => $this->queryPath,
                'services_winpay' => array_keys((array) config('services.winpay', [])),
            ]);

            return [
                'code' => -998,
                'message' => 'WINPAY config missing: base_url or query_path',
            ];
        }

        $url = $this->resolveUrl($this->queryPath);

        $data = [
            'client_id'   => $this->clientId,
            'bill_number' => $billNumber,
            'timestamp'   => now()->format('Y-m-d H:i:s'),
        ];

        $data = $this->withSign($data, $cid, 'queryOrder');

        $this->log('info', 'WINPAY queryOrder: request', [
            'cid' => $cid,
            'url' => $url,
            'payload' => $this->maskArray($data),
        ]);

        try {
            $res = $this->postJson($url, $data);
            return $this->handleResponse($res, $cid, 'queryOrder');
        } catch (ConnectionException $e) {
            $this->log('error', 'WINPAY queryOrder: connection exception', [
                'cid' => $cid,
                'url' => $url,
                'err' => $e->getMessage(),
            ]);
            throw $e;
        } catch (\Throwable $e) {
            $this->log('error', 'WINPAY queryOrder: exception', [
                'cid' => $cid,
                'url' => $url,
                'err' => $e->getMessage(),
                'ex'  => get_class($e),
            ]);
            throw $e;
        }
    }

    public function verifySign(array $data, ?string $cid = null, string $scene = 'verifySign'): bool
    {
        $cid = $cid ?: ('winpay_' . now()->format('YmdHis') . '_' . substr(bin2hex(random_bytes(6)), 0, 12));

        $incoming = (string)($data['sign'] ?? '');
        $calc = $this->calcSign($data);

        $ok = ($incoming !== '' && hash_equals((string)$incoming, (string)$calc));

        if ($this->debug) {
            $this->log($ok ? 'info' : 'warning', "WINPAY {$scene}: sign check", [
                'cid' => $cid,
                'ok' => $ok,
                'incoming_sign' => $this->mask($incoming, 6),
                'calc_sign' => $this->mask((string)$calc, 6),
                'sign_src' => $this->maskKeyInString($this->buildSignSrc($data)),
            ]);
        }

        return $ok;
    }

    protected function postJson(string $url, array $data): Response
    {
        $req = Http::asJson()
            ->acceptJson()
            ->timeout($this->timeout)
            ->retry(0, 0)
            ->withHeaders([
                'User-Agent' => 'LMH-WinPay/1.0',
            ]);

        if ($this->debug && env('WINPAY_CURL_DEBUG', false)) {
            $fp = fopen(storage_path('logs/winpay_curl_debug.log'), 'a');
            $req = $req->withOptions(['debug' => $fp]);
        }

        return $req->post($url, $data);
    }

    protected function handleResponse(Response $res, string $cid, string $scene): array
    {
        $status = $res->status();
        $raw = $res->body();

        $json = null;
        $jsonErr = null;

        try {
            $json = $res->json();
        } catch (\Throwable $e) {
            $jsonErr = $e->getMessage();
        }

        $this->log('info', "WINPAY {$scene}: response", [
            'cid' => $cid,
            'http_status' => $status,
            'ok' => $res->ok(),
            'content_type' => $res->header('Content-Type'),
            'body_raw' => $this->limitString($raw, 5000),
            'json_parse_error' => $jsonErr,
            'json' => is_array($json) ? $this->maskArray($json) : null,
        ]);

        if (!is_array($json)) {
            return [
                'code' => -999,
                'message' => 'Non-JSON response from gateway',
                'http_status' => $status,
                'raw' => $this->limitString($raw, 5000),
            ];
        }

        $code = $json['code'] ?? $json['Code'] ?? null;
        $msg  = $json['message'] ?? $json['msg'] ?? $json['Message'] ?? null;

        $out = $json;
        if ($code !== null) $out['code'] = $code;
        if ($msg !== null)  $out['message'] = $msg;

        return $out;
    }

    protected function withSign(array $data, string $cid, string $scene): array
    {
        $data['sign'] = $this->calcSign($data);

        if ($this->debug) {
            $src = $this->buildSignSrc($data);

            $this->log('info', "WINPAY {$scene}: sign built", [
                'cid' => $cid,
                'client_id' => $data['client_id'] ?? null,
                'sign_delimiter' => $this->signDelimiter,
                'sign_src' => $this->maskKeyInString($src),
                'sign' => $this->mask((string)$data['sign'], 6),
            ]);
        }

        return $data;
    }

    protected function calcSign(array $data): string
    {
        $src = $this->buildSignSrc($data);
        return strtolower(md5($src));
    }

    protected function buildSignSrc(array $data): string
    {
        unset($data['sign']);

        $filtered = [];
        foreach ($data as $k => $v) {
            if ($v === null) continue;
            if (is_string($v) && trim($v) === '') continue;
            $filtered[$k] = $v;
        }

        ksort($filtered, SORT_STRING);

        $pairs = [];
        foreach ($filtered as $k => $v) {
            if (is_bool($v)) $v = $v ? '1' : '0';
            if (is_array($v) || is_object($v)) {
                $v = json_encode($v, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            }
            $pairs[] = $k . '=' . $v;
        }

        // doc says connect with "&&" (some systems use "&")
        $src = implode($this->signDelimiter, $pairs);

        // keep consistent delimiter before key=
        $src .= $this->signDelimiter . 'key=' . $this->apiKey;

        return $src;
    }

    protected function resolveUrl(string $pathOrUrl): string
    {
        $p = trim($pathOrUrl);
        if ($p === '') return $this->baseUrl;
        if (str_starts_with($p, 'http://') || str_starts_with($p, 'https://')) return $p;
        return $this->baseUrl . '/' . ltrim($p, '/');
    }

    protected function log(string $level, string $msg, array $ctx = []): void
    {
        try {
            Log::channel($this->logChannel)->{$level}($msg, $ctx);
        } catch (\Throwable $e) {
        }
    }

    protected function mask(?string $s, int $show = 4): ?string
    {
        if ($s === null) return null;
        $s = (string)$s;
        $len = strlen($s);
        if ($len <= $show * 2) return str_repeat('*', $len);
        return substr($s, 0, $show) . str_repeat('*', $len - ($show * 2)) . substr($s, -$show);
    }

    protected function maskKeyInString(string $signSrc): string
    {
        // mask key=xxx in either "&key=" or "&&key="
        return preg_replace_callback('/(key=)([^&]+)/', function ($m) {
            return $m[1] . $this->mask($m[2], 4);
        }, $signSrc) ?? $signSrc;
    }

    protected function limitString(?string $s, int $max): ?string
    {
        if ($s === null) return null;
        if (strlen($s) <= $max) return $s;
        return substr($s, 0, $max) . '...[truncated]';
    }

    protected function maskArray(array $arr): array
    {
        $sensitiveKeys = [
            'api_key', 'apikey', 'key', 'secret', 'password', 'token',
            'sign', 'signature', 'authorization', 'Authorization',
        ];

        $out = [];
        foreach ($arr as $k => $v) {
            $lk = strtolower((string)$k);

            if (in_array($lk, $sensitiveKeys, true)) {
                $out[$k] = is_string($v) ? $this->mask($v, 4) : '***';
                continue;
            }

            if (is_array($v)) {
                $out[$k] = $this->maskArray($v);
                continue;
            }

            $out[$k] = $v;
        }
        return $out;
    }
}
