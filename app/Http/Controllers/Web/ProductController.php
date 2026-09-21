<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Contracts\View\View;

class ProductController extends Controller
{
    public function index(): View
    {
        return view('products.index', [
            'categories' => Category::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }
}
