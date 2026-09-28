<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\StockIn;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

/**
 * Seeds realistic sample sales + stock-in history so the Transaction History
 * page (SS-24), the transaction summary CSV (SS-25 / SS-34), the revenue
 * chart and the demand-based restocking suggestions (SS-35) all have real
 * data to display.
 *
 * The dataset is deterministic (fixed random seed) so every environment shows
 * the same numbers — easier to demo and to assert on in tests.
 */
class SaleSeeder extends Seeder
{
    /**
     * Cashier handles used on the sample sales, so the transaction history
     * demonstrates the "who processed this?" column from the BRD.
     *
     * @var array<string, int>  email => max spend per transaction
     */
    protected const CASHIERS = [
        'admin1@gmail.com' => 6000,
        'cashier1@gmail.com' => 2500,
        'cashier2@gmail.com' => 1800,
    ];

    public function run(): void
    {
        $staff = $this->resolveStaff();

        if ($staff->isEmpty()) {
            $this->command?->warn('No staff accounts found — skipping sale history seed.');

            return;
        }

        $products = Product::all();
        if ($products->isEmpty()) {
            $this->command?->warn('No products found — run ProductSeeder first.');

            return;
        }

        $suppliers = Supplier::where('is_active', true)->get();

        // Never stack demo data on top of real data.
        if (Sale::exists()) {
            $this->command?->info('Sales already exist — skipping sale history seed.');

            return;
        }

        mt_srand(20260928);

        // Build the sales first, then top the stock back up with the matching
        // stock-in records. This keeps the catalogue realistic: products are
        // NOT all drained to zero by the demo history.
        $this->seedSales($staff, $products);
        $this->seedStockIns($staff->first(), $products, $suppliers);

        $this->command?->info(sprintf(
            'Seeded %d sales, %d sale items and %d stock-ins.',
            Sale::count(),
            SaleItem::count(),
            StockIn::count(),
        ));
    }

    /**
     * Resolve the accounts that can appear on a transaction.
     *
     * @return \Illuminate\Support\Collection<int, User>
     */
    protected function resolveStaff()
    {
        return User::whereIn('email', array_keys(self::CASHIERS))->get()
            ->sortBy(fn (User $u) => array_search($u->email, array_keys(self::CASHIERS), true));
    }

    /**
     * Create ~30 days of sales so the 30-day revenue chart is fully populated.
     *
     * @param  \Illuminate\Support\Collection<int, User>  $staff
     * @param  \Illuminate\Support\Collection<int, Product>  $products
     */
    protected function seedSales($staff, $products): void
    {
        $staffList = $staff->values();

        for ($dayOffset = 29; $dayOffset >= 0; $dayOffset--) {
            $date = Carbon::today()->subDays($dayOffset);

            // Busier on weekdays, quieter on Sundays — makes the chart look real.
            $isSunday = $date->isSunday();
            $maxSales = $isSunday ? mt_rand(1, 2) : mt_rand(2, 4);

            for ($n = 0; $n < $maxSales; $n++) {
                // Spread transactions across trading hours.
                $soldAt = $date->copy()
                    ->setTime(mt_rand(8, 19), mt_rand(0, 59), mt_rand(0, 59));

                $staffMember = $staffList[mt_rand(0, $staffList->count() - 1)];
                $lineCount = mt_rand(1, 3);

                $this->createSale($soldAt, $staffMember, $products, $lineCount);
            }
        }
    }

