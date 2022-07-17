<?php

namespace App\Http\Services;

use App\Models\Product;
use App\Models\Variant;

class ProductService
{
    use ResponseService;

    /**
     * @return array
     */
    public function index (): array
    {
        try {
            $products = Product::all();
            $variants = Variant::all();
//            dd ($products);
            $data = [
                'products' => $products->toArray(),
                'variants' => $variants->toArray()
            ];

            return $this->response($data)->success();

        } catch (\Exception $exception) {
            dd($exception);

            return $this->response()->error($exception->getMessage());
        }
    }
}
