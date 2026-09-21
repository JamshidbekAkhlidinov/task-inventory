<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Batch;
use App\Services\BatchProfitService;
use Illuminate\Http\JsonResponse;

class BatchProfitController extends Controller
{
    public function __construct(
        private readonly BatchProfitService $batchProfitService,
    ) {}

    public function show(Batch $batch): JsonResponse
    {
        return response()->json(
            $this->batchProfitService->forBatch($batch)
        );
    }

    public function index(): JsonResponse
    {
        return response()->json([
            'data' => $this->batchProfitService->forAllBatches()->values(),
        ]);
    }
}
