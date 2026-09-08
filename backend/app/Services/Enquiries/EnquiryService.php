<?php

declare(strict_types=1);

namespace PerrymanFinance\Services\Enquiries;

use PerrymanFinance\Http\Exceptions\ValidationException;
use PerrymanFinance\Logging\LoggerInterface;
use PerrymanFinance\Repositories\ContentRepository;
use PerrymanFinance\Repositories\EnquiryRepository;
use PerrymanFinance\Services\Identity\RateLimiter;
use PerrymanFinance\Validation\Validator;

final readonly class EnquiryService
{
    public function __construct(
        private EnquiryRepository $enquiries,
        private ContentRepository $content,
        private Validator $validator,
        private RateLimiter $limiter,
        private EnquiryNotifier $notifier,
        private LoggerInterface $logger,
    ) {
    }

    /** @param array<string, mixed> $input */
    public function submit(array $input, string $ip): void
    {
        $this->limiter->hit('enquiries', $ip, 5, 900);
        if (($input['website'] ?? '') !== '') {
            return;
        }
        foreach ($input as $key => $value) {
            if (is_string($value)) {
                $input[$key] = trim($value);
            }
        }
        $data = $this->validator->validate($input, [
            'name' => ['required', 'string', 'max:160'],
            'email' => ['required', 'string', 'email', 'max:254'],
            'phone' => ['string', 'max:40'],
            'enquiry_type' => ['required', 'string', 'max:80'],
            'subject' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string', 'max:5000'],
            'source_page' => ['string', 'max:500'],
            'consent' => ['required', 'boolean'],
        ]);
        if (!in_array($data['enquiry_type'], ['general', 'consultation', 'investment'], true)) {
            throw new ValidationException(['enquiry_type' => ['Choose a supported enquiry type.']]);
        }
        if ($data['consent'] !== true) {
            throw new ValidationException(['consent' => ['Consent is required.']]);
        }
        $settings = array_column($this->content->settings(true), 'value', 'setting_key');
        if (!is_string($settings['enquiry_consent'] ?? null) || trim($settings['enquiry_consent']) === '' || $this->content->legal('privacy-policy', true) === null) {
            throw new ValidationException(['consent' => ['The enquiry form is temporarily unavailable.']]);
        }
        unset($data['consent']);
        $now = gmdate('Y-m-d H:i:s');
        $hex = bin2hex(random_bytes(16));
        $uuid = substr($hex, 0, 8) . '-' . substr($hex, 8, 4) . '-4' . substr($hex, 13, 3) . '-a' . substr($hex, 17, 3) . '-' . substr($hex, 20);
        $this->enquiries->create([
            ...$data, 'phone' => $data['phone'] ?? null, 'source_page' => $data['source_page'] ?? null,
            'uuid' => $uuid, 'consent_at' => $now, 'created_at' => $now, 'updated_at' => $now,
        ]);
        try {
            $this->notifier->notify();
        } catch (\Throwable) {
            $this->logger->log('warning', 'Enquiry saved; notification failed.');
        }
    }
}
