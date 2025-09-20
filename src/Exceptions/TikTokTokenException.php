<?php

namespace Laraditz\TikTok\Exceptions;

use Exception;
use Throwable;

class TikTokTokenException extends Exception
{
    public function __construct(
        string $message = 'Access token created error.',
        int $code = 0,
        ?Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }
}
