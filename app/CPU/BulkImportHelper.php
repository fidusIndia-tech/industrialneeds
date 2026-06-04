<?php

namespace App\CPU;

use App\Model\Brand;
use App\Model\Category;
use Illuminate\Support\Str;

/**
 * Helper logic for the admin product bulk importer (Phase 1).
 *
 * Responsibilities:
 *  - Map loose Excel/supplier headers to the canonical column names the importer understands.
 *  - Normalise free-text names so casing / extra spaces never create duplicate categories or brands.
 *  - Produce clean display names that preserve known technical acronyms (MCB, PLC, ...).
 *  - Find-or-create brands and the 3-level category path, caching lookups for one import run.
 *  - Compute purchase / selling price from supplier inputs with safe defaults.
 *
 * IMPORTANT: this helper never touches images and never reaches out to the network. Image handling
 * stays in ProductController (URL download today, ZIP support in Phase 2).
 */
class BulkImportHelper
{
    /**
     * Technical acronyms that must stay upper-cased in generated display names.
     * Compared case-insensitively against each whitespace-separated word.
     */
    const ACRONYMS = [
        'MCB', 'MCCB', 'RCCB', 'RCBO', 'PLC', 'HMI', 'VFD', 'RFID', 'LED',
        'AC', 'DC', 'I/O', 'USB', 'CPU', 'SMPS', 'M8', 'M12', 'IP', 'DIN', 'PCB',
    ];

    /**
     * Header alias map: canonical column => list of accepted header spellings.
     * Header keys from the file are lower-cased, trimmed and space-collapsed before matching.
     *
     * Category level mapping (per project decision):
     *   L1 (main)     <= category_id | category_name | "Category"
     *   L2 (sub)      <= sub_category_id | sub_category_name | "Subcategory"
     *   L3 (sub-sub)  <= sub_sub_category_id | sub_sub_category_name | "Sub-subcategory"
     * "eCommerce Category" and "Product Specific Category" are intentionally ignored
     * (the products table has no field for the latter, and the former is not part of the tree).
     */
    const HEADER_ALIASES = [
        'name'                  => ['name', 'product name', 'product_name'],
        'product_code'          => ['product_code', 'product code', 'part#', 'part #', 'part no', 'part number', 'part_number', 'sku', 'code'],

        'brand_id'              => ['brand_id'],
        'brand_name'            => ['brand_name', 'brand'],

        'category_id'           => ['category_id'],
        'sub_category_id'       => ['sub_category_id'],
        'sub_sub_category_id'   => ['sub_sub_category_id'],

        'category_name'         => ['category_name', 'category'],
        'sub_category_name'     => ['sub_category_name', 'subcategory', 'sub-category', 'sub category'],
        'sub_sub_category_name' => ['sub_sub_category_name', 'sub-subcategory', 'sub sub category', 'sub-sub-category', 'sub subcategory'],

        // Used only to auto-generate a product name when no name column exists; NOT a category level.
        'product_specific_category' => ['product_specific_category', 'product specific category'],

        'unit'                  => ['unit', 'uom'],
        'min_qty'               => ['min_qty'],
        'moq'                   => ['moq', 'minimum_order_quantity', 'minimum order quantity'],
        'refundable'            => ['refundable'],

        // Selling price. Blank in any of these spellings => the product is imported price-less
        // ("Price on Request"); it is NOT coerced to 0. First non-empty alias wins.
        'unit_price'            => ['unit_price', 'price', 'selling_price', 'selling price', 'sale_price', 'sale price', 'mrp'],
        'purchase_price'        => ['purchase_price'],
        'supplier_price'        => ['supplier_price', 'supplier price', 'price [eur]', 'price[eur]', 'price_eur', 'price eur', 'purchase_price_source'],
        'supplier_currency'     => ['supplier_currency'],
        'exchange_rate'         => ['exchange_rate'],
        'landed_cost_percent'   => ['landed_cost_percent'],
        'margin_percent'        => ['margin_percent'],

        'tax'                   => ['tax'],
        'discount'              => ['discount'],
        'discount_type'         => ['discount_type'],
        'current_stock'         => ['current_stock'],
        'details'               => ['details'],
        'details_html'          => ['details_html'],
        'youtube_video_url'     => ['youtube_video_url'],

        'thumbnail_url'         => ['thumbnail_url'],
        'gallery_urls'          => ['gallery_urls'],
        'thumbnail_file'        => ['thumbnail_file'],
        'gallery_files'         => ['gallery_files'],
    ];

