<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Provider;
use Illuminate\Contracts\View\View;

class CategoryController extends Controller
{
    public function index(): View
    {
        return view('categories.index', [
            'providers' => Provider::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }
}
