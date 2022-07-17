<?php

namespace App\Http\Services;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ProductVariantPrice;
use App\Models\Variant;
use Illuminate\Database\Eloquent\Collection;

class ProductService
{
    use ResponseService;

    /**
     * @var int
     */
    private $perPage;

    /**
     *
     */
    function __construct ()
    {
        $this->perPage = 10;
    }

    /**
     * @param object $request
     * @return array
     */
    public function index (object $request): array
    {
        try {
            $products = $this->_getFilteredProducts($request);
            $variants = $this->_getVariants();
            $data = [
                'products' => $products,
                'variants' => $variants->toArray()
            ];

            return $this->response($data)->success();

        } catch (\Exception $exception) {
            dd($exception);

            return $this->response()->error($exception->getMessage());
        }
    }

    private function _getVariants ()
    {
        $variants = Variant::all();
        $variants->map(function ($variant) {
            $variant['items'] = ProductVariant::where(['variant_id' => $variant['id']])
                ->distinct('variant')
                ->pluck('variant');
            return $variant;
        });
        return $variants;
    }

    /**
     * @param object $request
     * @return mixed
     */
    private function _getFilteredProducts (object $request)
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
        $products = $products->paginate($this->perPage)
            ->appends($request->query());
        return $this->_mapVariantPrices($products, $request);
    }

    /**
     * @param $products
     * @param object $request
     * @return mixed
     */
    private function _mapVariantPrices($products, object $request)
    {
        $products->map(function ($product) use ($request) {
            $productVariantPrices = ProductVariantPrice::where(['product_id' => $product['id']]);
            //filter by price
            if ($request->query('price_from')){
                $productVariantPrices = $productVariantPrices->where('price', '>=', $request->query('price_from'));
            }
            if ($request->query('price_to')){
                $productVariantPrices = $productVariantPrices->where('price', '<=', $request->query('price_to'));
            }
            $productVariantPrices = $productVariantPrices->get();
            $productVariantPrices = $this->_mapVariants($productVariantPrices, $request);
            $product['product_variant_prices'] = $productVariantPrices->toArray();

            return $product;
        });
//        $products = $products->filter(function ($product) {
//            return count($product->product_variant_prices)>0;
//        });

        return $products;
    }

    /**
     * @param Collection $productVariantPrices
     * @param object $request
     * @return Collection
     */
    private function _mapVariants(Collection $productVariantPrices, object $request): Collection
    {
        $productVariantPrices->map(function ($variantPrice) {
            $variants = ProductVariant::where(['id' => $variantPrice['product_variant_one']])
                ->orWhere(['id' => $variantPrice['product_variant_two']])
                ->orWhere(['id' => $variantPrice['product_variant_three']])
                ->pluck('variant')
                ->toArray();
            $variantPrice['variants'] = implode('/', $variants);
            return $variantPrice;
        });

        if ($request->query('variant')) {
            $productVariantPrices =  $productVariantPrices->filter(function ($item) use ($request) {
                return str_contains($item['variants'], $request->query('variant'));
            });
        }
        return $productVariantPrices;
    }
}
