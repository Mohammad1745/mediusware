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
     * @param object $request
     * @return array
     */
    public function index (object $request): array
    {
        try {
            dump($request->query());
            $variants = Variant::all();
            $products = $this->_getFilterProducts($request);

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

    /**
     * @param object $request
     * @return mixed
     */
    private function _getFilterProducts (object $request)
    {
        $products = Product::query();
        //filter by name
        if ($request->query('title')) {
            $products = $products->where('title', 'like', "%".$request->query('title')."%");
        }
        //filter by date
        if ($request->query('date')) {
            $products = $products->whereDate('created_at', $request->query('date'));
        }
        $products = $products->get();
        $products->map(function ($product) use ($request) {
            $productVariantPrices = ProductVariantPrice::where(['product_id' => $product['id']]);
            //filter by price - starts
            if ($request->query('price_from')){
                $productVariantPrices = $productVariantPrices->where('price', '>=', $request->query('price_from'));
            }
            if ($request->query('price_to')){
                $productVariantPrices = $productVariantPrices->where('price', '<=', $request->query('price_to'));
            }
            //filter by price - ends
            $productVariantPrices = $productVariantPrices->get();
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

        return $products;
    }
}
