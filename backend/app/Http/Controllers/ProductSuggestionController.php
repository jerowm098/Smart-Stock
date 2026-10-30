<?php

namespace App\Http\Controllers;

use App\Services\SkuGenerator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * AI-assisted product form suggestions.
 *
 * Uses Ollama's OpenAI-compatible API.
 * Two modes:
 * 1) Name-based (default): suggest Item Code (SKU) and Category for the Add Product form.
 *    Price, reorder threshold, unit of measure and starting stock remain manual.
 * 2) Name + Unit-based: when `unit` (or `receiving_unit`) is provided together with `name`,
 *    suggest a realistic retail PRICE per that unit for the Philippine hardware market.
 */
class ProductSuggestionController extends Controller
{
    public function suggest(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $user) {
            return response()->json(['message' => 'Authentication required.'], 401);
        }

        $validated = $request->validate([
            'name'            => ['required', 'string', 'max:255'],
            'sku'             => ['nullable', 'string', 'max:50'],
            'category'        => ['nullable', 'string', 'max:100'],
            'unit'            => ['nullable', 'string', 'max:30'],
            'receiving_unit'  => ['nullable', 'string', 'max:30'],
        ]);

        $apiKey = config('services.ollama.key');
        if (empty($apiKey)) {
            return response()->json([
                'message' => 'AI suggestions are not configured. Add OLLAMA_API_KEY to the backend .env.',
            ], 503);
        }

        $base   = rtrim(config('services.ollama.base_url'), '/');
        $models = config('services.ollama.models', []);
        if (empty($models)) {
            return response()->json(['message' => 'No AI models configured (OLLAMA_MODELS).'], 503);
        }

        $unit = $validated['unit'] ?? ($validated['receiving_unit'] ?? null);

        // If a unit of measure is provided with a product name, focus ONLY on price suggestion
        // for that (product + unit) combination.
        if (! empty($unit)) {
            $prompt = $this->buildPricePrompt([
                'name' => $validated['name'],
                'unit' => $unit,
            ]);

            foreach ($models as $model) {
                $suggestion = $this->tryPriceModel($base, $model, $apiKey, $prompt);
                if ($suggestion !== null) {
                    return response()->json([
                        'suggestion' => $suggestion,
                        'model_used' => $model,
                    ]);
                }
            }

            return response()->json([
                'message' => 'All AI models failed. Check OLLAMA_API_KEY and OLLAMA_MODELS, then try again.',
            ], 502);
        }

        $prompt = $this->buildPrompt($validated);

        // A deterministic item code is generated locally via SkuGenerator
        // (max 3 segments or with suffix) and always overrides any AI suggestion.
        $code = SkuGenerator::next($validated['name']);

        // Try each model in the fallback chain until one yields a usable result.
        foreach ($models as $model) {
            $suggestion = $this->tryModel($base, $model, $apiKey, $prompt);
            if ($suggestion !== null) {
                $suggestion['sku'] = $code;

                return response()->json([
                    'suggestion' => $suggestion,
                    'model_used' => $model,
                ]);
            }
        }

