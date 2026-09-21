<?php

namespace App\Services;

use App\Models\Batch;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class BatchProfitService
{
    /**
     * Profit for a single batch, based on the actual FIFO allocations
     * sold from it (net of any client refunds), never the product's
     * current sale price.
     *
     * @return array{batch_id: int, quantity_sold: int, revenue: float, cost: float, profit: float}
     */
    public function forBatch(Batch $batch): array
    {
        $rows = $this->aggregate(fn ($query) => $query->where('client_order_allocations.batch_id', $batch->id));

        return $rows->first() ?? [
            'batch_id' => $batch->id,
            'quantity_sold' => 0,
            'revenue' => 0.0,
            'cost' => 0.0,
            'profit' => 0.0,
        ];
    }

    /**
     * Profit for every batch that has at least one FIFO allocation.
     *
     * @return Collection<int, array{batch_id: int, quantity_sold: int, revenue: float, cost: float, profit: float}>
     */
    public function forAllBatches(): Collection
    {
        return $this->aggregate();
    }

    /**
     * @return Collection<int, array{batch_id: int, quantity_sold: int, revenue: float, cost: float, profit: float}>
     */
    private function aggregate(?\Closure $constrain = null): Collection
    {
        $refundedPerAllocation = DB::table('client_refund_items')
            ->select('order_allocation_id', DB::raw('SUM(quantity) as refunded_quantity'))
            ->groupBy('order_allocation_id');

        $query = DB::table('client_order_allocations')
            ->join('client_order_items', 'client_order_items.id', '=', 'client_order_allocations.order_item_id')
            ->leftJoinSub($refundedPerAllocation, 'refunds', 'refunds.order_allocation_id', '=', 'client_order_allocations.id')
            ->selectRaw(
                'client_order_allocations.batch_id as batch_id,'
                .' SUM(client_order_allocations.quantity - COALESCE(refunds.refunded_quantity, 0)) as quantity_sold,'
                .' SUM((client_order_allocations.quantity - COALESCE(refunds.refunded_quantity, 0)) * client_order_items.unit_price) as revenue,'
                .' SUM((client_order_allocations.quantity - COALESCE(refunds.refunded_quantity, 0)) * client_order_allocations.unit_cost) as cost'
            )
            ->groupBy('client_order_allocations.batch_id');

        if ($constrain) {
            $constrain($query);
        }

        return $query->get()->map(function ($row) {
            $revenue = round((float) $row->revenue, 2);
            $cost = round((float) $row->cost, 2);

            return [
                'batch_id' => (int) $row->batch_id,
                'quantity_sold' => (int) $row->quantity_sold,
                'revenue' => $revenue,
                'cost' => $cost,
                'profit' => round($revenue - $cost, 2),
            ];
        });
    }
}
