<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Product;
use App\Models\Sale;
use App\Models\StockAdjustment;
use App\Models\StockIn;
use App\Models\User;

/**
 * ActivityLogger — writes a single human-readable row to `activity_logs` for
 * every meaningful admin action, so the Products page can render a unified
 * "Recent Activity" feed.
 *
 * Each static helper builds the ready-to-render `description` sentence and an
 * optional structured `meta` payload. Logging is best-effort: a failure here
 * must never break the underlying business action, so callers use these inside
 * try/catch (see ActivityLogger::safe()).
 */
class ActivityLogger
{
    /** Human labels for the stock-adjustment reason keys. */
    private const REASON_LABELS = [
        'damaged' => 'Damaged items',
        'lost' => 'Lost / missing',
        'internal_transfer' => 'Internal transfer',
        'correction' => 'Count correction',
    ];

    /**
     * Wrap a logging call so an audit failure can never abort the real action.
     * (DB errors, missing tables during a partial deploy, etc.)
     */
    public static function safe(callable $fn): void
    {
        try {
            $fn();
        } catch (\Throwable $e) {
            report($e);
        }
    }

    private static function log(User $user, string $action, string $description, array $extra = []): void
    {
        ActivityLog::create(array_merge([
            'user_id' => $user->id,
            'action' => $action,
            'description' => $description,
        ], $extra));
    }

    public static function productCreated(User $user, Product $product): void
    {
        // A brand-new row has no "before", so the change list describes the
        // starting values that were written.
        $changes = [
            ['field' => 'name', 'text' => 'Name "'.$product->name.'"'],
            ['field' => 'sku', 'text' => 'SKU '.$product->sku],
            ['field' => 'category', 'text' => 'Category '.($product->category ?: 'Uncategorized')],
            ['field' => 'price', 'text' => 'Price ₱'.number_format((float) $product->price, 2)],
            ['field' => 'receiving_unit', 'text' => 'Unit '.($product->receiving_unit ?: 'piece')],
            ['field' => 'current_stock', 'text' => 'Starting stock '.(int) $product->current_stock],
            ['field' => 'reorder_threshold', 'text' => 'Reorder threshold '.(int) $product->reorder_threshold],
            ['field' => 'is_active', 'text' => 'Status Active'],
        ];

        self::log($user, 'product_created', "Added new product {$product->name}", [
            'subject_type' => 'product',
            'subject_id' => $product->id,
            'subject_name' => $product->name,
            'meta' => [
                'sku' => $product->sku,
                'category' => $product->category,
                'stock' => (int) $product->current_stock,
                'changes' => $changes,
            ],
        ]);
    }

    /**
     * Log a product edit, summarising only the fields that actually changed
     * (e.g. "Renamed Copper Wire 12 AWG → Copper Wire 10 AWG; price ₱850 → ₱900").
     * If nothing changed, no row is written.
     *
     * @param  array<string,mixed>  $before  product attribute snapshot BEFORE update
     */
    public static function productUpdated(User $user, Product $product, array $before): void
    {
        $changed = self::describeProductChanges($before, $product);
        if ($changed === []) {
            return;
        }

        // Lead with the most meaningful change (a rename), then the rest.
        $parts = array_map(fn ($c) => $c['text'], $changed);
        $description = "Edited {$product->name}: ".implode('; ', $parts);

        self::log($user, 'product_updated', $description, [
            'subject_type' => 'product',
            'subject_id' => $product->id,
            'subject_name' => $product->name,
            'meta' => ['changes' => $changed],
        ]);
    }

    public static function productDeactivated(User $user, Product $product): void
    {
        self::log($user, 'product_deactivated', "Deactivated {$product->name}", [
            'subject_type' => 'product',
            'subject_id' => $product->id,
            'subject_name' => $product->name,
            'meta' => [
                'changes' => [
                    ['field' => 'is_active', 'text' => 'Status Active → Deactivated'],
                    ['field' => 'visibility', 'text' => 'Hidden from Product List and POS catalog'],
                    ['field' => 'history', 'text' => 'Stock history kept, SKU stays reserved'],
                ],
            ],
        ]);
    }

    public static function productReactivated(User $user, Product $product): void
    {
        self::log($user, 'product_reactivated', "Reactivated {$product->name}", [
            'subject_type' => 'product',
            'subject_id' => $product->id,
            'subject_name' => $product->name,
            'meta' => [
                'changes' => [
                    ['field' => 'is_active', 'text' => 'Status Deactivated → Active'],
                    ['field' => 'visibility', 'text' => 'Back in Product List and POS catalog'],
                ],
            ],
        ]);
    }

    public static function stockReceived(User $user, StockIn $stockIn, ?Product $product = null): void
    {
        $name = $product?->name ?? $stockIn->product?->name ?? 'product';
        $unit = $stockIn->unit_of_measure ?: 'unit';
        $description = "Received {$stockIn->quantity_received} {$unit} of {$name}";

        self::log($user, 'stock_received', $description, [
            'subject_type' => 'product',
            'subject_id' => $stockIn->product_id,
            'subject_name' => $name,
            'meta' => [
                'quantity' => (int) $stockIn->quantity_received,
                'unit' => $unit,
                'stock_before' => (int) $stockIn->stock_before,
                'stock_after' => (int) $stockIn->stock_after,
                'piece_delta' => (int) $stockIn->piece_delta,
                'changes' => array_values(array_filter([
                    ['field' => 'stock', 'text' => 'Stock '.$stockIn->stock_before.' → '.$stockIn->stock_after],
                    ['field' => 'received', 'text' => 'Received '.(int) $stockIn->quantity_received.' '.$unit],
                    $stockIn->piece_delta
                        ? ['field' => 'pieces', 'text' => 'Pieces '.($stockIn->piece_delta > 0 ? '+' : '').(int) $stockIn->piece_delta]
                        : null,
                ])),
            ],
        ]);
    }

