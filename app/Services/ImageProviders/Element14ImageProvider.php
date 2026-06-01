<?php

namespace App\Services\ImageProviders;

use Illuminate\Support\Facades\Http;

/**
 * element14 / Farnell / Newark image provider. Uses the free Premier Farnell "Product Search" REST
 * API to look a part up by manufacturer part number and returns the catalog photo only on an EXACT
 * MPN match whose manufacturer matches the product's brand (or a known equivalent). Farnell carries
 * a large Schneider/Telemecanique catalogue and serves images from a plain, downloadable CDN — so it
 * is a good fallback for parts DigiKey reports as "details_no_image".
 *
 * Requires ELEMENT14_API_KEY (free key from partner.element14.com). Optional ELEMENT14_STORE
 * (default uk.farnell.com) and ELEMENT14_IMAGE_BASE (only if the derived image host 404s). If the
 * key is unset, the provider reports itself unavailable and the job chain skips it.
 */
class Element14ImageProvider implements ProductImageProviderInterface
{
    private const ENDPOINT = 'https://api.element14.com/catalog/products';

    public function name(): string
    {
        return 'element14';
    }

    public function isAvailable(): bool
    {
        return (string) config('services.element14.key') !== '';
    }

    public function findImage(string $partNumber, ?string $brand): array
    {
        if (!$this->isAvailable()) {
            return $this->result('skipped');
        }
        $store = (string) config('services.element14.store') ?: 'uk.farnell.com';

        try {
            $resp = Http::timeout(25)->acceptJson()->get(self::ENDPOINT, [
                'term'                            => 'manuPartNum:' . $partNumber,
                'storeInfo.id'                    => $store,
                'resultsSettings.offset'          => 0,
                'resultsSettings.numberOfResults' => 5,
                'resultsSettings.responseGroup'   => 'large', // includes image + manufacturer
                'callInfo.responseDataFormat'     => 'json',
                'callInfo.apiKey'                 => (string) config('services.element14.key'),
            ]);
        } catch (\Throwable $e) {
            return $this->result('error', null, null, null, null, $e->getMessage());
        }

        if (in_array($resp->status(), [429, 403], true)) {
            return $this->result('quota');
        }
        if ($resp->status() === 401) {
            return $this->result('error', null, null, null, null, 'element14 401 (bad API key?)');
        }
        if (!$resp->ok()) {
            return $this->result('error', null, null, null, null, 'HTTP ' . $resp->status());
        }

        $json = $resp->json();
        // The response root key varies with the search term; accept the known variants.
        $root = $json['manufacturerPartNumberSearchReturn']
            ?? $json['keywordSearchReturn']
            ?? $json['premierFarnellPartNumberReturn']
            ?? null;
        if (!$root || empty($root['products'])) {
            return $this->result('no_match');
        }

        // element14 images live at <store>/productimages/standard/en_GB/<baseName> (the API's
        // vrntPath is not the real path). Override via ELEMENT14_IMAGE_BASE for other stores/locales.
        $imageBase = rtrim((string) (config('services.element14.image_base') ?: ('https://' . $store . '/productimages/standard/en_GB')), '/');
        $cands = [];
        foreach ($root['products'] as $prod) {
            $img = '';
            if (!empty($prod['image']['baseName'])) {
                $img = $imageBase . '/' . ltrim((string) $prod['image']['baseName'], '/');
            }
            $cands[] = [
                'mpn'          => $prod['translatedManufacturerPartNumber'] ?? ($prod['manufacturerPartNumber'] ?? ''),
                'manufacturer' => $prod['vendorName'] ?? ($prod['brandName'] ?? ''),
                'image'        => $img,
            ];
        }
        return $this->pickTrusted($cands, $partNumber, $brand);
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
                return $this->result('image', $img, 85, $c['mpn'] ?? null, $c['manufacturer'] ?? null);
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