    /** Headers we explicitly recognise but ignore on purpose (no row-skip, no use). */
    const IGNORED_HEADERS = ['ecommerce category', 'e-commerce category'];

    /** Row fields considered when deciding whether a row is "effectively empty" (formatting-only). */
    const ROW_CONTENT_KEYS = [
        'name', 'product_code', 'supplier_price', 'purchase_price', 'unit_price',
        'brand_id', 'brand_name', 'category_id', 'category_name',
        'sub_category_id', 'sub_category_name', 'sub_sub_category_id', 'sub_sub_category_name',
        'product_specific_category',
    ];

    /**
     * Normalise a header label for matching: trim, collapse internal whitespace, lower-case.
     */
    public static function normalizeHeader($header): string
    {
        return mb_strtolower(self::cleanSpaces($header));
    }

    /**
     * Trim and collapse any run of whitespace down to a single space.
     */
    public static function cleanSpaces($value): string
    {
        return trim(preg_replace('/\s+/u', ' ', (string)$value));
    }

    /**
     * Normalisation key used for duplicate detection / matching.
     * "Electrical", " electrical ", "ELECTRICAL", " industrial   automation "
     * all collapse to the same comparable key.
     */
    public static function normalizeKey($value): string
    {
        return mb_strtolower(self::cleanSpaces($value));
    }

    /**
     * Clean display name: collapse spaces, title-case normal words, keep known acronyms upper-cased.
     *   "electrical"           => "Electrical"
     *   "industrial automation" => "Industrial Automation"
     *   "plc automation"        => "PLC Automation"
     *   "mccb accessories"      => "MCCB Accessories"
     */
    public static function displayName($value): string
    {
        $value = self::cleanSpaces($value);
        if ($value === '') {
            return '';
        }

        $acronyms = [];
        foreach (self::ACRONYMS as $acr) {
            $acronyms[mb_strtolower($acr)] = $acr;
        }

        $words = explode(' ', $value);
        foreach ($words as $i => $word) {
            $lower = mb_strtolower($word);
            $words[$i] = $acronyms[$lower] ?? (mb_strtoupper(mb_substr($lower, 0, 1)) . mb_substr($lower, 1));
        }

        return implode(' ', $words);
    }

    /**
     * Auto-generate a product name when the sheet has no name column.
     * Joins the available parts in order: brand, product code, product-specific category.
     *   Eaton + PXM-1600 + Panel Meter      => "Eaton PXM-1600 Panel Meter"
     *   (no brand) + XCKM115 + Limit Switch  => "XCKM115 Limit Switch"
     *   Eaton + PXM-1600 + (no category)     => "Eaton PXM-1600"
     *   (only code)                          => "PXM-1600"
     * Product codes are kept verbatim (case-sensitive); brand/category are space-cleaned only.
     */
    public static function generateProductName($brand, $productCode, $specificCategory): string
    {
        $parts = [];
        foreach ([$brand, $productCode, $specificCategory] as $part) {
            $part = self::cleanSpaces($part);
            if ($part !== '') {
                $parts[] = $part;
            }
        }
        return implode(' ', $parts);
    }

