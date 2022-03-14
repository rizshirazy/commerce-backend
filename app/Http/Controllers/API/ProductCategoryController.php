<?php

namespace App\Http\Controllers\API;

use App\Helpers\ResponseFormatter;
use App\Http\Controllers\Controller;
use App\Models\ProductCategory;
use Illuminate\Http\Request;

class ProductCategoryController extends Controller
{
    public function all(Request $request)
    {
        $id           = $request->input('id');
        $limit        = $request->input('limit');
        $name         = $request->input('name');
        $show_product = $request->input('show_product');

        if ($id) {
            $category = ProductCategory::with(['products'])->find($id);

            if (!$category) {
                return ResponseFormatter::error(
                    null,
                    'Data tidak ada',
                    404
                );
            }

            return ResponseFormatter::success(
                $category,
                'Data kategori berhasil diambil'
            );
        }

        $categories = ProductCategory::when($name, function ($q, $name) {
            return $q->where('name', 'like', '%' . $name . '%');
        });

        if ($show_product) {
            $categories->with('products');
        }

        return ResponseFormatter::success(
            $categories->paginate($limit),
            'Data kategori berhasil diambil'
        );
    }
}
