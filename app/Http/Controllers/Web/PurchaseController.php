<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Provider;
use App\Models\Storage;
use Illuminate\Contracts\View\View;

class PurchaseController extends Controller
{
    public function create(): View
    {
        return view('purchases.create', [
            'providers' => Provider::query()->orderBy('name')->get(['id', 'name']),
            'storages' => Storage::query()->orderBy('name')->get(['id', 'name']),
            'products' => Product::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }
}
