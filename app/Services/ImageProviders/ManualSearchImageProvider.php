<?php

namespace App\Services\ImageProviders;

/**
 * Stub for a future manual/assisted image source (e.g. a human-curated lookup or an approved
 * supplier search). Disabled for now so it never auto-assigns anything. Kept so the provider chain
 * is easy to extend without touching the job.
 */
class ManualSearchImageProvider implements ProductImageProviderInterface
{
    public function name(): string
    {
        return 'manual';
    }

    public function isAvailable(): bool
    {
        return false; // not active yet — no automatic manual search
    }

    public function findImage(string $partNumber, ?string $brand): array
    {
        return ['status' => 'skipped', 'image_url' => null, 'confidence' => null, 'matched_mpn' => null, 'manufacturer' => null, 'note' => 'manual provider disabled'];
    }
}