    /**
     * Split a messy imported product title into a clean part-number title + a descriptive block.
     *
     * The product NAME should hold ONLY the manufacturer part/model number; everything else from the
     * original text (brand, series, product type, the full original title) is preserved as description
     * so nothing is lost. This runs on whatever ended up as the product name (a real "name" column OR
     * an auto-generated one).
     *
     * Input : "Telemecanique 9007TUB12M11 NEMA Heavy-Duty Limit Switches & Spare Parts - 9007"
     *         brand "Telemecanique", code "9007TUB12M11"
     * Output: product_name = "9007TUB12M11"
     *         description  = "Brand: Telemecanique\nSeries: 9007\nProduct Type: NEMA Heavy-Duty Limit
     *                         Switches & Spare Parts\nOriginal imported title: <full original>"
     *
     * Rules:
     *  - A part number is an alphanumeric token containing BOTH letters and digits (e.g. 9007TUB12M11).
     *  - A provided $productCode wins, but only if it itself looks like a part number (mixed letters+digits)
     *    — so a generic numeric code like "9007" is ignored in favour of the more specific token.
     *  - Pure-number tokens (e.g. a trailing "- 9007" series) are never used as the title.
     *  - The brand is stripped first and is never used as the title.
     *  - (7) If the title is already a single clean code, OR (8) no confident part number is found,
     *    the original title is returned unchanged with an empty description (nothing is forced/broken).
     *
     * @return array{product_name:string, description:string, changed:bool}
     */
    public static function extractProductTitleAndDescription(?string $rawProductName, ?string $brandName = null, ?string $productCode = null): array
    {
        $raw = self::cleanSpaces($rawProductName);
        // 'type' and 'series' are the parsed descriptive parts, reused to build SEO meta fields.
        $out = ['product_name' => $raw, 'description' => '', 'changed' => false, 'type' => null, 'series' => null];
        if ($raw === '') {
            return $out;
        }

        // (7) Already a single clean code token (letters+digits, no spaces)? Keep unchanged.
        if (strpos($raw, ' ') === false && self::looksLikePartNumber($raw)) {
            return $out;
        }

        // Strip a leading brand prefix so the brand can never be chosen as the title.
        $brand = self::cleanSpaces($brandName);
        $working = $raw;
        if ($brand !== '' && stripos($working, $brand . ' ') === 0) {
            $working = self::cleanSpaces(substr($working, strlen($brand)));
        }

        $tokens = preg_split('/\s+/u', $working) ?: [];
        $trimChars = " \t\n\r\0\x0B.,;:()[]";

        // Choose the part number.
        $part = null;
        $code = self::cleanSpaces($productCode);

        // 1) Prefer the supplied product_code — it is the authoritative SKU, so accept it even when
        //    it is pure-letters (e.g. XCSRZE) or pure-digits (e.g. 90070000). The ONLY exception is
        //    when a longer, more specific code token in the text contains it (e.g. code "9007" while
        //    the title also has "9007TUB12M11") — then we defer to detection so the generic code does
        //    not win over the specific one.
        if ($code !== '') {
            $codeLower = mb_strtolower($code);
            $matchedToken = null;
            $hasMoreSpecific = false;
            foreach ($tokens as $t) {
                $clean = trim($t, $trimChars);
                $cl = mb_strtolower($clean);
                if ($cl === $codeLower) {
                    $matchedToken = $clean;
                } elseif ($cl !== '' && mb_strlen($cl) > mb_strlen($codeLower)
                    && mb_strpos($cl, $codeLower) !== false && self::looksLikePartNumber($clean)) {
                    $hasMoreSpecific = true; // a more specific code (superset) exists in the title
                }
            }
            if (!$hasMoreSpecific) {
                if ($matchedToken !== null) {
                    $part = $matchedToken;
                } elseif (stripos($raw, $code) !== false) {
                    $part = $code; // present but glued/odd-spaced — still trust the SKU
                }
            }
        }

        // 2) Otherwise pick the most specific alphanumeric token (longest wins → 9007TUB12M11 over short codes).
        if ($part === null) {
            $best = null;
            foreach ($tokens as $t) {
                $clean = trim($t, $trimChars);
                if (self::looksLikePartNumber($clean) && ($best === null || mb_strlen($clean) > mb_strlen($best))) {
                    $best = $clean;
                }
            }
            $part = $best;
        }

        // (8) No confident part number → keep the original title untouched.
        if ($part === null || $part === '') {
            return $out;
        }

        // If the detected part already IS the whole title, just normalise and return (no description).
        if (mb_strtolower($part) === mb_strtolower($raw)) {
            $out['product_name'] = $part;
            return $out;
        }

        // Build the descriptive block from the leftover text.
        $descLines = [];
        if ($brand !== '') {
            $descLines[] = 'Brand: ' . $brand;
        }

        // Series = a trailing standalone number group, often after a dash ("... - 9007").
        $series = null;
        if (preg_match('/[-\x{2013}]\s*([0-9]{2,})\s*$/u', $raw, $m)) {
            $series = $m[1];
            $descLines[] = 'Series: ' . $series;
        }

        // Product type = original text minus brand, minus the part number, minus the trailing series.
        $type = $raw;
        if ($brand !== '') {
            $type = preg_replace('/' . preg_quote($brand, '/') . '/i', ' ', $type, 1);
        }
        $type = preg_replace('/' . preg_quote($part, '/') . '/i', ' ', $type, 1);
        if ($series !== null) {
            $type = preg_replace('/[-\x{2013}]\s*' . preg_quote($series, '/') . '\s*$/u', ' ', $type);
        }
        $type = trim(self::cleanSpaces($type), " -\x{2013}|,;:");
        if ($type !== '') {
            $descLines[] = 'Product Type: ' . $type;
        }

        // Always keep the full original title so no information is lost.
        $descLines[] = 'Original imported title: ' . $raw;

        $out['product_name'] = $part;
        $out['description']  = implode("\n", $descLines);
        $out['changed']      = true;
        $out['type']         = $type !== '' ? $type : null;
        $out['series']       = $series;
        return $out;
    }

