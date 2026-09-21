<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Storage;
use Illuminate\Contracts\View\View;

class OrderController extends Controller
{
    public function create(): View
    {
        return view('orders.create', [
            'clients' => Client::query()->orderBy('name')->get(['id', 'name']),
            'storages' => Storage::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }
}
