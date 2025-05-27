<?php

namespace Laraditz\TikTok\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class WebhookReceived
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public string $eventType,
        public array $data,
    ) {

    }
}