    /**
     * Build SEO meta_title + meta_description from the resolved part number and descriptive parts.
     * meta_title is part-number centric (brand-prefixed for searchability); meta_description is a
     * short factual sentence. Both are length-capped to fit the products table (varchar 191).
     *
     * @return array{meta_title:string, meta_description:string}
     */
    public static function buildSeoMeta(string $partNumber, ?string $brand = null, ?string $type = null, ?string $series = null): array
    {
        $partNumber = self::cleanSpaces($partNumber);
        $brand = self::cleanSpaces($brand);
        $type  = self::cleanSpaces($type);

        // meta_title: "Brand PartNumber" (or just the part number), capped ~60 chars.
        $title = trim(($brand !== '' ? $brand . ' ' : '') . $partNumber);
        $title = self::truncate($title !== '' ? $title : $partNumber, 60);

        // meta_description: "Brand PartNumber - Type. Series X." capped ~160 chars.
        $lead = trim(($brand !== '' ? $brand . ' ' : '') . $partNumber);
        $desc = $type !== '' ? ($lead . ' - ' . $type . '.') : ($lead . '.');
        if ($series !== null && $series !== '') {
            $desc .= ' Series ' . $series . '.';
        }
        $desc = self::truncate(self::cleanSpaces($desc), 160);

        return ['meta_title' => $title, 'meta_description' => $desc];
    }

    /** Truncate to $max characters with an ellipsis, on a word boundary where possible. */
    private static function truncate(string $value, int $max): string
    {
        if (mb_strlen($value) <= $max) {
            return $value;
        }
        $cut = mb_substr($value, 0, $max - 1);
        $space = mb_strrpos($cut, ' ');
        if ($space !== false && $space > $max * 0.6) {
            $cut = mb_substr($cut, 0, $space);
        }
        return rtrim($cut) . '…';
    }

    /**
     * A token "looks like a part number" when it mixes letters AND digits and is otherwise made of
     * code-ish characters (letters, digits, - _ / .). Pure numbers and pure words are rejected.
     */
    public static function looksLikePartNumber(string $token): bool
    {
        if (mb_strlen($token) < 3) {
            return false;
        }
        $hasLetter    = preg_match('/[A-Za-z]/', $token) === 1;
        $hasDigit     = preg_match('/[0-9]/', $token) === 1;
        $onlyCodeChar = preg_match('/^[A-Za-z0-9\-_\/.]+$/', $token) === 1;
        return $hasLetter && $hasDigit && $onlyCodeChar;
    }

    /**
     * Infer a brand name from a supplier file name. Supplier files are commonly named like
     * "INT_2025-09-16 Telemecanique.xlsx" — the brand is the trailing run of alphabetic words.
     *   "INT_2025-09-16 Telemecanique.xlsx" => "Telemecanique"
     *   "INT_2025-11-18 Eaton.xlsx"          => "Eaton"
     * Returns '' when no plausible trailing brand token is found.
     */
    public static function inferBrandFromFilename(?string $filename): string
    {
        if (!$filename) {
            return '';
        }
        $base = pathinfo($filename, PATHINFO_FILENAME); // drop extension
        $base = str_replace('_', ' ', $base);           // keep '-' so dates/hyphenated brands stay intact
        $tokens = preg_split('/\s+/u', trim($base)) ?: [];

        $collected = [];
        for ($i = count($tokens) - 1; $i >= 0; $i--) {
            $t = $tokens[$i];
            // a "word" token: starts with a letter, may contain letters & . - (e.g. Allen-Bradley, L&T)
            if (preg_match('/^[A-Za-z][A-Za-z&.\-]*$/u', $t)) {
                array_unshift($collected, $t);
            } else {
                break; // stop at the first non-word token (e.g. a date) scanning from the end
            }
        }
        return self::cleanSpaces(implode(' ', $collected));
    }

