<?php

namespace Laraditz\TikTok\Events;

use Illuminate\Support\Collection;
use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Events\Dispatchable;

class SellerAuthorized
{
    use Dispatchable, SerializesModels;

    /**
     * @param Collection $shops TiktokShop models, each with its accessToken relation loaded.
     */
    public function __construct(
        public readonly string $openId,
        public readonly Collection $shops,
    ) {

    }
}
