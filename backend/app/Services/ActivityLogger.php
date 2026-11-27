<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Product;
use App\Models\Sale;
use App\Models\StockAdjustment;
use App\Models\StockIn;
use App\Models\Supplier;
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

    /**
     * Sale completed on either POS till (Admin or Cashier).
     *
     * Scoped to the POS Checkout page's Recent Activity List: the default
     * Products feed excludes `sale_completed`, and Transactions remains the
     * source of truth for sale records. `meta.items` keeps the receipt lines
     * so the detail popup can list what was sold without a second request.
     *
     * @param  array<int, array{name?:string,quantity:int,unit_price:float,line_total:float}>  $lines
     */
    public static function saleCompleted(User $user, Sale $sale, array $lines = []): void
    {
        $receipt = 'SS-' . str_pad((string) $sale->id, 6, '0', STR_PAD_LEFT);
        $channel = $sale->channel === 'admin' ? 'Admin POS' : 'Cashier POS';
        $total   = number_format((float) $sale->total_amount, 2);

        $items = array_map(fn (array $line) => [
            'name'       => $line['name'] ?? 'Item',
            'quantity'   => (int) ($line['quantity'] ?? 0),
            'unit_price' => (float) ($line['unit_price'] ?? 0),
            'line_total' => (float) ($line['line_total'] ?? 0),
        ], $lines);

        $changes = [
            ['field' => 'receipt', 'text' => "Receipt {$receipt}"],
            ['field' => 'channel', 'text' => "Channel {$channel}"],
            ['field' => 'total', 'text' => "Total \u{20B1}{$total}"],
        ];

        foreach ($items as $item) {
            $linePrice = number_format($item['unit_price'], 2);
            $changes[] = [
                'field' => 'item',
                'text'  => "{$item['quantity']}\u{00D7} {$item['name']} @ \u{20B1}{$linePrice}",
            ];
        }

        self::log($user, 'sale_completed', "Completed sale #{$receipt} ({$channel})", [
            'subject_type' => 'sale',
            'subject_id'   => $sale->id,
            'subject_name' => $receipt,
            'meta' => [
                'receipt_no'     => $receipt,
                'channel'        => $sale->channel,
                'total_amount'   => (float) $sale->total_amount,
                'payment_amount' => (float) $sale->payment_amount,
                'change_amount'  => (float) $sale->change_amount,
                'items'          => $items,
                'changes'        => $changes,
            ],
        ]);
    }

    /**
     * Suppliers page feed: a new supplier joined the directory.
     */
    public static function supplierCreated(User $user, Supplier $supplier): void
    {
        $changes = array_values(array_filter([
            ['field' => 'name', 'text' => 'Name "'.$supplier->name.'"'],
            $supplier->contact_person
                ? ['field' => 'contact_person', 'text' => 'Contact '.$supplier->contact_person]
                : null,
            $supplier->phone
                ? ['field' => 'phone', 'text' => 'Phone '.$supplier->phone]
                : null,
            $supplier->email
                ? ['field' => 'email', 'text' => 'Email '.$supplier->email]
                : null,
            ['field' => 'is_active', 'text' => 'Status Active'],
        ]));

        self::log($user, 'supplier_created', "Added new supplier {$supplier->name}", [
            'subject_type' => 'supplier',
            'subject_id' => $supplier->id,
            'subject_name' => $supplier->name,
            'meta' => [
                'contact_person' => $supplier->contact_person,
                'phone' => $supplier->phone,
                'email' => $supplier->email,
                'changes' => $changes,
            ],
        ]);
    }

    /**
     * Suppliers page feed: a supplier was removed (soft-deactivated) from the
     * active directory. Stock-in history keeps the reference.
     */
    public static function supplierRemoved(User $user, Supplier $supplier): void
    {
        self::log($user, 'supplier_removed', "Removed supplier {$supplier->name}", [
            'subject_type' => 'supplier',
            'subject_id' => $supplier->id,
            'subject_name' => $supplier->name,
            'meta' => [
                'changes' => [
                    ['field' => 'is_active', 'text' => 'Status Active → Removed'],
                    ['field' => 'visibility', 'text' => 'Hidden from the Supplier List and Stock-In'],
                    ['field' => 'history', 'text' => 'Past stock-in records keep this supplier'],
                ],
            ],
        ]);
    }

    /**
     * Users page feed: a new account was provisioned by an Admin.
     */
    public static function userCreated(User $actor, User $account): void
    {
        self::log($actor, 'user_created', "Created account for {$account->name}", [
            'subject_type' => 'user',
            'subject_id' => $account->id,
            'subject_name' => $account->name,
            'meta' => [
                'username' => $account->username,
                'role' => $account->role,
                'changes' => [
                    ['field' => 'name', 'text' => 'Name "'.$account->name.'"'],
                    ['field' => 'username', 'text' => 'Username '.$account->username],
                    ['field' => 'email', 'text' => 'Email '.$account->email],
                    ['field' => 'role', 'text' => 'Role '.ucfirst((string) $account->role)],
                    ['field' => 'is_active', 'text' => 'Status Active'],
                ],
            ],
        ]);
    }

    /**
     * Users page feed: an account's details or role changed.
     *
     * @param  array<string,mixed>  $before  account attributes snapshot BEFORE update
     * @param  bool  $passwordChanged  true when the same save also rotated the password
     *                                 (self-service Account modal); the password itself is never logged.
     */
    public static function userUpdated(User $actor, User $account, array $before, bool $passwordChanged = false): void
    {
        $changes = [];

        foreach (['name', 'username', 'email', 'role'] as $field) {
            $old = $before[$field] ?? null;
            $new = $account->{$field};
            if ($old !== null && (string) $old !== (string) $new) {
                $changes[] = [
                    'field' => $field,
                    'text' => ucfirst($field).' '.($old !== '' ? $old : '—').' → '.($new !== '' ? $new : '—'),
                ];
            }
        }

        if ($passwordChanged) {
            $changes[] = ['field' => 'password', 'text' => 'Password changed'];
        }

        if ($changes === []) {
            return;
        }

        $parts = array_map(fn (array $change) => $change['text'], $changes);

        self::log($actor, 'user_updated', "Updated account for {$account->name}: ".implode('; ', $parts), [
            'subject_type' => 'user',
            'subject_id' => $account->id,
            'subject_name' => $account->name,
            'meta' => ['changes' => $changes],
        ]);
    }

    /**
     * Users page feed: an account was deactivated (history is preserved).
     */
    public static function userDeactivated(User $actor, User $account): void
    {
        self::log($actor, 'user_deactivated', "Deactivated account for {$account->name}", [
            'subject_type' => 'user',
            'subject_id' => $account->id,
            'subject_name' => $account->name,
            'meta' => [
                'changes' => [
                    ['field' => 'is_active', 'text' => 'Status Active → Inactive'],
                    ['field' => 'access', 'text' => 'Cannot sign in until reactivated'],
                    ['field' => 'history', 'text' => 'Past sales stay linked to this account'],
                ],
            ],
        ]);
    }

    /**
     * Users page feed: an account was reactivated.
     */
    public static function userReactivated(User $actor, User $account): void
    {
        self::log($actor, 'user_reactivated', "Reactivated account for {$account->name}", [
            'subject_type' => 'user',
            'subject_id' => $account->id,
            'subject_name' => $account->name,
            'meta' => [
                'changes' => [
                    ['field' => 'is_active', 'text' => 'Status Inactive → Active'],
                    ['field' => 'access', 'text' => 'Can sign in again'],
                ],
            ],
        ]);
    }

    /**
     * Users page feed: an Admin manually reset a password.
     *
     * The password itself is never logged — only the fact that it changed.
     */
    public static function userPasswordReset(User $actor, User $account): void
    {
        self::log($actor, 'user_password_reset', "Reset password for {$account->name}", [
            'subject_type' => 'user',
            'subject_id' => $account->id,
            'subject_name' => $account->name,
            'meta' => [
                'changes' => [
                    ['field' => 'password', 'text' => 'Password reset by Admin'],
                    ['field' => 'access', 'text' => 'Previous password no longer works'],
                ],
            ],
        ]);
    }

    /**
     * Backups page feed: an on-demand backup completed successfully.
     */
    public static function backupCreated(User $user, ?string $filename, int $keep = 7): void
    {
        $name = $filename ?: 'Database backup';

        self::log($user, 'backup_created', $filename ? "Created backup {$filename}" : 'Created a database backup', [
            'subject_type' => 'backup',
            'subject_id' => null,
            'subject_name' => $name,
            'meta' => [
                'filename' => $filename,
                'keep' => $keep,
                'changes' => array_values(array_filter([
                    $filename ? ['field' => 'file', 'text' => 'File '.$filename] : null,
                    ['field' => 'retention', 'text' => 'Keep the newest '.$keep.' backups'],
                    ['field' => 'storage', 'text' => 'Stored in the database and on the server'],
                ])),
            ],
        ]);
    }

    /**
     * Backups page feed: a backup file was deleted.
     */
    public static function backupDeleted(User $user, string $filename): void
    {
        self::log($user, 'backup_deleted', "Deleted backup {$filename}", [
            'subject_type' => 'backup',
            'subject_id' => null,
            'subject_name' => $filename,
            'meta' => [
                'filename' => $filename,
                'changes' => [
                    ['field' => 'file', 'text' => 'File '.$filename],
                    ['field' => 'storage', 'text' => 'Removed from the database and server storage'],
                ],
            ],
        ]);
    }

    /**
     * Backups page feed: a backup file was downloaded.
     */
    public static function backupDownloaded(User $user, string $filename): void
    {
        self::log($user, 'backup_downloaded', "Downloaded backup {$filename}", [
            'subject_type' => 'backup',
            'subject_id' => null,
            'subject_name' => $filename,
            'meta' => [
                'filename' => $filename,
                'changes' => [
                    ['field' => 'file', 'text' => 'File '.$filename],
                    ['field' => 'format', 'text' => 'JSON database dump'],
                ],
            ],
        ]);
    }

    /**
     * Transactions page feed: the admin exported the transaction history as CSV.
     *
     * Scoped to the Transactions page's Recent Activity List — the page shows
     * export events (not sales) so the audit trail names every download of the
     * sales report. meta keeps the applied filters and the exported row count.
     *
     * @param  array<string, string|null>  $filters  search/cashier/channel/date_from/date_to
     */
    public static function transactionsExported(User $user, string $filename, int $rows, array $filters = []): void
    {
        $changes = [
            ['field' => 'file', 'text' => 'File '.$filename],
            ['field' => 'rows', 'text' => $rows.' transaction'.($rows === 1 ? '' : 's').' exported'],
            ['field' => 'format', 'text' => 'CSV file (Excel-compatible)'],
        ];

        $filterLines = array_values(array_filter([
            ! empty($filters['search']) ? ['field' => 'search', 'text' => 'Search "'.$filters['search'].'"'] : null,
            ! empty($filters['cashier']) ? ['field' => 'cashier', 'text' => 'Cashier '.$filters['cashier']] : null,
            ! empty($filters['channel']) ? ['field' => 'channel', 'text' => 'Channel '.($filters['channel'] === 'admin' ? 'Admin POS' : 'Cashier POS')] : null,
            ! empty($filters['date_from']) ? ['field' => 'date_from', 'text' => 'From '.$filters['date_from']] : null,
            ! empty($filters['date_to']) ? ['field' => 'date_to', 'text' => 'To '.$filters['date_to']] : null,
        ]));

        if ($filterLines === []) {
            $changes[] = ['field' => 'scope', 'text' => 'All transactions (no filters)'];
        } else {
            $changes = array_merge($changes, $filterLines);
        }

        self::log($user, 'transactions_exported', "Exported {$rows} transactions to {$filename}", [
            'subject_type' => 'export',
            'subject_id' => null,
            'subject_name' => $filename,
            'meta' => [
                'filename' => $filename,
                'rows' => $rows,
                'filters' => $filters,
                'changes' => $changes,
            ],
        ]);
    }

    public static function suggestionOrdered(User $user, \App\Models\OrderSuggestion $suggestion): void
    {
        $name = $suggestion->product?->name ?? 'product';
        $sku  = $suggestion->product?->sku  ?? '—';

        self::log($user, 'suggestion_ordered', "Marked order suggestion as ordered: {$name}", [
            'subject_type' => 'suggestion',
            'subject_id'   => $suggestion->id,
            'subject_name' => $name,
            'meta' => [
                'sku'           => $sku,
                'suggested_qty' => (int) ceil((float) $suggestion->suggested_qty),
                'urgency'       => $suggestion->urgency,
                'changes' => [
                    ['field' => 'status', 'text' => 'Status Active → Ordered'],
                    ['field' => 'product', 'text' => "Product {$name} ({$sku})"],
                    ['field' => 'qty', 'text' => 'Suggested qty '.(int) ceil((float) $suggestion->suggested_qty)],
                    ['field' => 'urgency', 'text' => 'Urgency '.ucfirst($suggestion->urgency)],
                ],
            ],
        ]);
    }

    public static function suggestionDismissed(User $user, \App\Models\OrderSuggestion $suggestion): void
    {
        $name = $suggestion->product?->name ?? 'product';
        $sku  = $suggestion->product?->sku  ?? '—';

        self::log($user, 'suggestion_dismissed', "Dismissed order suggestion: {$name}", [
            'subject_type' => 'suggestion',
            'subject_id'   => $suggestion->id,
            'subject_name' => $name,
            'meta' => [
                'sku'     => $sku,
                'urgency' => $suggestion->urgency,
                'changes' => [
                    ['field' => 'status', 'text' => 'Status Active → Dismissed'],
                    ['field' => 'product', 'text' => "Product {$name} ({$sku})"],
                    ['field' => 'urgency', 'text' => 'Urgency '.ucfirst($suggestion->urgency)],
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

    /**
     * Suppliers page feed: an Admin edited a supplier record (SS-124).
     *
     * @param  array<string,mixed>  $before
     */
    public static function supplierUpdated(User $actor, \App\Models\Supplier $supplier, array $before): void
    {
        $changes = [];
        $labels = [
            'name' => 'Name',
            'contact_person' => 'Contact person',
            'phone' => 'Phone',
            'email' => 'Email',
        ];

        foreach ($labels as $field => $label) {
            $old = trim((string) ($before[$field] ?? ''));
            $new = trim((string) ($supplier->{$field} ?? ''));
            if ($old !== $new) {
                $changes[] = [
                    'field' => $field,
                    'text' => "{$label} ".($old !== '' ? $old : '—').' → '.($new !== '' ? $new : '—'),
                ];
            }
        }

        if ($changes === []) {
            return;
        }

        $parts = array_map(fn (array $c) => $c['text'], $changes);

        self::log($actor, 'supplier_updated', "Updated {$supplier->name}: ".implode('; ', $parts), [
            'subject_type' => 'supplier',
            'subject_id' => $supplier->id,
            'subject_name' => $supplier->name,
            'meta' => ['changes' => $changes],
        ]);
    }

    /**
     * Categories page feed: an Admin created a hardware category (SS-123).
     */
    public static function categoryCreated(User $actor, \App\Models\Category $category): void
    {
        $changes = [
            ['field' => 'name', 'text' => "Name {$category->name}"],
        ];
        if ($category->description) {
            $changes[] = ['field' => 'description', 'text' => "Description {$category->description}"];
        }

        self::log($actor, 'category_created', "Added category {$category->name}", [
            'subject_type' => 'category',
            'subject_id' => $category->id,
            'subject_name' => $category->name,
            'meta' => ['changes' => $changes],
        ]);
    }

    /**
     * Categories page feed: an Admin renamed or updated a category (SS-123).
     *
     * @param  array<string,mixed>  $before
     */
    public static function categoryUpdated(User $actor, \App\Models\Category $category, array $before): void
    {
        $changes = [];
        foreach (['name' => 'Name', 'description' => 'Description'] as $field => $label) {
            $old = trim((string) ($before[$field] ?? ''));
            $new = trim((string) ($category->{$field} ?? ''));
            if ($old !== $new) {
                $changes[] = [
                    'field' => $field,
                    'text' => "{$label} ".($old !== '' ? $old : '—').' → '.($new !== '' ? $new : '—'),
                ];
            }
        }

        if ($changes === []) {
            return;
        }

        $parts = array_map(fn (array $c) => $c['text'], $changes);

        self::log($actor, 'category_updated', "Updated category {$category->name}: ".implode('; ', $parts), [
            'subject_type' => 'category',
            'subject_id' => $category->id,
            'subject_name' => $category->name,
            'meta' => ['changes' => $changes],
        ]);
    }

    /**
     * Categories page feed: an Admin deleted an unused category (SS-123).
     */
    public static function categoryDeleted(User $actor, string $name, int $id): void
    {
        self::log($actor, 'category_deleted', "Deleted category {$name}", [
            'subject_type' => 'category',
            'subject_id' => $id,
            'subject_name' => $name,
            'meta' => [
                'changes' => [
                    ['field' => 'deleted', 'text' => "Removed category {$name}"],
                ],
            ],
        ]);
    }
}
