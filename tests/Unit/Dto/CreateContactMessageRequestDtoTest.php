<?php

declare(strict_types=1);

namespace App\Tests\Unit\Dto;

use App\Dto\CreateContactMessageRequestDto;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;

class CreateContactMessageRequestDtoTest extends TestCase
{
    public function testFullNameMustCannotBeNull(): void
    {
        $validator = Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->getValidator();

        $dto = new CreateContactMessageRequestDto();
        $dto->fullName = null;

        $violations = $validator->validate($dto);

        self::assertEquals(4, $violations->count());
        self::assertGreaterThan(0, $violations->count());
    }

    public function testConsentMustBeTrue(): void
    {
        $validator = Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->getValidator();

        $dto = new CreateContactMessageRequestDto();
        $dto->fullName = 'Test Full Name';
        $dto->email = 'test@test.com';
        $dto->message = 'Test Message';
        $dto->consent = false;

        $violations = $validator->validate($dto);

        self::assertEquals(2, $violations->count());
    }

    public function testEmail(): void
    {
        $validator = Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->getValidator();

        $dto = new CreateContactMessageRequestDto();
        $dto->fullName = 'Test Full Name';
        $dto->email = 'this is a email';
        $dto->message = 'Test Message';
        $dto->consent = true;

        $violations = $validator->validate($dto);

        self::assertEquals(1, $violations->count());
    }
}
