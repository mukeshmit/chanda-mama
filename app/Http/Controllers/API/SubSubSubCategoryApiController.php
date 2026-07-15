<?php

namespace App\Http\Controllers\API;

use App\Helpers\CommonHelper;
use App\Models\Category;
use Illuminate\Http\Request;

class SubSubSubCategoryApiController extends CategoryApiController
{
    public function index(Request $request)
    {
        if ($request->filled('id')) {
            return parent::getCategories($request);
        }

        $request->merge([
            'limit' => $request->input('limit', 1000),
            'offset' => $request->input('offset', 1),
        ]);

        $response = parent::getCategories($request);
        $payload = $response->getData(true);
        $categories = collect($payload['data'] ?? []);
        $allCategories = Category::select('id', 'parent_id')->get()->keyBy('id');

        $records = $categories->filter(function ($category) use ($allCategories) {
            $parent = $allCategories->get((int) ($category['parent_id'] ?? 0));
            $grandParent = $parent ? $allCategories->get((int) $parent->parent_id) : null;
            $greatGrandParent = $grandParent ? $allCategories->get((int) $grandParent->parent_id) : null;

            return $parent
                && $grandParent
                && $greatGrandParent
                && (int) $greatGrandParent->parent_id === 0;
        })->values();

        return CommonHelper::responseWithData($records, $records->count());
    }

    public function save(Request $request)
    {
        if (!$this->hasValidSubSubCategoryParent($request)) {
            return CommonHelper::responseError('Please select valid Sub SubCategory');
        }

        return parent::save($request);
    }

    public function update(Request $request)
    {
        if (!$this->hasValidSubSubCategoryParent($request)) {
            return CommonHelper::responseError('Please select valid Sub SubCategory');
        }

        return parent::update($request);
    }

    public function delete(Request $request)
    {
        return parent::delete($request);
    }

    private function hasValidSubSubCategoryParent(Request $request): bool
    {
        $parentId = (int) $request->input('parent_id', 0);
        if ($parentId <= 0) {
            return false;
        }

        $parent = Category::find($parentId);
        if (!$parent || (int) $parent->parent_id === 0) {
            return false;
        }

        $grandParent = Category::find((int) $parent->parent_id);
        if (!$grandParent || (int) $grandParent->parent_id === 0) {
            return false;
        }

        $greatGrandParent = Category::find((int) $grandParent->parent_id);

        return $greatGrandParent && (int) $greatGrandParent->parent_id === 0;
    }
}
