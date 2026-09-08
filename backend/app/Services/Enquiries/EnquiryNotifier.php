<?php

declare(strict_types=1);

namespace PerrymanFinance\Services\Enquiries;

use PerrymanFinance\Config\Config;
use PHPMailer\PHPMailer\PHPMailer;

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
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host = $this->config->string('mail.host');
        $mail->Port = (int) $this->config->get('mail.port', 587);
        $mail->Username = $this->config->string('mail.username');
        $mail->Password = $this->config->string('mail.password');
        $mail->SMTPAuth = $mail->Username !== '';
        $mail->SMTPSecure = $this->config->string('mail.encryption', 'tls');
        if ($this->config->string('app.env', 'production') === 'production' && !in_array($mail->SMTPSecure, ['tls', 'ssl'], true)) {
            throw new \RuntimeException('Encrypted SMTP is required in production.');
        }
        $mail->Timeout = 10;
        $mail->CharSet = PHPMailer::CHARSET_UTF8;
        $mail->setFrom($from);
        $mail->addAddress($recipient);
        $mail->Subject = 'New PerrymanFinance enquiry';
        $mail->Body = 'A new enquiry has been saved. Review it through your authorized enquiry workflow.';
        $mail->send();
    }
}
