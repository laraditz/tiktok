<?php

namespace Laraditz\TikTok\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Laraditz\TikTok\Skeleton\SkeletonClass
 */
class TikTok extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'tiktok';
    }
}
