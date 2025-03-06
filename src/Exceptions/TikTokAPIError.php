<?php

namespace Laraditz\TikTok\Exceptions;

use Exception;
use Throwable;

class TikTokAPIError extends Exception
{
    protected array $result = [];

    protected ?string $requestId = null;

    protected ?string $messageCode = null;

    public function __construct(
        array $result = [],
        string $message = 'API Error',
        int $code = 0,
        ?Throwable $previous = null
    ) {
        $this->result = $result;
        $this->requestId = data_get($this->result, 'request_id');
        $this->messageCode = data_get($this->result, 'code');
        $message = ($this->messageCode ? $this->messageCode . ': ' : '') . data_get($this->result, 'message') ?? $message;

        parent::__construct($message, $code, $previous);
    }

    public function getResult()
    {
        return $this->result;
    }

    public function getMessageCode()
    {
        return $this->messageCode;
    }


    public function getRequestId()
    {
        return $this->requestId;
    }
}
