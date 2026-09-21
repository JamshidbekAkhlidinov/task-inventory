<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;

class BatchProfitController extends Controller
{
    public function index(): View
    {
        return view('batches.profit');
    }
}
