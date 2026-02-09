<?php

namespace App\Support\DBOX;

class DBOXHash
{
    public static function generate(string $base64Secret, string $minifiedJsonBody, string $ts): string
    {
        $base64Secret = trim($base64Secret);

        $decodedKey = base64_decode($base64Secret, true);
        if ($decodedKey === false) {
            throw new \RuntimeException('Invalid Base64 secret (mscrt).');
        }

        $message = $minifiedJsonBody . $ts;
        $raw = hash_hmac('sha512', $message, $decodedKey, true);
        return base64_encode($raw);
    }

    public static function minifyJson(array $data): string
    {
        $json = json_encode(
            $data,
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION
        );

        if ($json === false) {
            throw new \RuntimeException('Failed to json_encode body: ' . json_last_error_msg());
        }

        return $json;
    }

    public static function minifyAndSortJson(array $data): string
    {
        $sorted = self::sortAssocRecursive($data);
        return self::minifyJson($sorted);
    }

    /**
     * Sort ONLY associative arrays. Keep list arrays (txns/betDtls) order unchanged.
     */
    private static function sortAssocRecursive(array $arr): array
    {
        foreach ($arr as $k => $v) {
            if (is_array($v)) {
                $arr[$k] = self::sortAssocRecursive($v);
            }
        }

        if (!self::isList($arr)) {
            ksort($arr);
        }

        return $arr;
    }

    private static function isList(array $arr): bool
    {
        // Compatible with PHP < 8.1
        $i = 0;
        foreach ($arr as $k => $_) {
            if ($k !== $i) return false;
            $i++;
        }
        return true;
    }
}