    /**
     * True when a mapped row carries no meaningful content (a blank / formatting-only row that
     * spreadsheets often pad files with). Such rows are skipped silently and not counted.
     */
    public static function isRowEmpty(array $row): bool
    {
        foreach (self::ROW_CONTENT_KEYS as $key) {
            if (isset($row[$key]) && trim((string)$row[$key]) !== '') {
                return false;
            }
        }
        return true;
    }

    /**
     * Map a raw FastExcel row (assoc array keyed by file headers) to canonical column names.
     * Unknown headers are dropped; recognised-but-ignored headers are silently skipped.
     * First matching alias wins so a real value is not clobbered by a later empty column.
     */
    public static function mapRow($collection): array
    {
        // Build reverse lookup once (alias => canonical).
        static $reverse = null;
        if ($reverse === null) {
            $reverse = [];
            foreach (self::HEADER_ALIASES as $canonical => $aliases) {
                foreach ($aliases as $alias) {
                    $reverse[$alias] = $canonical;
                }
            }
        }

        $mapped = [];
        foreach ($collection as $header => $value) {
            $key = self::normalizeHeader($header);
            if ($key === '' || in_array($key, self::IGNORED_HEADERS, true)) {
                continue;
            }
            if (!isset($reverse[$key])) {
                continue; // unknown column, ignore safely
            }
            $canonical = $reverse[$key];
            // keep the first non-empty value seen for a canonical key
            if (!array_key_exists($canonical, $mapped) || $mapped[$canonical] === '' || $mapped[$canonical] === null) {
                $mapped[$canonical] = is_string($value) ? trim($value) : $value;
            }
        }

        return $mapped;
    }

    /**
     * Generate a unique category slug in the project's style (Str::slug), appending -2, -3 ...
     * against both the database and any slugs already minted during this run.
     */
    public static function uniqueCategorySlug($name, array &$usedSlugs = []): string
    {
        $base = Str::slug($name);
        if ($base === '') {
            $base = 'category';
        }
        $slug = $base;
        $i = 1;
        while (isset($usedSlugs[$slug]) || Category::where('slug', $slug)->exists()) {
            $i++;
            $slug = $base . '-' . $i;
        }
        $usedSlugs[$slug] = true;
        return $slug;
    }

    /**
     * Build a normalised lookup map of existing categories for one import run.
     * Shape: $map[position][parent_id][normalizedName] = id
     */
    public static function buildCategoryMap(): array
    {
        $map = [];
        Category::query()->select('id', 'name', 'parent_id', 'position')
            ->orderBy('id')
            ->chunk(500, function ($rows) use (&$map) {
                foreach ($rows as $row) {
                    $map[(int)$row->position][(int)$row->parent_id][self::normalizeKey($row->name)] = (int)$row->id;
                }
            });
        return $map;
    }

    /**
     * Build a normalised lookup map of existing brands. Shape: $map[normalizedName] = id
     */
    public static function buildBrandMap(): array
    {
        $map = [];
        Brand::query()->select('id', 'name')->orderBy('id')
            ->chunk(500, function ($rows) use (&$map) {
                foreach ($rows as $row) {
                    $map[self::normalizeKey($row->name)] = (int)$row->id;
                }
            });
        return $map;
    }

    /**
     * Resolve a brand by name, creating it (live, status=1) if missing.
     * Returns the brand id, or null when $name is blank.
     *
     * @param array $brandMap  normalised name => id cache (modified in place)
     * @param int   $created   incremented when a new brand is inserted
     */
    public static function resolveBrandId($name, array &$brandMap, int &$created): ?int
    {
        $key = self::normalizeKey($name);
        if ($key === '') {
            return null;
        }
        if (isset($brandMap[$key])) {
            return $brandMap[$key];
        }

        $brand = new Brand();
        $brand->name = self::displayName($name);
        $brand->image = 'def.png'; // column is NOT NULL; reuse the project default placeholder
        $brand->status = 1;        // go live immediately (per project decision)
        $brand->save();

        $created++;
        $brandMap[$key] = (int)$brand->id;
        return (int)$brand->id;
    }

