<?php

declare(strict_types=1);

namespace PerrymanFinance\Http\Controllers;

use PerrymanFinance\Http\ApiResponseFactory;
use PerrymanFinance\Http\Request;
use PerrymanFinance\Http\Response;
use PerrymanFinance\Services\Seo\SeoService;

final readonly class SeoController
{
    public function __construct(private SeoService $seo, private ApiResponseFactory $responses)
    {
    }

    public function sitemap(Request $request): Response
    {
        return new Response(
            $this->seo->sitemapXml(),
            200,
            ['Content-Type' => 'application/xml; charset=utf-8'],
        );
    }

    public function robots(Request $request): Response
    {
        return new Response(
            $this->seo->robotsTxt(),
            200,
            ['Content-Type' => 'text/plain; charset=utf-8'],
        );
    }

    public function sitemapIndex(Request $request): Response
    {
        return $this->responses->success($this->seo->sitemapEntries());
    }

    public function redirect(Request $request): Response
    {
        return $this->responses->success($this->seo->redirect((string) $request->input('path', '')));
    }
}
