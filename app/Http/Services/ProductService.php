<?php

namespace App\Http\Services;

use App\Http\Services\Traits\Response;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Models\ProductVariantPrice;
use App\Models\Variant;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProductService
{
    use Response;

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

    /**
     * @param Product $product
     * @return array
     */
    public function getProductEditableData (Product $product): array
    {
        try {

            $productVariantPrices = ProductVariantPrice::where(['product_id' => $product['id']])->get();
            $productVariantPrices->map(function ($variantPrice) {
                $variants = ProductVariant::where(['id' => $variantPrice['product_variant_one']])
                    ->orWhere(['id' => $variantPrice['product_variant_two']])
                    ->orWhere(['id' => $variantPrice['product_variant_three']])
                    ->pluck('variant')
                    ->toArray();
                $variantPrice['variant_title'] = implode('/', $variants).'/';
                return $variantPrice;
            });
            $productVariants = Variant::all();
            $productVariants->map(function ($variant) use ($product){
                $variant['variant']  = ProductVariant::where([
                    'product_id' => $product['id'],
                    'variant_id' => $variant->id,
                ])->pluck('variant');
                return $variant;
            });
            $productVariants = $productVariants->filter(function ($variant) {
                return count($variant['variant']) > 0;
            });


            $product['product_variants'] = $productVariants->toArray();
            $product['product_variant_prices'] = $productVariantPrices->toArray();

            $data = [
                'product' => collect([$product]),
                'variants' => Variant::all()
            ];

            return $this->response($data)->success();

        } catch (\Exception $exception) {
            dd($exception);

            return $this->response()->error($exception->getMessage());
        }
    }

    /**
     * @param array $request
     * @return array
     */
    public function saveProduct (array $request): array
    {
        try {
            DB::beginTransaction();
            $product = Product::create($this->_formatProductData($request));
            $this->_saveProductImage($request, $product['id']);
            $this->_saveProductVariants($request, $product['id']);
            $this->_saveProductVariantPrices($request, $product['id']);

            DB::commit();

            return $this->response()->success('Product Saved Successfully!');
        } catch (\Exception $exception) {
            DB::rollBack();

            return $this->response()->error($exception->getMessage());
        }
    }

    /**
     * @param array $request
     * @param Product $product
     * @return array
     */
    public function updateProduct (array $request, Product $product): array
    {
        try {
            DB::beginTransaction();
            $product->update($this->_formatProductData($request));
            $this->_saveProductImage($request, $product['id']);
            ProductVariant::where('product_id', $product['id'])->delete();
            ProductVariantPrice::where('product_id', $product['id'])->delete();
            $this->_saveProductVariants($request, $product['id']);
            $this->_saveProductVariantPrices($request, $product['id']);

            DB::commit();

            return $this->response()->success('Product Saved Successfully!');
        } catch (\Exception $exception) {
            DB::rollBack();

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
        //filter by variant
        if ($request->query('variant')) {
            $productIds = ProductVariant::where(['variant'=> $request->query('variant')])->pluck('product_id')->toArray();
            $products = $products->whereIn('id', $productIds);
        }
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

    /**
     * @param array $request
     * @param int $productId
     * @return void
     */
    private function _saveProductVariants(array $request, int $productId)
    {
        foreach ($request['product_variant'] as $productVariant) {
            foreach ($productVariant['tags'] as $tag) {
                ProductVariant::create($this->_formatProductVariantData($productId, $productVariant['option'], $tag));
            }
        }
    }

    /**
     * @param array $request
     * @param int $productId
     * @return void
     */
    private function _saveProductVariantPrices(array $request, int $productId)
    {
        foreach ($request['product_variant_prices'] as $variantPrice) {
            $titles = explode('/', $variantPrice['title']);
            $titles = array_filter($titles, function($title) {
                return $title != "";
            });
            $titleIds = ProductVariant::where(['product_id' => $productId])
                ->whereIn('variant' , $titles)
                ->get('id')
                ->toArray();
            ProductVariantPrice::create($this->_formatProductVariantPriceData($variantPrice, $productId, $titleIds));
        }
    }

    /**
     * @param array $request
     * @param int $productId
     * @return void
     */
    private function _saveProductImage (array $request, int $productId)
    {
        ProductImage::where('product_id', $productId)->delete();
        foreach ($request['product_image'] as $image) {
            ProductImage::create($this->_formatProductImageData($image, $productId));
        }
    }

    /**
     * @param array $request
     * @return array
     */
    private function _formatProductData(array $request): array
    {
        return [
            'title' => $request['title'],
            'sku' => $request['sku'],
            'description' => $request['description'],
        ];
    }

    /**
     * @param string $image
     * @param int $productId
     * @return array
     */
    private function _formatProductImageData(string $image, int $productId): array
    {
        return [
            'product_id' => $productId,
            'file_path' => $image,
        ];
    }

    /**
     * @param int $productId
     * @param int $variantId
     * @param string $tag
     * @return array
     */
    private function _formatProductVariantData(int $productId, int $variantId, string $tag): array
    {
        return [
            'variant' => $tag,
            'variant_id' => $variantId,
            'product_id' => $productId
        ];
    }

    /**
     * @param array $variantPrice
     * @param int $productId
     * @param array $titleIds
     * @return array
     */
    private function _formatProductVariantPriceData(array $variantPrice, int $productId, array $titleIds): array
    {
        return [
            'product_variant_one' => $titleIds[0]['id'],
            'product_variant_two' => count($titleIds)>1 ? $titleIds[1]['id'] : null,
            'product_variant_three' => count($titleIds)>2 ? $titleIds[2]['id'] : null,
            'price' => $variantPrice['price'],
            'stock' => $variantPrice['stock'],
            'product_id' => $productId
        ];
    }
}