    public static function stockAdjusted(User $user, StockAdjustment $adjustment, ?Product $product = null): void
    {
        $name = $product?->name ?? $adjustment->product?->name ?? 'product';
        $reason = self::REASON_LABELS[$adjustment->reason] ?? ucfirst((string) $adjustment->reason);
        $sign = $adjustment->delta > 0 ? "+{$adjustment->delta}" : (string) $adjustment->delta;
        $description = "Adjusted {$name}: {$adjustment->stock_before} → {$adjustment->stock_after} ({$reason}, {$sign})";

        $changes = array_values(array_filter([
            ['field' => 'stock', 'text' => 'Stock '.$adjustment->stock_before.' → '.$adjustment->stock_after],
            ['field' => 'delta', 'text' => 'Delta '.$sign],
            ['field' => 'reason', 'text' => 'Reason '.$reason],
            $adjustment->reason_note
                ? ['field' => 'note', 'text' => 'Note '.$adjustment->reason_note]
                : null,
        ]));

        self::log($user, 'stock_adjusted', $description, [
            'subject_type' => 'product',
            'subject_id' => $adjustment->product_id,
            'subject_name' => $name,
            'meta' => [
                'reason' => $adjustment->reason,
                'reason_label' => $reason,
                'delta' => (int) $adjustment->delta,
                'stock_before' => (int) $adjustment->stock_before,
                'stock_after' => (int) $adjustment->stock_after,
                'changes' => $changes,
            ],
        ]);
    }

    public static function saleCompleted(User $user, Sale $sale, int $itemCount): void
    {
        $description = "Completed a sale of {$itemCount} ".($itemCount === 1 ? 'item' : 'items')
            .' for ₱'.number_format((float) $sale->total_amount, 2);

        self::log($user, 'sale_completed', $description, [
            'subject_type' => 'sale',
            'subject_id' => $sale->id,
            'subject_name' => 'Sale #'.$sale->id,
            'meta' => [
                'total_amount' => (float) $sale->total_amount,
                'item_count' => $itemCount,
                'changes' => [
                    ['field' => 'items', 'text' => 'Items sold '.(int) $itemCount],
                    ['field' => 'total', 'text' => 'Total ₱'.number_format((float) $sale->total_amount, 2)],
                ],
            ],
        ]);
    }

    /**
     * Compare a before-snapshot against the freshly-saved product and return a
     * list of {field, text} entries for the fields that changed.
     *
     * @param  array<string,mixed>  $before
     * @return array<int,array{field:string,text:string}>
     */
    private static function describeProductChanges(array $before, Product $product): array
    {
        $changes = [];

        $oldName = (string) ($before['name'] ?? '');
        $newName = (string) $product->name;
        if ($oldName !== '' && $oldName !== $newName) {
            $changes[] = ['field' => 'name', 'text' => "\"{$oldName}\" → \"{$newName}\""];
        }

        $oldSku = (string) ($before['sku'] ?? '');
        if ($oldSku !== '' && $oldSku !== (string) $product->sku) {
            $changes[] = ['field' => 'sku', 'text' => "SKU {$oldSku} → {$product->sku}"];
        }

        $oldCat = (string) ($before['category'] ?? '');
        $newCat = (string) ($product->category ?? '');
        if ($oldCat !== $newCat) {
            $changes[] = [
                'field' => 'category',
                'text' => 'Category '.($oldCat !== '' ? $oldCat : '—').' → '.($newCat !== '' ? $newCat : '—'),
            ];
        }

        $oldPrice = (float) ($before['price'] ?? 0);
        $newPrice = (float) $product->price;
        if (round($oldPrice, 2) !== round($newPrice, 2)) {
            $changes[] = [
                'field' => 'price',
                'text' => 'Price ₱'.number_format($oldPrice, 2).' → ₱'.number_format($newPrice, 2),
            ];
        }

        $oldUnit = (string) ($before['receiving_unit'] ?? '');
        $newUnit = (string) ($product->receiving_unit ?? '');
        if ($oldUnit !== $newUnit) {
            $changes[] = ['field' => 'receiving_unit', 'text' => "Unit {$oldUnit} → {$newUnit}"];
        }

        $oldThreshold = (int) ($before['reorder_threshold'] ?? 0);
        $newThreshold = (int) $product->reorder_threshold;
        if ($oldThreshold !== $newThreshold) {
            $changes[] = [
                'field' => 'reorder_threshold',
                'text' => "Reorder threshold {$oldThreshold} → {$newThreshold}",
            ];
        }

        // NOTE: current_stock is deliberately excluded here — stock changes flow
        // through Adjust / Receive which log their own, more precise rows. An
        // edit that only touches stock fields will not duplicate that here.

        return $changes;
    }
}