    /**
     * Find-or-create a single category at $position (0=main,1=sub,2=sub-sub) under $parentId.
     * Returns the category id, or null when $name is blank.
     *
     * @param array $categoryMap nested cache (modified in place)
     * @param int   $created     incremented when a new category is inserted
     * @param array $usedSlugs   slugs minted this run (modified in place)
     */
    public static function resolveCategoryId($name, int $position, int $parentId, array &$categoryMap, int &$created, array &$usedSlugs): ?int
    {
        $key = self::normalizeKey($name);
        if ($key === '') {
            return null;
        }
        if (isset($categoryMap[$position][$parentId][$key])) {
            return $categoryMap[$position][$parentId][$key];
        }

        $category = new Category();
        $category->name = self::displayName($name);
        $category->slug = self::uniqueCategorySlug($category->name, $usedSlugs);
        $category->parent_id = $parentId;
        $category->position = $position;
        $category->priority = 1;
        $category->icon = 'def.png'; // column is nullable but views expect a value
        $category->home_status = 0;
        $category->save();

        $created++;
        $categoryMap[$position][$parentId][$key] = (int)$category->id;
        return (int)$category->id;
    }

    /**
     * Resolve the full L1->L2->L3 path from any mix of explicit IDs and names.
     * Precedence per level: valid ID column wins, else the name column.
     *
     * @return array{
     *   category_ids: array,  // product.category_ids JSON structure (positions 1/2/3)
     *   ids: array,           // [l1, l2, l3] resolved category ids (nulls allowed for l2/l3)
     *   created: array        // [l1=>n, l2=>n, l3=>n] new-record counts for the summary
     * }
     */
    public static function resolveCategoryPath(array $input, array &$categoryMap, array &$validIds, array &$usedSlugs): array
    {
        $createdL1 = $createdL2 = $createdL3 = 0;

        // ---- L1 (main, table position 0, json position 1) ----
        $l1Id = self::pickExistingId($input['category_id'] ?? null, 0, $validIds);
        if (!$l1Id) {
            $l1Id = self::resolveCategoryId($input['category_name'] ?? '', 0, 0, $categoryMap, $createdL1, $usedSlugs);
        }
        if (!$l1Id) {
            return ['category_ids' => [], 'ids' => [null, null, null], 'created' => ['l1' => 0, 'l2' => 0, 'l3' => 0]];
        }

        $categoryIds = [['id' => (string)$l1Id, 'position' => 1]];
        $ids = [$l1Id, null, null];

        // ---- L2 (sub, table position 1, json position 2) ----
        $l2Id = self::pickExistingId($input['sub_category_id'] ?? null, 1, $validIds);
        if (!$l2Id) {
            $l2Id = self::resolveCategoryId($input['sub_category_name'] ?? '', 1, $l1Id, $categoryMap, $createdL2, $usedSlugs);
        }
        if ($l2Id) {
            $categoryIds[] = ['id' => (string)$l2Id, 'position' => 2];
            $ids[1] = $l2Id;

            // ---- L3 (sub-sub, table position 2, json position 3) ----
            // Only attach a sub-sub-category when its parent sub-category exists.
            $l3Id = self::pickExistingId($input['sub_sub_category_id'] ?? null, 2, $validIds);
            if (!$l3Id) {
                $l3Id = self::resolveCategoryId($input['sub_sub_category_name'] ?? '', 2, $l2Id, $categoryMap, $createdL3, $usedSlugs);
            }
            if ($l3Id) {
                $categoryIds[] = ['id' => (string)$l3Id, 'position' => 3];
                $ids[2] = $l3Id;
            }
        }

        return [
            'category_ids' => $categoryIds,
            'ids' => $ids,
            'created' => ['l1' => $createdL1, 'l2' => $createdL2, 'l3' => $createdL3],
        ];
    }

    /**
     * Return the integer id if $value is a positive integer present in $validIds[$position], else null.
     */
    private static function pickExistingId($value, int $position, array &$validIds): ?int
    {
        if ($value === null || $value === '' ) {
            return null;
        }
        if (!is_numeric($value)) {
            return null;
        }
        $id = (int)$value;
        if ($id <= 0) {
            return null;
        }
        return in_array($id, $validIds[$position] ?? [], true) ? $id : null;
    }

