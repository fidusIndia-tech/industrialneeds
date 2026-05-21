<?php

namespace App\Library;

// Adapted from Paytm's official Web_Sample_Kit_PHP / paytm-checksum library.
// Algorithm: AES-128-CBC encrypt(SHA256(payload + "|" + salt) + salt, merchantKey).
// Used by both the legacy form-post flow and the modern initiateTransaction flow —
// the difference is what string you sign, not how you sign it.
class PaytmChecksum
{
    private const IV = "@@@@&&&&####$$$$";

    public static function generateSignature($params, string $key): string
    {
        if (is_array($params)) {
            ksort($params);
            $params = self::getStringByParams($params);
        }
        return self::generateSignatureByString((string) $params, $key);
    }

    public static function verifySignature($params, string $key, string $checksum): bool
    {
        if (is_array($params)) {
            unset($params['CHECKSUMHASH']);
            ksort($params);
            $params = self::getStringByParams($params);
        }
        return self::verifySignatureByString((string) $params, $key, $checksum);
    }

    private static function generateSignatureByString(string $params, string $key): string
    {
        $salt = self::generateRandomString(4);
        $hash = hash('sha256', $params . '|' . $salt) . $salt;
        return self::encrypt($hash, $key);
    }

    private static function verifySignatureByString(string $params, string $key, string $checksum): bool
    {
        $paytmHash = self::decrypt($checksum, $key);
        if ($paytmHash === false || strlen($paytmHash) < 4) {
            return false;
        }
        $salt = substr($paytmHash, -4);
        $ourHash = hash('sha256', $params . '|' . $salt) . $salt;
        return hash_equals($paytmHash, $ourHash);
    }

    private static function getStringByParams(array $params): string
    {
        $parts = [];
        foreach ($params as $value) {
            $parts[] = (strtolower((string) $value) === 'null') ? '' : (string) $value;
        }
        return implode('|', $parts);
    }

    private static function generateRandomString(int $length): string
    {
        $alphabet = 'AbcDE123IJKLMN67QRSTUVWXYZaBCdefghijklmn123opq45rs67tuv89wxyz0FGH45OP89';
        $max = strlen($alphabet) - 1;
        $out = '';
        for ($i = 0; $i < $length; $i++) {
            $out .= $alphabet[random_int(0, $max)];
        }
        return $out;
    }

    private static function encrypt(string $input, string $key): string
    {
        return openssl_encrypt($input, 'AES-128-CBC', html_entity_decode($key), 0, self::IV);
    }

    private static function decrypt(string $crypt, string $key): string
    {
        return (string) openssl_decrypt($crypt, 'AES-128-CBC', html_entity_decode($key), 0, self::IV);
    }
}
