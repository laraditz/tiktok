<?php

namespace Laraditz\TikTok\Exceptions;

use Exception;
use Throwable;

class TikTokException extends Exception
{
    public function __construct(
        string $message = 'TikTok Exception.',
        int $code = 0,
        ?Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }
}
