<?php

namespace App\Http\Controllers\Web;

use App\Enums\ClientOrderStatus;
use App\Http\Controllers\Controller;
use App\Models\ClientOrder;
use Illuminate\Contracts\View\View;

class ClientRefundController extends Controller
{
    public function create(): View
    {
        $orders = ClientOrder::query()
            ->where('status', ClientOrderStatus::COMPLETED)
            ->with(['client:id,name', 'items.product:id,name', 'items.allocations.refundItems'])
            ->latest('ordered_at')
            ->get()
            ->map(function (ClientOrder $order) {
                $order->items->each(function ($item) {
                    $refunded = $item->allocations
                        ->flatMap(fn ($allocation) => $allocation->refundItems)
                        ->sum('quantity');

                    $item->refundable_quantity = $item->quantity - $refunded;
                });

                return $order;
            })
            ->filter(fn (ClientOrder $order) => $order->items->contains(fn ($item) => $item->refundable_quantity > 0))
            ->values();

        return view('client-refunds.create', [
            'orders' => $orders,
        ]);
    }
}
