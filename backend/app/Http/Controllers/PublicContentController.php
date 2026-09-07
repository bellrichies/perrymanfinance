<?php

declare(strict_types=1);

namespace PerrymanFinance\Http\Controllers;

use PerrymanFinance\Http\ApiResponseFactory;
use PerrymanFinance\Http\Request;
use PerrymanFinance\Http\Response;
use PerrymanFinance\Services\Content\CmsService;

final readonly class PublicContentController
{
    public function __construct(private CmsService $cms, private ApiResponseFactory $responses)
    {
    }

    public function page(Request $request): Response
    {
        return $this->responses->success($this->cms->page((string) $request->route('slug'), true));
    }

    public function legal(Request $request): Response
    {
        return $this->responses->success($this->cms->legal((string) $request->route('slug'), true));
    }

    public function settings(Request $request): Response
    {
        $settings = [];
        foreach ($this->cms->settings(true) as $setting) {
            $settings[(string) $setting['setting_key']] = $setting['value'];
        }
        return $this->responses->success($settings);
    }
}
