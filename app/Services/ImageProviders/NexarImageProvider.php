<?php

namespace App\Services\ImageProviders;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * Nexar (Octopart) image provider. Looks a part up by MPN via the Nexar Supply GraphQL API and
 * returns the catalog photo only on an EXACT MPN match whose manufacturer matches the product's
 * brand (or a known equivalent). Octopart aggregates many distributors, so it often has images for
 * parts DigiKey reports as "details_no_image", and its image CDN allows normal downloads.
 *
 * Requires NEXAR_CLIENT_ID / NEXAR_CLIENT_SECRET (free Nexar app, Supply API). If unset, the provider
 * reports itself unavailable and is silently skipped by the job chain.
 */
class NexarImageProvider implements ProductImageProviderInterface
{
    private $token = false;

    public function name(): string
    {
        return 'nexar';
    }

    public function isAvailable(): bool
    {
        return (string) config('services.nexar.client_id') !== ''
            && (string) config('services.nexar.client_secret') !== '';
    }

    public function findImage(string $partNumber, ?string $brand): array
    {
        if (!$this->isAvailable()) {
            return $this->result('skipped');
        }
        $token = $this->accessToken();
        if (!$token) {
            return $this->result('error', null, null, null, null, 'nexar auth failed');
        }

        $gql = 'query($q:String!){ supSearchMpn(q:$q, limit:5){ results{ part{ mpn manufacturer{ name } bestImage{ url } } } } }';
        try {
            $resp = Http::withToken($token)->timeout(25)->acceptJson()
                ->post('https://api.nexar.com/graphql', ['query' => $gql, 'variables' => ['q' => $partNumber]]);
        } catch (\Throwable $e) {
            return $this->result('error', null, null, null, null, $e->getMessage());
        }

        if (in_array($resp->status(), [429, 403], true)) {
            return $this->result('quota'); // usage limit reached — let the job retry later
        }
        if ($resp->status() === 401) {
            Cache::forget('nexar_access_token'); // token went stale
            return $this->result('error', null, null, null, null, 'nexar 401');
        }
        if (!$resp->ok()) {
            return $this->result('error', null, null, null, null, 'HTTP ' . $resp->status());
        }
        if (!empty($resp->json('errors'))) {
            $msg = $resp->json('errors.0.message') ?? 'graphql error';
            // Nexar surfaces usage-limit problems as errors; treat those as quota, not no-image.
            $status = stripos($msg, 'limit') !== false || stripos($msg, 'quota') !== false ? 'quota' : 'error';
            return $this->result($status, null, null, null, null, $msg);
        }

        $cands = [];
        foreach (($resp->json('data.supSearchMpn.results') ?? []) as $res) {
            $part = $res['part'] ?? [];
            $cands[] = [
                'mpn'          => $part['mpn'] ?? '',
                'manufacturer' => $part['manufacturer']['name'] ?? '',
                'image'        => $part['bestImage']['url'] ?? '',
            ];
        }
        return $this->pickTrusted($cands, $partNumber, $brand);
    }

    private function accessToken(): ?string
    {
        if (is_string($this->token) && $this->token !== '') {
            return $this->token;
        }
        $id = (string) config('services.nexar.client_id');
        $secret = (string) config('services.nexar.client_secret');
        $this->token = Cache::remember('nexar_access_token', 3000, function () use ($id, $secret) {
            try {
                $resp = Http::asForm()->timeout(20)->post('https://identity.nexar.com/connect/token', [
                    'grant_type' => 'client_credentials', 'client_id' => $id, 'client_secret' => $secret,
                ]);
                return $resp->ok() ? ($resp->json('access_token') ?: null) : null;
            } catch (\Throwable $e) {
                return null;
            }
        });
        return $this->token;
    }

    private function pickTrusted(array $cands, string $code, ?string $brand): array
    {
        $want = $this->normalize($code);
        $exactNoImage = false;
        foreach ($cands as $c) {
            if ($this->normalize($c['mpn'] ?? '') !== $want) { continue; }
            $img = trim((string) ($c['image'] ?? ''));
            if ($img === '') { $exactNoImage = true; continue; }
            if ($this->brandMatches($brand, (string) ($c['manufacturer'] ?? ''))) {
                return $this->result('image', $img, 80, $c['mpn'] ?? null, $c['manufacturer'] ?? null);
            }
            $exactNoImage = true;
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
