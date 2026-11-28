<?php

namespace App\Services;

use App\Models\Product;

/**
 * Product code (SKU) generator.
 *
 * Builds a clean item code of at most THREE words/segments. The words come from
 * the English form of the product name (one 3-char segment per word); when the
 * name yields fewer than 3 segments, a random alphanumeric segment is appended
 * to fill up to the three-word limit. A name that already produces 3 words gets
 * NO random suffix.
 *
 * Examples:
 *   "Cordless Drill 18V" -> "COR-DRI-18V"      (3 words, no random)
 *   "Wood Screw"         -> "WOO-SCR-CDJ8NX"   (2 words + random)
 *   "Pako"               -> "NAI-4WEVVJ"       (1 word + random)
 *
 * The prefix uses the same English translation as HardwareTerms::skuPrefix, so a
 * Filipino entry still yields a readable English code ("Pako" -> "NAI-...").
 */
class SkuGenerator
{
    /** Max number of 3-letter English word segments from the product name. */
    private const MAX_NAME_SEGMENTS = 2;

    /**
     * Build a clean, collision-free SKU:
     *   - 1 word + spec:  "nail" + "1in"               -> "NAI-1in"
     *   - 2 words + spec: "Paint Brush" + "1in"        -> "PAI-BRU-1in"
     *   - 3+ words + spec: "Latex Paint White" + "4L"  -> "LAT-PAI-4L"
     *   - No spec yet:    "Paint Brush"                -> "PAI-BRU"
     */
    public static function next(string $name, ?string $spec = null): string
    {
        $baseName = trim($name);
        $specToken = self::normalizeSpec($spec);

        // If no explicit spec was passed, extract a trailing size+unit token
        // (e.g. "Latex Paint White 4L" -> base "Latex Paint White", spec "4L").
        if ($specToken === '' && preg_match('/\s+(\d+(?:\/\d+)?)\s*(oz|in|mm|cm|m|ft|kg|g|L|mL|gal|V|W|AWG)\s*$/i', $baseName, $m)) {
            $specToken = self::normalizeSpec($m[1] . $m[2]);
            $baseName = trim(substr($baseName, 0, -strlen($m[0])));
        }

        // Convert Filipino/English name to English 3-letter segments, capped at 2 words.
        $wordSegments = array_slice(HardwareTerms::skuSegments($baseName), 0, self::MAX_NAME_SEGMENTS);
        $prefix = implode('-', $wordSegments);

        $code = $specToken !== ''
            ? ($prefix !== '' ? $prefix . '-' . $specToken : $specToken)
            : ($prefix !== '' ? $prefix : 'ITM');

        return self::uniquify($code);
    }

    /**
     * Normalize a size+unit spec token (e.g. "1 in" -> "1in", "4 l" -> "4L").
     */
    private static function normalizeSpec(?string $spec): string
    {
        $clean = preg_replace('/\s+/', '', trim((string) $spec));
        if ($clean === '') {
            return '';
        }

        // Preserve canonical unit casing for common hardware units.
        $unitMap = [
            'oz' => 'oz',
            'in' => 'in',
            'mm' => 'mm',
            'cm' => 'cm',
            'm' => 'm',
            'ft' => 'ft',
            'kg' => 'kg',
            'g' => 'g',
            'l' => 'L',
            'ml' => 'mL',
            'gal' => 'gal',
            'v' => 'V',
            'w' => 'W',
            'awg' => 'AWG',
            'pc' => 'pc',
            'bx' => 'bx',
            'sck' => 'sck',
        ];

        if (preg_match('/^([0-9]+(?:\/[0-9]+)?)([a-zA-Z]+)$/', $clean, $m)) {
            $unitKey = strtolower($m[2]);
            $canonical = $unitMap[$unitKey] ?? $m[2];

            return $m[1] . $canonical;
        }

        return $clean;
    }

    /**
     * Ensure the generated code is unique; if already taken, append -2, -3, ...
     */
    private static function uniquify(string $code): string
    {
        if (! Product::where('sku', $code)->exists()) {
            return $code;
        }

        $suffix = 2;
        while (Product::where('sku', $code . '-' . $suffix)->exists()) {
            $suffix++;
        }

        return $code . '-' . $suffix;
    }
}
