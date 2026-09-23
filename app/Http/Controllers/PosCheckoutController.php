<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class PosCheckoutController extends Controller
{
    /**
     * Tax rate applied to POS sales.
     */
    private const TAX_RATE = 0.12;

    /**
     * Resolve the authenticated user used for ownership checks.
     */
    protected function currentUser(): ?\App\Models\User
    {
        return Auth::user();
    }

    /**
     * Show the POS checkout page.
     */
    public function index()
    {
        return view('pos');
    }

    /**
     * Process POS checkout - atomic transaction for sale and inventory deduction.
     */
    public function checkout(Request $request): JsonResponse
    {
        $user = $this->currentUser();
        if (! $user) {
            return response()->json(['message' => 'Authentication required.'], 401);
        }

        try {
            $validated = $request->validate([
                'cart_items' => 'required|array|min:1',
                'cart_items.*.product_id' => 'required|integer|exists:products,id',
                'cart_items.*.quantity' => 'required|integer|min:1',
                'payment_amount' => 'required|numeric|min:0',
            ]);
        } catch (ValidationException $exception) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors' => $exception->errors(),
            ], 422);
        }

        return DB::transaction(function () use ($user, $validated) {
            $cartItems = $this->normalizeCartItems($validated['cart_items']);
            $paymentAmount = (float) $validated['payment_amount'];

            $subtotal = 0.0;
            $saleItemsData = [];

            foreach ($cartItems as $cartItem) {
                $product = Product::findOrFail($cartItem['product_id']);

                if ($product->user_id !== $user->id) {
                    return response()->json([
                        'message' => 'Unauthorized: Product does not belong to you.',
                        'product_id' => $product->id,
                    ], 403);
                }

                if ($product->current_stock < $cartItem['quantity']) {
                    return response()->json([
                        'message' => 'Insufficient stock.',
                        'product_id' => $product->id,
                        'product_name' => $product->name,
                        'available_stock' => $product->current_stock,
                        'requested_quantity' => $cartItem['quantity'],
                    ], 409);
                }

                $unitPrice = (float) $product->price;
                $lineTotal = round($unitPrice * $cartItem['quantity'], 2);
                $subtotal = round($subtotal + $lineTotal, 2);

                $saleItemsData[] = [
                    'product_id' => $product->id,
                    'quantity' => $cartItem['quantity'],
                    'unit_price' => $unitPrice,
                    'line_total' => $lineTotal,
                ];
            }

            $taxAmount = round($subtotal * self::TAX_RATE, 2);
            $totalAmount = round($subtotal + $taxAmount, 2);

            if ($paymentAmount < $totalAmount) {
                return response()->json([
                    'message' => 'Payment amount is insufficient.',
                    'total_amount' => $totalAmount,
                    'payment_amount' => $paymentAmount,
                ], 402);
            }

            $changeAmount = round($paymentAmount - $totalAmount, 2);

            $sale = Sale::create([
                'user_id' => $user->id,
                'total_amount' => $totalAmount,
                'payment_amount' => $paymentAmount,
                'change_amount' => $changeAmount,
            ]);

            foreach ($saleItemsData as $itemData) {
                SaleItem::create([
                    'sale_id' => $sale->id,
                    'product_id' => $itemData['product_id'],
                    'quantity' => $itemData['quantity'],
                    'unit_price' => $itemData['unit_price'],
                    'line_total' => $itemData['line_total'],
                ]);

                $stockUpdated = Product::whereKey($itemData['product_id'])
                    ->where('current_stock', '>=', $itemData['quantity'])
                    ->decrement('current_stock', $itemData['quantity']);

                if ($stockUpdated < 1) {
                    throw new \RuntimeException('Stock changed while processing checkout.');
                }
            }

            return response()->json([
                'message' => 'Checkout completed successfully.',
                'sale_id' => $sale->id,
                'subtotal_amount' => $subtotal,
                'tax_amount' => $taxAmount,
                'total_amount' => $totalAmount,
                'payment_amount' => $paymentAmount,
                'change_amount' => $changeAmount,
                'change' => $changeAmount,
            ], 200);
        });
    }

    /**
     * Combine duplicate product selections so each product is checked and deducted once.
     */
    private function normalizeCartItems(array $cartItems): array
    {
        $normalized = [];

        foreach ($cartItems as $cartItem) {
            $productId = (int) $cartItem['product_id'];
            $quantity = (int) $cartItem['quantity'];

            if (! isset($normalized[$productId])) {
                $normalized[$productId] = [
                    'product_id' => $productId,
                    'quantity' => 0,
                ];
            }

            $normalized[$productId]['quantity'] += $quantity;
        }

        return array_values($normalized);
    }
}