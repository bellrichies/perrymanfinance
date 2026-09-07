<?php

declare(strict_types=1);

namespace Tests\Unit\Validation;

use PerrymanFinance\Http\Exceptions\ValidationException;
use PerrymanFinance\Validation\Validator;
use PHPUnit\Framework\TestCase;

final class ValidatorTest extends TestCase
{
    public function testValidDataIsReturnedAndUnknownFieldsAreExcluded(): void
    {
        $result = (new Validator())->validate(
            ['email' => 'person@example.com', 'ignored' => true],
            ['email' => ['required', 'email']],
        );
        self::assertSame(['email' => 'person@example.com'], $result);
    }
    public function testInvalidDataProducesFieldErrors(): void
    {
        try {
            (new Validator())->validate(['email' => 'bad'], ['email' => ['required', 'email']]);
        } catch (ValidationException $exception) {
            self::assertArrayHasKey('email', $exception->fields);
            return;
        }
        self::fail('ValidationException was not thrown.');
    }
}
