<?php

declare(strict_types=1);

namespace PerrymanFinance\Repositories;

final class EnquiryRepository extends AbstractRepository
{
    /** @param array<string, mixed> $record */
    public function create(array $record): void
    {
        $this->execute(
            'INSERT INTO enquiries (uuid,name,email,phone,enquiry_type,subject,message,source_page,consent_at,status,created_at,updated_at) '
            . "VALUES (:uuid,:name,:email,:phone,:enquiry_type,:subject,:message,:source_page,:consent_at,'new',:created_at,:updated_at)",
            $record,
        );
    }
}
