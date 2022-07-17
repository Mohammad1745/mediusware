<?php

namespace App\Http\Services;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ProductVariantPrice;
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
            $variants = Variant::all();
            $products = Product::all();
            $products->map(function ($product) {
                $productVariantPrices = ProductVariantPrice::where(['product_id' => $product['id']])->get();
                $productVariantPrices->map(function ($variantPrice) {
                    $variants = ProductVariant::where(['id' => $variantPrice['product_variant_one']])
                        ->orWhere(['id' => $variantPrice['product_variant_two']])
                        ->orWhere(['id' => $variantPrice['product_variant_three']])
                        ->pluck('variant')->toArray();
                    $variantPrice['variants'] = implode('/', $variants);
                    return $variantPrice;
                });
                $product['product_variant_prices'] = $productVariantPrices->toArray();

                return $product;
            });


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
