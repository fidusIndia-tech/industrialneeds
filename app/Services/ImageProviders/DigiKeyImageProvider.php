<?php

namespace App\Services\ImageProviders;

use Illuminate\Support\Facades\Http;

/**
 * DigiKey image provider. Looks up a part by manufacturer part number and returns the catalog photo
 * only when the result is an EXACT MPN match whose manufacturer matches the product's brand (or a
 * known equivalent — Telemecanique ≡ Schneider). Tracks DigiKey's daily quota so the job can stop
 * cleanly instead of hammering 429s.
 *
 * (This is the same proven matching logic used by the products:fetch-images command, packaged behind
 * the provider interface so the queue job and the command can share one implementation over time.)
 */
class DigiKeyImageProvider implements ProductImageProviderInterface
{
    private $token = false;          // false = not fetched, null = failed, string = bearer
    private $remaining = null;       // x-ratelimit-remaining from the last call

    public function name(): string
    {
        return 'digikey';
    }

    public function isAvailable(): bool
    {
        return (string) config('services.digikey.client_id') !== ''
            && (string) config('services.digikey.client_secret') !== '';
    }

    /** DigiKey daily calls left (null until first call). */
    public function remainingQuota(): ?int
    {
        return $this->remaining;
    }

    public function findImage(string $partNumber, ?string $brand): array
    {
        if (!$this->isAvailable()) {
            return $this->result('skipped');
        }
        // If we already know the quota is gone, don't waste a call — tell the job to retry later.
        if ($this->remaining !== null && $this->remaining <= 0) {
            return $this->result('quota');
        }

        $id = (string) config('services.digikey.client_id');
        $secret = (string) config('services.digikey.client_secret');

        for ($authTry = 0; $authTry < 2; $authTry++) {
            $token = $this->accessToken($id, $secret);
            if (!$token) {
                return $this->result('error', null, null, null, null, 'auth failed');
            }
            try {
                $resp = Http::withToken($token)
                    ->withHeaders(['X-DIGIKEY-Client-Id' => $id, 'X-DIGIKEY-Locale-Site' => 'US', 'X-DIGIKEY-Locale-Currency' => 'USD'])
                    ->timeout(25)->acceptJson()
                    ->post('https://api.digikey.com/products/v4/search/keyword', ['Keywords' => $partNumber, 'Limit' => 10]);
            } catch (\Throwable $e) {
                return $this->result('error', null, null, null, null, $e->getMessage());
            }

            $rem = $resp->header('x-ratelimit-remaining');
            if ($rem !== null && $rem !== '') { $this->remaining = (int) $rem; }

            if ($resp->status() === 401 && $authTry === 0) {
                \Illuminate\Support\Facades\Cache::forget('digikey_access_token');
                $this->token = false; continue; // token expired — refresh once
            }
            if ($resp->status() === 429) { return $this->result('quota'); }
            if (!$resp->ok()) { return $this->result('error', null, null, null, null, 'HTTP ' . $resp->status()); }

            $cands = [];
            foreach (($resp->json('Products') ?? []) as $prod) {
                $cands[] = [
                    'mpn'          => $prod['ManufacturerProductNumber'] ?? ($prod['ManufacturerPartNumber'] ?? ''),
                    'manufacturer' => $prod['Manufacturer']['Name'] ?? ($prod['Manufacturer']['Value'] ?? ''),
                    'image'        => $prod['PhotoUrl'] ?? '',
                ];
            }
            return $this->pickTrusted($cands, $partNumber, $brand);
        }
        return $this->result('error', null, null, null, null, '401');
    }

    private function accessToken(string $id, string $secret): ?string
    {
        if (is_string($this->token) && $this->token !== '') {
            return $this->token;
        }
        // Shared across many queued jobs (token lives ~30 min) to avoid re-authing per job.
        $this->token = \Illuminate\Support\Facades\Cache::remember('digikey_access_token', 1500, function () use ($id, $secret) {
            try {
                $resp = Http::asForm()->timeout(20)->post('https://api.digikey.com/v1/oauth2/token', [
                    'client_id' => $id, 'client_secret' => $secret, 'grant_type' => 'client_credentials',
                ]);
                return $resp->ok() ? ($resp->json('access_token') ?: null) : null;
            } catch (\Throwable $e) {
                return null;
            }
        });
        return $this->token;
    }

    /** Accept only an exact MPN match whose manufacturer matches the brand (or equivalent) AND has an image. */
    private function pickTrusted(array $cands, string $code, ?string $brand): array
    {
        $want = $this->normalize($code);
        $exactNoImage = false;
        foreach ($cands as $c) {
            if ($this->normalize($c['mpn'] ?? '') !== $want) { continue; }
            $img = trim((string) ($c['image'] ?? ''));
            if ($img === '') { $exactNoImage = true; continue; }
            if ($this->brandMatches($brand, (string) ($c['manufacturer'] ?? ''))) {
                return $this->result('image', $img, 90, $c['mpn'] ?? null, $c['manufacturer'] ?? null);
            }
            $exactNoImage = true; // exact MPN + image but brand mismatch — not trusted
        }
        return $this->result($exactNoImage ? 'details_no_image' : 'no_match');
    }

    private function brandMatches(?string $brand, string $mfr): bool
    {
        $bn = $this->normalize((string) $brand);
        if ($bn === '') { return true; }
        $mn = $this->normalize($mfr);
        if ($mn === '') { return false; }
        $tokens = [$bn];
        $equiv = [
            'TELEMECANIQUE' => ['SCHNEIDERELECTRIC', 'SCHNEIDER', 'TELEMECANIQUESENSORS'],
            'TELEMECANIQUESENSORS' => ['SCHNEIDERELECTRIC', 'SCHNEIDER', 'TELEMECANIQUE'],
            'SCHNEIDER' => ['TELEMECANIQUE', 'SCHNEIDERELECTRIC'],
            'SCHNEIDERELECTRIC' => ['TELEMECANIQUE', 'SCHNEIDER'],
        ];
        foreach ($equiv as $k => $vs) {
            if (strpos($bn, $k) !== false) { $tokens = array_merge($tokens, $vs); }
        }
        foreach (array_unique($tokens) as $t) {
            if ($t !== '' && (strpos($mn, $t) !== false || strpos($t, $mn) !== false)) { return true; }
        }
        return false;
    }

    private function normalize(string $v): string
    {
        return preg_replace('/[^a-z0-9]/', '', mb_strtolower($v));
    }

    private function result(string $status, ?string $url = null, ?int $confidence = null, ?string $mpn = null, ?string $mfr = null, ?string $note = null): array
    {
        return ['status' => $status, 'image_url' => $url, 'confidence' => $confidence, 'matched_mpn' => $mpn, 'manufacturer' => $mfr, 'note' => $note];
    }
}
