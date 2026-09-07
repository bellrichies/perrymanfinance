<?php

declare(strict_types=1);

namespace PerrymanFinance\Http\Controllers;

use PerrymanFinance\Config\Config;
use PerrymanFinance\Http\ApiResponseFactory;
use PerrymanFinance\Http\Request;
use PerrymanFinance\Http\Response;

final readonly class HealthController
{
    public function __construct(private Config $config, private ApiResponseFactory $responses)
    {
    }

    public function show(Request $request): Response
    {
        return $this->responses->success([
            'status' => 'ok',
            'service' => 'perrymanfinance-api',
            'version' => $this->config->string('app.version', 'dev'),
        ]);
    }
}
