<?php

namespace App\Http\Controllers\API;

use App\Helpers\CommonHelper;
use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SubSubSubCategoryApiController extends Controller
{
    private $categoryApiController;

    public function __construct(CategoryApiController $categoryApiController)
    {
        $this->categoryApiController = $categoryApiController;
    }

    public function index(Request $request)
    {
        try {
            if ($request->filled('id')) {
                return $this->categoryApiController->getCategories($request);
            }

            $request->merge([
                'limit' => $request->input('limit', 1000),
                'offset' => $request->input('offset', 1),
            ]);

            $response = $this->categoryApiController->getCategories($request);
            $payload = $response->getData(true);
            $categories = collect($payload['data'] ?? []);
            $allCategories = Category::select('id', 'parent_id')->get()->keyBy('id');

            $records = $categories->filter(function ($category) use ($allCategories) {
                return $this->isSubSubSubCategory((int) ($category['parent_id'] ?? 0), $allCategories);
            })->values();

            return CommonHelper::responseWithData($records, $records->count());
        } catch (\Throwable $e) {
            Log::error('Sub Sub SubCategory list error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return CommonHelper::responseError($e->getMessage());
        }
    }

    public function save(Request $request)
    {
        try {
            if (!$this->hasValidSubSubCategoryParent($request)) {
                return CommonHelper::responseError('Please select valid Sub SubCategory');
            }

            return $this->categoryApiController->save($request);
        } catch (\Throwable $e) {
            Log::error('Sub Sub SubCategory save error: ' . $e->getMessage(), [
                'parent_id' => $request->input('parent_id'),
                'language_id' => $request->input('language_id'),
                'trace' => $e->getTraceAsString(),
            ]);

            return CommonHelper::responseError($e->getMessage());
        }
    }

    public function update(Request $request)
    {
        try {
            if (!$this->hasValidSubSubCategoryParent($request)) {
                return CommonHelper::responseError('Please select valid Sub SubCategory');
            }

            return $this->categoryApiController->update($request);
        } catch (\Throwable $e) {
            Log::error('Sub Sub SubCategory update error: ' . $e->getMessage(), [
                'id' => $request->input('id'),
                'parent_id' => $request->input('parent_id'),
                'language_id' => $request->input('language_id'),
                'trace' => $e->getTraceAsString(),
            ]);

            return CommonHelper::responseError($e->getMessage());
        }
    }

    public function delete(Request $request)
    {
        try {
            return $this->categoryApiController->delete($request);
        } catch (\Throwable $e) {
            Log::error('Sub Sub SubCategory delete error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return CommonHelper::responseError($e->getMessage());
        }
    }

    private function hasValidSubSubCategoryParent(Request $request): bool
    {
        $parentId = (int) $request->input('parent_id', 0);
        if ($parentId <= 0) {
            return false;
        }

        $allCategories = Category::select('id', 'parent_id')->get()->keyBy('id');

        return $this->isSubSubCategory($parentId, $allCategories);
    }

    private function isSubSubSubCategory(int $parentId, $allCategories): bool
    {
        return $this->isSubSubCategory($parentId, $allCategories);
    }

    private function isSubSubCategory(int $categoryId, $allCategories): bool
    {
        $subSubCategory = $allCategories->get($categoryId);
        if (!$subSubCategory || (int) $subSubCategory->parent_id === 0) {
            return false;
        }

        $subCategory = $allCategories->get((int) $subSubCategory->parent_id);
        if (!$subCategory || (int) $subCategory->parent_id === 0) {
            return false;
        }

        $category = $allCategories->get((int) $subCategory->parent_id);

        return $category && (int) $category->parent_id === 0;
    }
}
