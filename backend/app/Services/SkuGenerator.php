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
    /** Hard cap on the number of words in a generated code. */
    private const MAX_SEGMENTS = 3;

    /** How many random alphanumeric characters to append as the filler segment. */
    private const SUFFIX_LENGTH = 6;

    /**
     * Collision-free code for the given product name, capped at 3 words.
     */
    public static function next(string $name): string
    {
        $segments = HardwareTerms::skuSegments($name);

        // A name that already fills all three words needs no random filler.
        if (count($segments) >= self::MAX_SEGMENTS) {
            $prefix = implode('-', array_slice($segments, 0, self::MAX_SEGMENTS));

            return self::uniquify($prefix);
        }

        $prefix = implode('-', $segments);
        do {
            $candidate = $prefix . '-' . self::randomSuffix(self::SUFFIX_LENGTH);
        } while (Product::where('sku', $candidate)->exists());

        return $candidate;
    }

    /**
     * Ensure a no-suffix code is unique; if it is already taken, append a random
     * segment so we never return a duplicate.
     */
    private static function uniquify(string $code): string
    {
        if (! Product::where('sku', $code)->exists()) {
            return $code;
        }

        do {
            $candidate = $code . '-' . self::randomSuffix(self::SUFFIX_LENGTH);
        } while (Product::where('sku', $candidate)->exists());

        return $candidate;
    }

    /**
     * Cryptographically-random uppercase alphanumeric string (no ambiguous
     * characters like 0/O or 1/I so codes are easy to read over the counter).
     */
    private static function randomSuffix(int $length): string
    {
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        $max = strlen($alphabet) - 1;
        $out = '';

        for ($i = 0; $i < $length; $i++) {
            $out .= $alphabet[random_int(0, $max)];
        }

        return $out;
    }
}
