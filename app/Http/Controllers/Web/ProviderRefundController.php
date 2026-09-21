<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Batch;
use Illuminate\Contracts\View\View;

class ProviderRefundController extends Controller
{
    public function create(): View
    {
        $batches = Batch::query()
            ->whereHas('items', fn ($query) => $query->where('available_quantity', '>', 0))
            ->with([
                'provider:id,name',
                'storage:id,name',
                'items' => fn ($query) => $query->where('available_quantity', '>', 0)->with('product:id,name'),
            ])
            ->latest('purchased_at')
            ->get();

        return view('provider-refunds.create', [
            'batches' => $batches,
        ]);
    }
}
