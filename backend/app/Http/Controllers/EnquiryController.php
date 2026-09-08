<?php

declare(strict_types=1);

namespace PerrymanFinance\Http\Controllers;

use PerrymanFinance\Http\ApiResponseFactory;
use PerrymanFinance\Http\Request;
use PerrymanFinance\Http\Response;
use PerrymanFinance\Services\Enquiries\EnquiryService;

final readonly class EnquiryController
{
    public function __construct(private EnquiryService $enquiries, private ApiResponseFactory $responses)
    {
    }

    public function create(Request $request): Response
    {
        $this->enquiries->submit($request->body(), (string) $request->attribute('client_ip', 'unknown'));
        return $this->responses->success(null, [], 'Thank you. Your enquiry has been received.', 202);
    }
}