    /**
     * Compute purchase / selling price and resolve unit / MOQ / stock with layered defaults.
     * Row values override $defaults, which override hard-coded fallbacks.
     *
     * Returns the resolved values (in the store's display currency, NOT yet USD) plus flags
     * used by the import summary, and a possible 'error' string when the row must be skipped.
     *
     * @param array $row      canonical row values
     * @param array $defaults upload-form defaults (exchange_rate, landed_cost_percent, margin_percent,
     *                        unit, tax, stock, refundable, rounding, allow_below)
     */
    public static function computePricing(array $row, array $defaults): array
    {
        $flags = [
            'calc_purchase' => false,
            'calc_unit'     => false,
            'default_unit'  => false,
            'default_moq'   => false,
            'default_stock' => false,
        ];

        $numeric = function ($v) {
            return ($v === null || $v === '' || !is_numeric($v)) ? null : (float)$v;
        };

        // ---- price validation (blank is allowed, garbage text is not) ----
        // A blank price means the product is sold on enquiry (stored as NULL, never 0). Only a
        // cell that has content AND is not a number is a hard error — so "abc"/"call us" fails,
        // but an empty cell sails through and becomes a "Price on Request" product.
        foreach (['unit_price', 'purchase_price', 'supplier_price'] as $priceField) {
            $raw = $row[$priceField] ?? null;
            if ($raw !== null && trim((string)$raw) !== '' && !is_numeric(trim((string)$raw))) {
                return ['error' => "Invalid price in '{$priceField}': '" . trim((string)$raw) . "' is not a number."];
            }
        }

        // ---- purchase price ----
        // Left as NULL when the row carries no purchase/supplier price — it is NOT coerced to 0.
        $purchase = $numeric($row['purchase_price'] ?? null);
        if ($purchase === null) {
            $supplier = $numeric($row['supplier_price'] ?? null);
            if ($supplier !== null) {
                $rate = $numeric($row['exchange_rate'] ?? null) ?? $numeric($defaults['exchange_rate'] ?? null) ?? 1.0;
                $landed = $numeric($row['landed_cost_percent'] ?? null) ?? $numeric($defaults['landed_cost_percent'] ?? null) ?? 0.0;
                $purchase = $supplier * $rate * (1 + $landed / 100);
                $flags['calc_purchase'] = true;
            }
        }

        // ---- selling (unit) price ----
        // Computed from purchase + margin only when we actually have a purchase price. With no
        // purchase price and no explicit unit price, it stays NULL (an enquiry-only product).
        $unitPrice = $numeric($row['unit_price'] ?? null);
        if ($unitPrice === null && $purchase !== null) {
            $margin = $numeric($row['margin_percent'] ?? null) ?? $numeric($defaults['margin_percent'] ?? null) ?? 0.0;
            $unitPrice = $purchase * (1 + $margin / 100);
            $flags['calc_unit'] = true;
        }

        $rounding = $defaults['rounding'] ?? 'whole';
        if ($purchase !== null) {
            $purchase = self::roundPrice($purchase, $rounding);
        }
        if ($unitPrice !== null) {
            $unitPrice = self::roundPrice($unitPrice, $rounding);
        }

        // ---- unit / MOQ / stock ----
        $unit = self::firstNonEmpty([$row['unit'] ?? null, $defaults['unit'] ?? null]);
        if ($unit === null) {
            $unit = 'Pc';
            $flags['default_unit'] = true;
        } elseif (($row['unit'] ?? '') === '') {
            // value came from the form default, not the row
            $flags['default_unit'] = true;
        }

        $minQty = $numeric($row['min_qty'] ?? null);
        if ($minQty === null || $minQty < 1) {
            $moq = $numeric($row['moq'] ?? null);
            if ($moq !== null && $moq >= 1) {
                $minQty = $moq;
            } else {
                $minQty = 1;
                $flags['default_moq'] = true;
            }
        }
        $minQty = (int)$minQty;

        $stock = $numeric($row['current_stock'] ?? null);
        if ($stock === null) {
            $stock = $numeric($defaults['stock'] ?? null);
            if ($stock === null) {
                $stock = 0.0;
            }
            $flags['default_stock'] = true;
        }
        $stock = (int)$stock;

        // ---- tax / discount / refundable ----
        // Hard-coded fallback is 18 (typical GST) when neither the row nor the upload form set a tax.
        $tax = $numeric($row['tax'] ?? null) ?? $numeric($defaults['tax'] ?? null) ?? 18.0;
        $discount = $numeric($row['discount'] ?? null) ?? 0.0;
        $discountType = self::firstNonEmpty([$row['discount_type'] ?? null]) ?? 'percent';
        $refundable = $numeric($row['refundable'] ?? null);
        if ($refundable === null) {
            $refundable = $numeric($defaults['refundable'] ?? null) ?? 0.0;
        }
        $refundable = (int)$refundable;

        $result = [
            'purchase_price' => $purchase,
            'unit_price'     => $unitPrice,
            'unit'           => $unit,
            'min_qty'        => $minQty,
            'current_stock'  => $stock,
            'tax'            => $tax,
            'discount'       => $discount,
            'discount_type'  => $discountType,
            'refundable'     => $refundable,
            'flags'          => $flags,
            // Only meaningful when both prices are present; an enquiry product (null price) is never "below".
            'unit_below_purchase' => ($unitPrice !== null && $purchase !== null && $unitPrice < $purchase),
        ];

        $allowBelow = !empty($defaults['allow_below']);
        if ($result['unit_below_purchase'] && !$allowBelow) {
            $result['error'] = 'Selling price (' . $unitPrice . ') is lower than purchase price (' . $purchase . ').';
        }

        return $result;
    }