        return response()->json([
            'message' => 'All AI models failed. Check OLLAMA_API_KEY and OLLAMA_MODELS, then try again.',
        ], 502);
    }

    private function tryModel(string $base, string $model, string $apiKey, string $prompt): ?array
    {
        $body = [
            'model' => $model,
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'You are a JSON API that only returns valid JSON. Do not include explanations, markdown, or extra text.',
                ],
                [
                    'role' => 'user',
                    'content' => $prompt,
                ],
            ],
            'temperature' => 0.4,
            'max_tokens' => 600,
            'response_format' => ['type' => 'json_object'],
            'stream' => false,
        ];

        $response = $this->postChat($base, $apiKey, $body);
        if ($response === null) {
            return null;
        }

        if ($response->failed()) {
            Log::warning("Ollama model {$model} returned {$response->status()}: " . $response->body());
            return null;
        }

        $text = data_get($response->json(), 'choices.0.message.content');
        if (! is_string($text) || trim($text) === '') {
            Log::warning("Ollama model {$model} returned an empty response.");
            return null;
        }

        $suggestion = $this->parseJson($text);
        if ($suggestion === null) {
            Log::warning("Ollama model {$model} response could not be parsed: {$text}");
            return null;
        }

        return $suggestion;
    }

    private function tryPriceModel(string $base, string $model, string $apiKey, string $prompt): ?array
    {
        $body = [
            'model' => $model,
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'You are a JSON API that only returns valid JSON with integer prices in pesos. Do not include explanations, markdown, or extra text.',
                ],
                [
                    'role' => 'user',
                    'content' => $prompt,
                ],
            ],
            'temperature' => 0.2,
            'max_tokens' => 200,
            'response_format' => ['type' => 'json_object'],
            'stream' => false,
        ];

        $response = $this->postChat($base, $apiKey, $body);
        if ($response === null) {
            return null;
        }

        if ($response->failed()) {
            Log::warning("Ollama model {$model} returned {$response->status()}: " . $response->body());
            return null;
        }

        $text = data_get($response->json(), 'choices.0.message.content');
        if (! is_string($text) || trim($text) === '') {
            Log::warning("Ollama model {$model} returned an empty response.");
            return null;
        }

        $cleaned = trim($text);
        if (str_starts_with($cleaned, '```')) {
            $cleaned = preg_replace('/^```[a-zA-Z]*\s*|\s*```$/', '', $cleaned) ?? $cleaned;
        }
        $decoded = json_decode($cleaned, true);
        if (! is_array($decoded) || ! is_numeric($decoded['price'] ?? null)) {
            Log::warning("Ollama model {$model} price response could not be parsed: {$text}");
            return null;
        }

        $price = (int) round((float) $decoded['price']);
        if ($price < 2) {
            $price = 2;
        }

        return [
            'price' => $price,
        ];
    }

    private function postChat(string $base, string $apiKey, array $body): ?\Illuminate\Http\Client\Response
    {
        try {
            return Http::timeout(30)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . $apiKey,
                ])
                ->post("{$base}/chat/completions", $body);
        } catch (\Throwable $e) {
            Log::warning("Ollama request failed: " . $e->getMessage());
            return null;
        }
    }

    private function buildPrompt(array $input): string
    {
        $name = trim($input['name']);
        $known = [];
        if (! empty($input['sku'])) {
            $known[] = "SKU already typed: {$input['sku']}";
        }
        if (! empty($input['category'])) {
            $known[] = "Category already typed: {$input['category']}";
        }
        $knownText = $known ? ' Respect what is already given: ' . implode('; ', $known) . '.' : '';

        return <<<PROMPT
You are helping a small hardware store fill ONLY specific fields for a new product in the Add Product form.

Product name entered by the admin: "{$name}".{$knownText}

For this request, ONLY suggest values for: SKU/Item Code and Category.
Do NOT suggest price, reorder threshold, unit of measure, starting stock, or quantity.

Return ONLY a JSON object with exactly these keys:

- "name": a clean, properly capitalised product display name (fix casing/spelling, keep it recognisable). Write the display name in ENGLISH — translate Tagalog/Filipino or other non-English input to English (e.g. "pako" -> "Nails", "turnilyo" -> "Screws", "kawad" -> "Wire"). Keep the item recognisable.
- "sku": a short, readable stock-keeping code in ENGLISH, uppercase. Use English name-derived segments. Do NOT include unit of measure or quantity (never "1KG", "5PCS"). Prefer full English words over unclear abbreviations. This will be overridden locally by a deterministic generator, but still suggest a sensible English form.
- "category": a concise ENGLISH hardware category, e.g. "Power Tools", "Fasteners", "Plumbing", "Electrical", "Hand Tools", "Paint & Sundries".
- "description": a one-sentence ENGLISH description of the product (max 120 characters).

Do NOT return price, reorder_threshold, current_stock, receiving_unit/unit of measure.

Use plain text only. Do NOT use emojis, emoticons, or special decorative symbols anywhere in any field. Return professional, factual values suitable for an inventory system.

Do not include any text outside the JSON object.
PROMPT;
    }

    private function buildPricePrompt(array $input): string
    {
        $name = trim($input['name']);
        $unit = strtolower(trim($input['unit']));

        $minNote = ($unit === 'piece' || $unit === 'pc' || $unit === 'pcs')
            ? 'For "piece/pc/pcs" the MINIMUM realistic retail price is 2.00 pesos. Never suggest below 2.'
            : 'Suggest a realistic whole-peso price for this unit.';

        return <<<PROMPT
You are helping a small hardware store set a realistic retail price in the Add Product form.

Product name: "{$name}"
Unit of measure: "{$unit}"

Suggest a realistic retail price PER "{$unit}" for this item in the PHILIPPINE hardware market (PHP).
Consider common retail sizes/packaging for this unit (e.g. "nail" sold per piece vs per kilogram; "wire" per meter/roll; etc.).

RULES:
- Return price as a WHOLE NUMBER in pesos ONLY. NO decimals, NO cents, NO .00, NO .50.
- {$minNote}
- Use common hardware retail prices. Avoid extreme outliers.

Return ONLY a JSON object with exactly this key:
- "price": an integer (whole number) in PHP, e.g. 2, 3, 5, 25, 150.

Do not include any other keys, explanations, or text outside the JSON object.
PROMPT;
    }

    private function parseJson(string $text): ?array
    {
        $text = trim($text);

        // Models occasionally wrap JSON in ```json fences despite the schema.
        if (str_starts_with($text, '```')) {
            $text = preg_replace('/^```[a-zA-Z]*\s*|\s*```$/', '', $text) ?? $text;
        }

        $decoded = json_decode($text, true);
        if (! is_array($decoded)) {
            return null;
        }

        // Normalise: only Item Code (SKU) and Category are AI-suggested for the
        // product-name-driven suggestion. Price/reorder_threshold are manual now.
        return [
            'name'        => (string) ($decoded['name'] ?? ''),
            'sku'         => strtoupper((string) ($decoded['sku'] ?? '')),
            'category'    => (string) ($decoded['category'] ?? ''),
            'description' => (string) ($decoded['description'] ?? ''),
        ];
    }
}
