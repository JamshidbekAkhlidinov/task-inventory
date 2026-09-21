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

    /**
     * Profit for a single batch, computed from its actual FIFO sales
     * net of client refunds — never the product's current sale price.
     */
    public function show(Batch $batch): JsonResponse
    {
        return response()->json(
            $this->batchProfitService->forBatch($batch)
        );
    }

    /**
     * Profit for every batch that has at least one FIFO allocation.
     */
    public function index(): JsonResponse
    {
        return response()->json([
            'data' => $this->batchProfitService->forAllBatches()->values(),
        ]);
    }
}
