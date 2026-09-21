<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCategoryRequest;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\ValidationException;

class CategoryController extends Controller
{
    /**
     * List all categories, including their provider and parent category.
     */
    public function index(): AnonymousResourceCollection
    {
        return CategoryResource::collection(
            Category::query()->with(['provider', 'parent'])->orderBy('name')->get()
        );
    }

    /**
     * Create a category.
     */
    public function store(StoreCategoryRequest $request): JsonResponse
    {
        $category = Category::query()->create($request->validated());

        return (new CategoryResource($category->load(['provider', 'parent'])))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Update a category.
     */
    public function update(StoreCategoryRequest $request, Category $category): JsonResponse
    {
        $data = $request->validated();

        if (($data['parent_id'] ?? null) === $category->id) {
            throw ValidationException::withMessages([
                'parent_id' => 'A category cannot be its own parent.',
            ]);
        }

        $category->update($data);

        return (new CategoryResource($category->load(['provider', 'parent'])))->response();
    }

    /**
     * Soft delete a category.
     */
    public function destroy(Category $category): JsonResponse
    {
        $category->delete();

        return response()->json(null, 204);
    }
}
