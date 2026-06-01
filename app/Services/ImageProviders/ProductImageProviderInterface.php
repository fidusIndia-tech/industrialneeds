<?php

namespace App\Services\ImageProviders;

/**
 * A source of product images, looked up by manufacturer part number. Implementations must never
 * throw on network/quota issues — they report status instead, so the fetch job can decide safely.
 */
interface ProductImageProviderInterface
{
    /** Short machine name, e.g. "digikey" (stored as image_source). */
    public function name(): string;

    /** True only when the provider is configured (credentials present) and usable. */
    public function isAvailable(): bool;

    /**
     * Find a TRUSTED image URL for $partNumber (exact MPN + brand match). Never throws.
     *
     * @return array{
     *   status: string,        // image | details_no_image | no_match | quota | skipped | error
     *   image_url: ?string,    // set only when status = image
     *   confidence: ?int,      // 0-100, set when status = image
     *   matched_mpn: ?string,
     *   manufacturer: ?string,
     *   note: ?string
     * }
     */
    public function findImage(string $partNumber, ?string $brand): array;
}
