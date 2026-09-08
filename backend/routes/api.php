<?php

declare(strict_types=1);

use PerrymanFinance\Http\Controllers\HealthController;
use PerrymanFinance\Http\Controllers\AdminAuthController;
use PerrymanFinance\Http\Controllers\AdminContentController;
use PerrymanFinance\Http\Controllers\PublicContentController;
use PerrymanFinance\Http\Controllers\InvestmentController;
use PerrymanFinance\Http\Controllers\InsightController;
use PerrymanFinance\Http\Controllers\EnquiryController;
use PerrymanFinance\Http\Controllers\AdminEnquiryController;
use PerrymanFinance\Http\Controllers\SeoController;
use PerrymanFinance\Http\Middleware\AuthMiddleware;
use PerrymanFinance\Http\Middleware\PermissionMiddleware;
use PerrymanFinance\Http\Router;

return static function (Router $router): void {
    $router->get('/sitemap.xml', [SeoController::class, 'sitemap']);
    $router->get('/robots.txt', [SeoController::class, 'robots']);

    $router->group('/api/v1', [], static function (Router $router): void {
        $router->get('/health', [HealthController::class, 'show']);
        $router->post('/enquiries', [EnquiryController::class, 'create']);
        $router->get('/pages/{slug}', [PublicContentController::class, 'page']);
        $router->get('/legal/{slug}', [PublicContentController::class, 'legal']);
        $router->get('/site-settings/public', [PublicContentController::class, 'settings']);
        $router->get('/investments', [InvestmentController::class, 'publicList']);
        $router->get('/investments/{slug}', [InvestmentController::class, 'publicDetail']);
        $router->get('/investment-categories', [InvestmentController::class, 'categories']);
        $router->get('/insights', [InsightController::class, 'publicList']);
        $router->get('/insights/{slug}', [InsightController::class, 'publicDetail']);
        $router->get('/insight-categories', [InsightController::class, 'categories']);
        $router->get('/tags', [InsightController::class, 'tags']);
        $router->get('/faq', [InsightController::class, 'publicFaq']);
        $router->get('/sitemap.xml', [SeoController::class, 'sitemap']);
        $router->get('/robots.txt', [SeoController::class, 'robots']);
        $router->get('/seo/sitemap', [SeoController::class, 'sitemapIndex']);
        $router->get('/seo/redirect', [SeoController::class, 'redirect']);
        $router->group('/admin/auth', [], static function (Router $router): void {
            $router->post('/login', [AdminAuthController::class, 'login']);
            $router->post('/refresh', [AdminAuthController::class, 'refresh']);
            $router->post('/logout', [AdminAuthController::class, 'logout']);
            $router->post('/forgot-password', [AdminAuthController::class, 'forgotPassword']);
            $router->post('/reset-password', [AdminAuthController::class, 'resetPassword']);
            $router->get('/me', [AdminAuthController::class, 'me'], [AuthMiddleware::class]);
        });
        $router->group('/admin', [AuthMiddleware::class], static function (Router $router): void {
            $router->get('/enquiries', [AdminEnquiryController::class, 'index'], [new PermissionMiddleware('enquiries.view')]);
            $router->get('/enquiries/{uuid}', [AdminEnquiryController::class, 'show'], [new PermissionMiddleware('enquiries.view')]);
            $router->patch('/enquiries/{uuid}', [AdminEnquiryController::class, 'update'], [new PermissionMiddleware('enquiries.update')]);
            $router->get('/investments', [InvestmentController::class, 'adminList'], [new PermissionMiddleware('investments.view')]);
            $router->post('/investments', [InvestmentController::class, 'create'], [new PermissionMiddleware('investments.create')]);
            $router->get('/investments/{uuid}', [InvestmentController::class, 'adminDetail'], [new PermissionMiddleware('investments.view')]);
            $router->patch('/investments/{uuid}', [InvestmentController::class, 'update'], [new PermissionMiddleware('investments.update')]);
            $router->delete('/investments/{uuid}', [InvestmentController::class, 'archive'], [new PermissionMiddleware('investments.archive')]);
            $router->post('/investment-categories', [InvestmentController::class, 'createCategory'], [new PermissionMiddleware('investments.create')]);
            $router->patch('/investment-categories/{id}', [InvestmentController::class, 'updateCategory'], [new PermissionMiddleware('investments.update')]);
            $router->delete('/investment-categories/{id}', [InvestmentController::class, 'deleteCategory'], [new PermissionMiddleware('investments.archive')]);
            $router->get('/articles', [InsightController::class, 'adminList'], [new PermissionMiddleware('articles.view')]);
            $router->post('/articles', [InsightController::class, 'create'], [new PermissionMiddleware('articles.create')]);
            $router->get('/articles/{uuid}', [InsightController::class, 'adminDetail'], [new PermissionMiddleware('articles.view')]);
            $router->patch('/articles/{uuid}', [InsightController::class, 'update'], [new PermissionMiddleware('articles.update')]);
            $router->delete('/articles/{uuid}', [InsightController::class, 'archive'], [new PermissionMiddleware('articles.archive')]);
            $router->post('/article-categories', [InsightController::class, 'createCategory'], [new PermissionMiddleware('articles.create')]);
            $router->patch('/article-categories/{id}', [InsightController::class, 'updateCategory'], [new PermissionMiddleware('articles.update')]);
            $router->delete('/article-categories/{id}', [InsightController::class, 'deleteCategory'], [new PermissionMiddleware('articles.archive')]);
            $router->post('/tags', [InsightController::class, 'createTag'], [new PermissionMiddleware('articles.create')]);
            $router->patch('/tags/{id}', [InsightController::class, 'updateTag'], [new PermissionMiddleware('articles.update')]);
            $router->delete('/tags/{id}', [InsightController::class, 'deleteTag'], [new PermissionMiddleware('articles.archive')]);
            $router->get('/faqs', [InsightController::class, 'adminFaqs'], [new PermissionMiddleware('faqs.manage')]);
            $router->post('/faqs', [InsightController::class, 'createFaq'], [new PermissionMiddleware('faqs.manage')]);
            $router->patch('/faqs/{id}', [InsightController::class, 'updateFaq'], [new PermissionMiddleware('faqs.manage')]);
            $router->delete('/faqs/{id}', [InsightController::class, 'deleteFaq'], [new PermissionMiddleware('faqs.manage')]);
            $router->get('/pages', [AdminContentController::class, 'pages'], [new PermissionMiddleware('pages.view')]);
            $router->post('/pages', [AdminContentController::class, 'createPage'], [new PermissionMiddleware('pages.create')]);
            $router->get('/pages/{uuid}', [AdminContentController::class, 'page'], [new PermissionMiddleware('pages.view')]);
            $router->patch('/pages/{uuid}', [AdminContentController::class, 'updatePage'], [new PermissionMiddleware('pages.update')]);
            $router->delete('/pages/{uuid}', [AdminContentController::class, 'deletePage'], [new PermissionMiddleware('pages.archive')]);
            $router->get('/legal-documents', [AdminContentController::class, 'legalDocuments'], [new PermissionMiddleware('legal.manage')]);
            $router->post('/legal-documents', [AdminContentController::class, 'createLegal'], [new PermissionMiddleware('legal.manage')]);
            $router->get('/legal-documents/{uuid}', [AdminContentController::class, 'legal'], [new PermissionMiddleware('legal.manage')]);
            $router->patch('/legal-documents/{uuid}', [AdminContentController::class, 'updateLegal'], [new PermissionMiddleware('legal.manage')]);
            $router->delete('/legal-documents/{uuid}', [AdminContentController::class, 'archiveLegal'], [new PermissionMiddleware('legal.manage')]);
            $router->get('/settings', [AdminContentController::class, 'settings'], [new PermissionMiddleware('settings.manage')]);
            $router->put('/settings/{key}', [AdminContentController::class, 'saveSetting'], [new PermissionMiddleware('settings.manage')]);
            $router->delete('/settings/{key}', [AdminContentController::class, 'deleteSetting'], [new PermissionMiddleware('settings.manage')]);
            $router->get('/media', [AdminContentController::class, 'media'], [new PermissionMiddleware('media.manage')]);
            $router->post('/media', [AdminContentController::class, 'upload'], [new PermissionMiddleware('media.manage')]);
            $router->patch('/media/{uuid}', [AdminContentController::class, 'updateMedia'], [new PermissionMiddleware('media.manage')]);
            $router->delete('/media/{uuid}', [AdminContentController::class, 'deleteMedia'], [new PermissionMiddleware('media.manage')]);
            $router->get('/seo/{type}/{uuid}', [AdminContentController::class, 'seo']);
            $router->put('/seo/{type}/{uuid}', [AdminContentController::class, 'saveSeo']);
            $router->delete('/seo/{type}/{uuid}', [AdminContentController::class, 'deleteSeo']);
        });
    });
};
