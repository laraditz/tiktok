<?php

namespace Laraditz\TikTok\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Laraditz\TikTok\Models\TiktokProduct;
use Laraditz\TikTok\Models\TiktokProductSku;
use Laraditz\TikTok\Models\TiktokRequest;

class RemoveMissingProducts implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;


    public function __construct(public TiktokRequest $request, public ?array $result = [])
    {
        //
    }

    public function handle()
    {
        $request = $this->request->request;
        $skus = data_get($request, 'skus');

        if ($skus && is_array($skus)) {
            $productIds = [];
            $skuIds = collect($skus)->pluck('id')->unique()->toArray();

            $skus = TiktokProductSku::whereIn('id', $skuIds);

            foreach ($skus->cursor() as $sku) {
                $productIds[] = $sku->product_id;
            }

            $productIds = array_unique($productIds);

            // Make sure to only delete products that belong to the requested shop
            $products = TiktokProduct::query()
                ->whereIn('id', $productIds)
                ->where('shop_id', $this->request?->shop_id);

            foreach ($products->cursor() as $product) {
                DB::transaction(function () use ($product) {
                    $product->skus()->delete();
                    $product->delete();
                });
            }
        }
    }
}