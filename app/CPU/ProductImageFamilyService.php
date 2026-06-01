<?php

namespace App\CPU;

/**
 * Generates a stable "family key" for a product so that many part numbers which share the SAME
 * visual catalog image can reuse a single downloaded image (one API call per family, not per part).
 *
 * Design (per requirement "use tight patterns, do not over-group"):
 *  - We only GROUP part numbers when they belong to a curated, known-tight family whose members
 *    genuinely share one catalog photo (see FAMILIES). Those keys are reusable (curated = true).
 *  - Anything else gets a CONSERVATIVE key that is unique per part number (curated = false), so it
 *    is never reused across products — each fetches its own exact image. No accidental wrong reuse.
 *
 * IMPORTANT: reused images are always visible (flagged + in the gallery). If you spot a family whose
 * members actually differ, remove its line from FAMILIES — that's the only knob. Add new tight
 * families the same way, ideally after confirming in the gallery that the members share an image.
 */
class ProductImageFamilyService
{
    /**
     * Curated TIGHT families. Key = regex against the NORMALISED (UPPERCASE, alphanumeric-only)
     * part number; value = the family stem. Members of each genuinely share one catalog image.
     * Verify in the gallery before trusting a new entry.
     */
    const FAMILIES = [
        // From the spec's examples (validated):
        '/^LC1D\d+/'    => 'LC1D',     // TeSys D contactors (D09–D38 share the same body)
        '/^XB4BA\w*/'   => 'XB4BA',    // Harmony XB4 22mm metal flush pushbuttons
        '/^RXM2AB2\w*/' => 'RXM2AB2',  // Zelio RXM2 miniature plug-in relays

        // Common, generally-tight Harmony families — verify in the gallery, remove if members differ:
        '/^XB4BD\w*/'   => 'XB4BD',    // XB4 selector switches
        '/^XB5AA\w*/'   => 'XB5AA',    // Harmony XB5 22mm plastic pushbuttons
        '/^ZB4BZ\d+/'   => 'ZB4BZ',    // XB4 contact-block bodies
        '/^ZB5AZ\d+/'   => 'ZB5AZ',    // XB5 contact-block bodies
    ];

    /** Brand name fragments that all resolve to the single brand key "SCHNEIDER". */
    private const SCHNEIDER = ['TELEMECANIQUE', 'TELEMECANIQUESENSORS', 'SCHNEIDER', 'SCHNEIDERELECTRIC'];

    /**
     * @return array{key:string, curated:bool, confidence:int, needs_review:bool}
     *   - key          stable family key, e.g. "SCHNEIDER_LC1D" (curated) or "SCHNEIDER_LC1D09BD" (unique)
     *   - curated      true only for a known tight family => the image may be reused across products
     *   - confidence   rough 0-100 hint stored on reuse
     *   - needs_review advisory; the final review flag is decided by the fetch job from the outcome
     */
    public function familyKey(?string $brand, ?string $mpn, ?string $name = null): array
    {
        $brandKey = $this->brandKey($brand);
        $code = $this->normalize($mpn);

        if ($code === '') {
            // No part number — we can't form a safe family; key off the name and force review.
            $stem = $this->normalize($name);
            $hash = $stem !== '' ? substr(md5($stem), 0, 10) : substr(md5(uniqid('', true)), 0, 10);
            return ['key' => $brandKey . '_NAME_' . $hash, 'curated' => false, 'confidence' => 0, 'needs_review' => true];
        }

        foreach (self::FAMILIES as $regex => $stem) {
            if (preg_match($regex, $code)) {
                return ['key' => $brandKey . '_' . $stem, 'curated' => true, 'confidence' => 85, 'needs_review' => false];
            }
        }

        // Conservative: unique per part number => no cross-product reuse, each fetches its exact image.
        return ['key' => $brandKey . '_' . $code, 'curated' => false, 'confidence' => 40, 'needs_review' => false];
    }

    /** UPPERCASE, alphanumeric-only (drops spaces, hyphens, slashes, dots). */
    public function normalize(?string $value): string
    {
        return preg_replace('/[^A-Z0-9]/', '', strtoupper((string) $value));
    }

    /** Collapse Telemecanique/Schneider variants to a single brand key; else the normalised brand. */
    private function brandKey(?string $brand): string
    {
        $b = $this->normalize($brand);
        if ($b === '') {
            return 'UNKNOWN';
        }
        foreach (self::SCHNEIDER as $s) {
            if (strpos($b, $s) !== false) {
                return 'SCHNEIDER';
            }
        }
        return $b;
    }
}
