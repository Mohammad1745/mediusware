<?php

namespace App\Http\Services;

use App\Models\Product;

class ProductService
{
    use ResponseService;

    public function getProducts () {
        try {
            $products = Product::all();
//            dd ($products);

            return $this->response($products->toArray())->success();

        } catch (\Exception $exception) {
            dd($exception);

            return $this->response()->error($exception->getMessage());
        }
    }
}
