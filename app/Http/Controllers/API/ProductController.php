<?php

namespace App\Http\Controllers\API;

use App\Helpers\ResponseFormatter;
use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function all(Request $request)
    {
        $id          = $request->input('id');
        $limit       = $request->input('limit');
        $name        = $request->input('name');
        $description = $request->input('description');
        $tags        = $request->input('tags');
        $categories  = $request->input('categories');
        $price_from  = $request->input('price_from');
        $price_to    = $request->input('price_to');

        if ($id) {
            $product = Product::with(['category', 'galleries'])->find($id);

            if (!$product) {
                return ResponseFormatter::error(
                    null,
                    'Data tidak ada',
                    404
                );
            }

            return ResponseFormatter::success(
                $product,
                'Data product berhasil diambil'
            );
        }

        $products = Product::with(['category', 'galleries'])
            ->when($name, function ($q, $name) {
                return $q->where('name', 'like', '%' . $name . '%');
            })
            ->when($description, function ($q, $description) {
                return $q->where('description', 'like', '%' . $description . '%');
            })
            ->when($tags, function ($q, $tags) {
                return $q->where('tags', 'like', '%' . $tags . '%');
            })
            ->when($price_from, function ($q, $price_from) {
                return $q->where('price_from', '>=', $price_from);
            })
            ->when($price_to, function ($q, $price_to) {
                return $q->where('$price_to', '<=', $price_to);
            })
            ->when($categories, function ($q, $categories) {
                return $q->where('categories', $categories);
            });

        return ResponseFormatter::success(
            $products->paginate($limit),
            'Data product berhasil diambil'
        );
    }
}
