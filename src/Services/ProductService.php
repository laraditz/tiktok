<?php

namespace Laraditz\TikTok\Services;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Laraditz\TikTok\Models\TiktokProduct;
use Laraditz\TikTok\Models\TiktokRequest;
use Laraditz\TikTok\Models\TiktokProductSku;

class ProductService extends BaseService
{
    public function afterListRequest(TiktokRequest $request, array $result = []): void
    {
        $code = data_get($result, 'code');
        $count = data_get($result, 'data.total_count');
        $products = data_get($result, 'data.products');
        $shopId = $request->shop_id;

        if ($products && is_array($products) && $count > 0) {
            foreach ($products as $product) {
                $this->addProduct($shopId, $product);
            }
        }
    }

    public function afterGetRequest(TiktokRequest $request, array $result = []): void
    {
        $code = data_get($result, 'code');
        $product = data_get($result, 'data');
        $shopId = $request->shop_id;

        if ($code === 0 && $product) {
            $this->addProduct($shopId, $product);
        }
    }

    private function addProduct(string $shopId, array $product)
    {
        $productId = data_get($product, 'id');
        $skus = data_get($product, 'skus');

        $data = Arr::only($product, [
            'title',
            'status',
            'has_draft',
            'is_not_for_sale',
            'sales_regions',
            'audit',
            'create_time',
            'update_time'
        ]);

        DB::beginTransaction();

        try {
            $product = TiktokProduct::updateOrCreate([
                'id' => $productId,
                'shop_id' => $shopId
            ], $data);

            if ($product && $skus && is_array($skus) && count($skus) > 0) {
                $this->addProductSku($product, $skus);
            }

            DB::commit();
        } catch (\Throwable $th) {
            //throw $th;
            DB::rollBack();
        }
    }

    private function addProductSku(TiktokProduct $product, array $skus)
    {
        foreach ($skus as $sku) {
            $id = data_get($sku, 'id');

            $data = Arr::only($sku, [
                'seller_sku',
                'inventory',
                'price',
                'status_info',
            ]);

            $product = TiktokProductSku::updateOrCreate([
                'id' => $id,
                'product_id' => $product->id
            ], $data);
        }

    }
}