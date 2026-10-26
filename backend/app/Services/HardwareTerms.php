<?php

namespace App\Services;

/**
 * Filipino/Tagalog → English hardware vocabulary.
 *
 * Store staff often type product names in Filipino ("pako", "martilyo",
 * "turnilyo"). The product NAME is always saved exactly as typed — only the
 * values the system derives from it (auto-generated SKU prefix, suggested
 * category) are built from the English meaning, so codes and categories stay
 * consistent with the rest of the catalogue.
 *
 * The vocabulary itself lives in config/hardware-terms.php so the same list
 * feeds the server (generateSku) and the browser (live suggestions).
 */
class HardwareTerms
{
    /** @var array<string,string>|null */
    protected static ?array $terms = null;

    /** @var array<string,string>|null */
    protected static ?array $keywords = null;

    /** @var array<string,string>|null */
    protected static ?array $aliases = null;

    /** @return array<string,string> Filipino phrase => English replacement. */
    public static function terms(): array
    {
        return static::$terms ??= static::load()['translations'] ?? [];
    }

    /** @return array<string,string> English keyword => category. */
    public static function keywords(): array
    {
        return static::$keywords ??= static::load()['categories'] ?? [];
    }

    /** @return array<string,string> Category spelling => canonical category. */
    public static function aliases(): array
    {
        return static::$aliases ??= static::load()['category_aliases'] ?? [];
    }

    protected static function load(): array
    {
        $path = config_path('hardware-terms.php');

        if (! is_file($path)) {
            return [];
        }

        $config = require $path;

        return is_array($config) ? $config : [];
    }

    /** Lowercase, strip punctuation, collapse whitespace. */
    public static function normalize(string $text): string
    {
        $text = mb_strtolower($text, 'UTF-8');
        $text = preg_replace('/[^a-z0-9\s]+/u', ' ', $text) ?? '';

        return trim(preg_replace('/\s+/', ' ', $text) ?? '');
    }

    /**
     * Rewrites Filipino hardware words into English, leaving anything it does
     * not recognise untouched (brand names, sizes, model numbers).
     */
    public static function translate(string $name): string
    {
        $tokens = array_values(array_filter(explode(' ', static::normalize($name))));

        if (! $tokens) {
            return '';
        }

        // Multi-word phrases must win over their single words
        // ("pipe wrench" before "pipe"), so apply longest first.
        $phrases = static::terms();
        uksort($phrases, function (string $a, string $b) {
            $wordsA = substr_count($a, ' ') + 1;
            $wordsB = substr_count($b, ' ') + 1;

            return $wordsB <=> $wordsA ?: strlen($b) <=> strlen($a);
        });

        foreach ($phrases as $phrase => $english) {
            $words = explode(' ', $phrase);
            $length = count($words);
            $output = [];

            for ($i = 0; $i < count($tokens); $i++) {
                $slice = array_slice($tokens, $i, $length);

                if (count($slice) === $length && $slice === $words) {
                    if ($english !== '') {
                        $output[] = $english;
                    }
                    $i += $length - 1;
                } else {
                    $output[] = $tokens[$i];
                }
            }

            $tokens = $output;
        }

        return trim(implode(' ', $tokens));
    }

    /**
     * Short uppercase prefix for an auto-generated SKU, taken from the English
     * form of the name: "Pako" -> "NAI", "Martilyo" -> "HAM".
     */
    public static function skuPrefix(string $name): string
    {
        $source = static::translate($name) ?: $name;
        $letters = strtoupper((string) preg_replace('/[^A-Za-z0-9]/', '', $source));

        return substr($letters, 0, 3) ?: 'NEW';
    }

    /**
     * Recommended category for a product name, matched against the English
     * form. Longest keyword first so "screwdriver" beats "screw".
     *
     * @param  array<int, object|array<string,mixed>>  $products  Existing catalogue rows
     */
    public static function categoryFor(string $name, array $products = []): string
    {
        $english = static::translate($name);

        if ($english === '') {
            return 'Uncategorized';
        }

        $keywords = static::keywords();
        uksort($keywords, fn (string $a, string $b) => strlen($b) <=> strlen($a));

        foreach ($keywords as $keyword => $category) {
            if (preg_match('/(^|\s)' . preg_quote($keyword, '/') . '(\s|$)/', $english, $match)) {
                return $category;
            }
        }

        // Fall back to a category an existing catalogue product with a shared
        // English name-word already uses.
        $words = array_values(array_filter(
            explode(' ', $english),
            fn (string $word) => strlen($word) >= 3
        ));

        foreach ($products as $product) {
            $data = is_array($product) ? $product : (array) $product;
            $category = static::canonicalCategory((string) ($data['category'] ?? ''));

            if ($category === '' || strcasecmp($category, 'Uncategorized') === 0) {
                continue;
            }

            $productWords = array_filter(
                explode(' ', static::translate((string) ($data['name'] ?? ''))),
                fn (string $word) => strlen($word) >= 3
            );

            if (array_intersect($words, $productWords)) {
                return $category;
            }
        }

        return 'Uncategorized';
    }

    /** Maps a legacy category spelling onto its canonical name ("Nail" -> "Fasteners"). */
    public static function canonicalCategory(string $category): string
    {
        $key = static::normalize($category);

        if ($key === '') {
            return '';
        }

        return static::aliases()[$key] ?? trim($category);
    }

    /** Payload for API consumers that need the full term dictionary. */
    public static function toViewData(): array
    {
        return [
            'terms' => static::terms(),
            'keywords' => static::keywords(),
            'aliases' => static::aliases(),
        ];
    }
}
