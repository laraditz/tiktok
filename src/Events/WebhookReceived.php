<?php

namespace Laraditz\TikTok\Events;

use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Events\Dispatchable;

class WebhookReceived
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public string $eventType,
        public array $data,
    ) {

    }
}
