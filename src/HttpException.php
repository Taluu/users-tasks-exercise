<?php
namespace Test\One;

use RuntimeException;

class HttpException extends RuntimeException
{
    /** @var int http status code */
    private $statusCode;

    /** @var string[] Header name => Header value */
    private $headers;

    public function __construct(int $statusCode = 500, $message = null, array $headers = [], Throwable $previous = null)
    {
        $this->headers = $headers;
        $this->statusCode = $statusCode;

        parent::__construct($message, 0, $previous);
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }
}
