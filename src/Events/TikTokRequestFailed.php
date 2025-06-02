<?php

namespace Laraditz\TikTok\Events;

use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Events\Dispatchable;

class TikTokRequestFailed
{
    use Dispatchable, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public string $fqcn,
        public string $methodName,
        public ?string $message = null,
    ) {
        //
    }

}