    /** Round a price per the chosen strategy. */
    public static function roundPrice(float $value, string $mode): float
    {
        switch ($mode) {
            case 'none':
                return round($value, 2);
            case '5':
                return (float)(ceil($value / 5) * 5);
            case '10':
                return (float)(ceil($value / 10) * 10);
            case 'whole':
            default:
                return (float)round($value);
        }
    }

    /** Return the first trimmed non-empty string from the list, else null. */
    private static function firstNonEmpty(array $values): ?string
    {
        foreach ($values as $v) {
            if ($v !== null && trim((string)$v) !== '') {
                return trim((string)$v);
            }
        }
        return null;
    }

    /**
     * Build an auto-generated details string from brand + part number + category path.
     * Example: "Brand: Eaton. Part Number: PXM-1600. Category: Electrical > Industrial Automation > Panel Meters."
     */
    public static function autoDetails(?string $brandName, ?string $productCode, array $categoryNames): string
    {
        $parts = [];
        if ($brandName) {
            $parts[] = 'Brand: ' . $brandName . '.';
        }
        if ($productCode) {
            $parts[] = 'Part Number: ' . $productCode . '.';
        }
        $categoryNames = array_values(array_filter($categoryNames, fn($n) => $n !== null && $n !== ''));
        if ($categoryNames) {
            $parts[] = 'Category: ' . implode(' > ', $categoryNames) . '.';
        }
        return implode(' ', $parts);
    }

    // ---------------------------------------------------------------------
    // Phase 2: image helpers (ZIP matching + URL download share this logic)
    // ---------------------------------------------------------------------

    /** Image extensions we accept (validated against the real MIME type, see validateImageBytes). */
    const ALLOWED_IMAGE_EXTENSIONS = ['jpg', 'jpeg', 'png', 'webp'];

    /** Real MIME types we accept => the canonical extension we store the file as. */
    const ALLOWED_IMAGE_MIMES = [
        'image/jpeg' => 'jpg',
        'image/pjpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
    ];

    /**
     * Reduce any user/ZIP-supplied name to a safe base filename (no directory, no traversal).
     * Output filenames are always system-generated, so this is only used for matching/logging.
     */
    public static function sanitizeFilename($name): string
    {
        $name = basename(str_replace('\\', '/', (string)$name));
        return preg_replace('/[^A-Za-z0-9._ -]/', '_', $name);
    }

    /** Lower-cased base filename used as a match key against ZIP entries. */
    public static function fileMatchKey($name): string
    {
        return mb_strtolower(trim(self::sanitizeFilename($name)));
    }

    /**
     * Validate raw image bytes by sniffing the real MIME type (not the extension).
     * Returns the canonical extension (jpg/png/webp) when safe, or null for anything
     * else (svg, html, php, js, exe, corrupt files, ...). Never throws.
     */
    public static function validateImageBytes(?string $bytes): ?string
    {
        if ($bytes === null || $bytes === '') {
            return null;
        }
        try {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            if ($finfo === false) {
                return null;
            }
            $mime = finfo_buffer($finfo, $bytes);
            finfo_close($finfo);
        } catch (\Throwable $e) {
            return null;
        }
        $mime = is_string($mime) ? strtolower($mime) : '';
        return self::ALLOWED_IMAGE_MIMES[$mime] ?? null;
    }
}