    /**
     * Build a single sale plus its line items, and deduct the stock.
     *
     * @param  \Illuminate\Support\Collection<int, Product>  $products
     */
    protected function createSale(Carbon $soldAt, User $staffMember, $products, int $lineCount): void
    {
        // Pick distinct products that still have stock to sell.
        $picked = [];
        $candidates = $products->where('current_stock', '>', 0)->shuffle();

        foreach ($candidates->take($lineCount) as $product) {
            $picked[$product->id] = [
                'product' => $product,
                'quantity' => mt_rand(1, min(3, (int) $product->current_stock)),
            ];
        }

        if ($picked === []) {
            return;
        }

        $subtotal = 0.0;

        foreach ($picked as $entry) {
            $product = $entry['product'];
            $quantity = $entry['quantity'];
            $unitPrice = (float) $product->price;
            $lineTotal = round($unitPrice * $quantity, 2);
            $subtotal += $lineTotal;
        }

        $subtotal = round($subtotal, 2);
        $tax = round($subtotal * 0.12, 2);
        $total = round($subtotal + $tax, 2);

        // Cashiers tend to receive round amounts.
        $payment = ceil($total / 100) * 100;
        $change = round($payment - $total, 2);

        $sale = new Sale;
        $sale->user_id = $staffMember->id;
        $sale->total_amount = $total;
        $sale->payment_amount = $payment;
        $sale->change_amount = $change;
        $sale->created_at = $soldAt;
        $sale->updated_at = $soldAt;
        $sale->save();

        foreach ($picked as $entry) {
            $product = $entry['product'];
            $quantity = $entry['quantity'];
            $unitPrice = (float) $product->price;

            $item = new SaleItem;
            $item->sale_id = $sale->id;
            $item->product_id = $product->id;
            $item->quantity = $quantity;
            $item->unit_price = $unitPrice;
            $item->line_total = round($unitPrice * $quantity, 2);
            $item->created_at = $soldAt;
            $item->updated_at = $soldAt;
            $item->save();

            $product->current_stock = max(0, (int) $product->current_stock - $quantity);
            $product->save();
        }
    }

    /**
     * Create stock-in history so the dashboard's "recent stock-ins" list and
     * each product's "last received" date have realistic values.
     *
     * @param  \Illuminate\Support\Collection<int, Product>  $products
     * @param  \Illuminate\Support\Collection<int, Supplier>  $suppliers
     */
    protected function seedStockIns(User $staffMember, $products, $suppliers): void
    {
        if ($products->isEmpty()) {
            return;
        }

        $products->each(function (Product $product, int $index) use ($staffMember, $suppliers) {
            if ($product->receiving_unit === null || $product->receiving_unit === '') {
                return;
            }

            $receivedAt = Carbon::today()->subDays(mt_rand(5, 45))
                ->setTime(mt_rand(8, 17), mt_rand(0, 59));

            $piecesPerUnit = max(1, (int) ($product->pieces_per_receiving_unit ?: 1));
            $units = mt_rand(2, 5);

            $stockIn = new StockIn;
            $stockIn->product_id = $product->id;
            $stockIn->user_id = $staffMember->id;
            $stockIn->supplier_id = $suppliers->isNotEmpty()
                ? $suppliers[$index % $suppliers->count()]->id
                : null;
            $stockIn->quantity_received = $units;
            $stockIn->unit_of_measure = $product->receiving_unit;
            $stockIn->unit_conversion = $piecesPerUnit;
            $stockIn->piece_delta = $units * $piecesPerUnit;
            $stockIn->stock_before = (int) $product->current_stock;
            $stockIn->note = 'Seeded receiving record';
            $stockIn->created_at = $receivedAt;
            $stockIn->updated_at = $receivedAt;
            $stockIn->save();

            // The seeded sales drained almost the whole catalogue, which made
            // every product read as "out of stock". Only roughly half of the
            // products get a matching delivery, so the demo ends up with a
            // believable mix of healthy stock and genuine low-stock alerts
            // (which is what the alerts + restock suggestions are for).
            $shouldRestock = ($index % 2) === 0;

            $restockTo = $shouldRestock
                ? max(
                    (int) $product->current_stock + $units * $piecesPerUnit,
                    (int) $product->reorder_threshold * 2,
                )
                : (int) $product->current_stock;

            $stockIn->stock_after = $restockTo;
            $stockIn->save();

            $product->current_stock = $restockTo;
            $product->save();
        });
    }
}
