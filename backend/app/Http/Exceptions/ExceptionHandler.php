<?php

declare(strict_types=1);

namespace PerrymanFinance\Http\Exceptions;

use PerrymanFinance\Config\Config;
use PerrymanFinance\Http\ApiResponseFactory;
use PerrymanFinance\Http\Request;
use PerrymanFinance\Http\Response;
use PerrymanFinance\Logging\LoggerInterface;
use Throwable;

final readonly class ExceptionHandler
{
    public function __construct(
        private ApiResponseFactory $responses,
        private LoggerInterface $logger,
        private Config $config,
    ) {
    }

    public function render(Throwable $exception, Request $request): Response
    {
        $requestId = $request->attribute('request_id');
        try {
            $this->logger->log('error', 'Request failed.', [
                'request_id' => $requestId,
                'method' => $request->method(),
                'path' => $request->uri(),
                'exception_class' => $exception::class,
                'exception_message' => $exception->getMessage(),
            ]);
        } catch (Throwable) {
            // A logging failure must not replace the safe API response.
        }
        if ($exception instanceof HttpException) {
            $response = $this->responses->error(
                $exception->errorCode,
                $exception->getMessage(),
                $exception->status,
                $exception->fields,
            );
            foreach ($exception->headers as $name => $value) {
                $response = $response->withHeader($name, $value);
            }
        } else {
            $message = $this->config->bool('app.debug') ? $exception->getMessage() : 'An unexpected error occurred.';
            $response = $this->responses->error('INTERNAL_SERVER_ERROR', $message, 500);
        }
        return is_string($requestId) ? $response->withHeader('X-Request-ID', $requestId) : $response;
    }
}
