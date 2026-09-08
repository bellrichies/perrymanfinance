<?php

declare(strict_types=1);

namespace PerrymanFinance\Services\Enquiries;

use PerrymanFinance\Config\Config;

class EnquiryNotifier
{
    public function __construct(private readonly Config $config)
    {
    }

    public function notify(): void
    {
        $recipient = $this->config->string('mail.enquiries_to');
        $from = $this->config->string('mail.from');
        if (!filter_var($recipient, FILTER_VALIDATE_EMAIL) || !filter_var($from, FILTER_VALIDATE_EMAIL)) {
            throw new \RuntimeException('Enquiry email is not configured.');
        }
        if (!mail($recipient, 'New PerrymanFinance enquiry', 'A new enquiry has been saved. Review it through your authorized enquiry workflow.', "From: {$from}\r\nContent-Type: text/plain; charset=UTF-8")) {
            throw new \RuntimeException('Enquiry notification failed.');
        }
    }
}
