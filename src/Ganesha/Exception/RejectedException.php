<?php

namespace Ackintosh\Ganesha\Exception;

class RejectedException extends \RuntimeException
{
    public function __construct(
        string $message = "",
        int $code = 0,
        ?\Throwable $previous = null,
        private ?string $serviceName = null,
    ) {
        parent::__construct($message, $code, $previous);
    }

    public static function withServiceName(string $serviceName): self
    {
        return new self(sprintf('"%s" is not available', $serviceName), serviceName: $serviceName);
    }

    public function serviceName(): ?string
    {
        return $this->serviceName;
    }
}
