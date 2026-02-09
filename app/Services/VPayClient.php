<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class VPayClient
{
    public function __construct(
        private readonly string $gateway,
        private readonly string $token,
        private readonly string $traderId,
    ) {}

    public static function make(): self
    {
        $cfg = config('services.vpay');

        return new self(
            gateway: rtrim($cfg['gateway'], '/'),
            token: (string) $cfg['token'],
            traderId: (string) $cfg['trader_id'],
        );
    }

    /**
     * Signature rules:
     * - sort body params by ASCII key
     * - exclude empty and exclude sign (and any non-signed fields if needed)
     * - MD5(strA) uppercase = ap
     * - dt = local datetime from unix timestamp t (yyyy-MM-dd HH:mm:ss)
     * - strB = token=...&dt=...&ap=...
     * - sign = base64(strB)  (DO NOT uppercase base64; it's case-sensitive)
     */
    public function sign(array $params, int $unixTime): string
    {
        // Remove sign itself and any fields you don't want included
        unset($params['sign']);

        $filtered = [];
        foreach ($params as $k => $v) {
            if ($v === null) continue;
            if (is_string($v) && trim($v) === '') continue;
            $filtered[$k] = $v;
        }

        ksort($filtered, SORT_STRING);

        $pairs = [];
        foreach ($filtered as $k => $v) {
            // do not urlencode; follow doc example
            $pairs[] = $k . '=' . $v;
        }
        $strA = implode('&', $pairs);

        $ap = strtoupper(md5($strA));

        // dt must be LOCAL time string (server timezone should be Asia/Kuala_Lumpur)
        $dt = date('Y-m-d H:i:s', $unixTime);

        $strB = "token={$this->token}&dt={$dt}&ap={$ap}";

        // IMPORTANT: DO NOT strtoupper() base64 output
        return base64_encode($strB);
    }

    public function unifiedOrder(array $params): array
    {
        $t = time();

        $base = [
            'action'    => 'TRADER_ORDER',
            'trader_id' => $this->traderId,
        ];

        $payload = array_merge($base, $params);
        $payload['sign'] = $this->sign($payload, $t);

        $url = "{$this->gateway}/app/v1/trader/rest?t={$t}";

        $res = Http::timeout(15)
            ->acceptJson()
            ->asJson()
            ->post($url, $payload);

        if (!$res->ok()) {
            throw new RuntimeException("VPay HTTP error: {$res->status()}");
        }

        $json = $res->json();
        if (!is_array($json)) {
            throw new RuntimeException("VPay invalid JSON");
        }

        return $json;
    }

    public function verifySign(array $body, int $unixTime, string $sign): bool
    {
        $expected = $this->sign($body, $unixTime);

        // IMPORTANT: do NOT strtoupper($sign) either
        return hash_equals($expected, $sign);
    }
}