<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\IngredientBatch;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;

class StockService
{
    /**
     * Deduct stock for an entire order using FEFO logic
     */
    public function deductOrderStock(Order $order)
    {
        return DB::transaction(function () use ($order) {
            foreach ($order->details as $detail) {
                $this->deductMenuStock($detail);
            }
        });
    }

    /**
     * Deduct stock for a single menu item (OrderDetail)
     * Optional $manualSelections: [ingredient_id => batch_id]
     */
    public function deductMenuStock(OrderDetail $detail, $manualSelections = [])
    {
        $menu = $detail->menu;
        $orderQty = $detail->qty;
        $totalHPP = 0;

        foreach ($menu->ingredients as $recipe) {
            $totalNeeded = $recipe->quantity * $orderQty;
            $batchId = $manualSelections[$recipe->ingredient_id] ?? null;
            
            $hppForThisIngredient = $this->deductIngredientBatch(
                $recipe->ingredient_id, 
                $totalNeeded, 
                $detail->order->invoice_no, 
                $batchId,
                $detail->id
            );
            $totalHPP += $hppForThisIngredient;
        }

        $detail->update(['hpp' => $totalHPP, 'is_stock_deducted' => true]);
    }

    /**
     * Deduct specific ingredient using FEFO or specific batch
     */
    public function deductIngredientBatch($ingredientId, $quantity, $reference, $specificBatchId = null, $orderDetailId = null)
    {
        $totalCost = 0;
        $remainingToDeduct = $quantity;

        if ($specificBatchId) {
            // Manual Selection: Prioritize this batch
            $batches = IngredientBatch::where('id', $specificBatchId)
                ->union(
                    IngredientBatch::where('ingredient_id', $ingredientId)
                        ->where('id', '!=', $specificBatchId)
                        ->where('remaining_quantity', '>', 0)
                        ->orderByRaw('expiry_date ASC NULLS LAST')
                )
                ->get();
        } else {
            // Default FEFO
            $batches = IngredientBatch::where('ingredient_id', $ingredientId)
                ->where('remaining_quantity', '>', 0)
                ->orderByRaw('expiry_date ASC NULLS LAST')
                ->orderBy('entry_date', 'asc')
                ->get();
        }

        foreach ($batches as $batch) {
            if ($remainingToDeduct <= 0) break;
            if ($batch->remaining_quantity <= 0) continue;

            $deduction = 0;
            if ($batch->remaining_quantity >= $remainingToDeduct) {
                $deduction = $remainingToDeduct;
                $batch->remaining_quantity -= $remainingToDeduct;
                $remainingToDeduct = 0;
            } else {
                $deduction = $batch->remaining_quantity;
                $remainingToDeduct -= $batch->remaining_quantity;
                $batch->remaining_quantity = 0;
            }

            $costForThisBatch = ($deduction * $batch->buy_price);
            $totalCost += $costForThisBatch;
            $batch->save();

            StockMovement::create([
                'ingredient_id' => $ingredientId,
                'ingredient_batch_id' => $batch->id,
                'order_detail_id' => $orderDetailId,
                'type' => 'out',
                'quantity' => $deduction,
                'cost_total' => $costForThisBatch,
                'reason' => 'sales_deduction',
                'reference' => $reference
            ]);
        }

        if ($remainingToDeduct > 0) {
            StockMovement::create([
                'ingredient_id' => $ingredientId,
                'order_detail_id' => $orderDetailId,
                'type' => 'out',
                'quantity' => $remainingToDeduct,
                'reason' => 'sales_deduction_out_of_stock',
                'reference' => $reference
            ]);
        }

        return $totalCost;
    }

    /**
     * Adjust stock based on physical count (Stock Opname)
     */
    public function adjustStock($ingredientId, $physicalQty, $reason = 'stock_opname')
    {
        return DB::transaction(function () use ($ingredientId, $physicalQty, $reason) {
            $ingredient = \App\Models\Ingredient::findOrFail($ingredientId);
            $systemQty = $ingredient->currentStock();
            $difference = $physicalQty - $systemQty;

            if ($difference == 0) return true;

            if ($difference < 0) {
                $this->deductIngredientBatch($ingredientId, abs($difference), $reason);
            } else {
                $latestBatch = IngredientBatch::where('ingredient_id', $ingredientId)
                    ->orderBy('entry_date', 'desc')
                    ->first();

                if ($latestBatch) {
                    $latestBatch->remaining_quantity += $difference;
                    $latestBatch->save();
                }

                StockMovement::create([
                    'ingredient_id' => $ingredientId,
                    'ingredient_batch_id' => $latestBatch ? $latestBatch->id : null,
                    'type' => 'in',
                    'quantity' => $difference,
                    'reason' => $reason,
                    'reference' => 'Adjustment Gain'
                ]);
            }

            return true;
        });
    }
}
