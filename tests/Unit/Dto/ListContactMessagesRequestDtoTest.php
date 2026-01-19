<?php

declare(strict_types=1);

namespace App\Tests\Unit\Dto;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;
use App\Dto\ListContactMessagesRequestDto;

final class ListContactMessagesRequestDtoTest extends TestCase
{
    public function testLimitMustBePositive(): void
    {
        $validator = Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->getValidator();

        $dto = new ListContactMessagesRequestDto();
        $dto->limit = 0;

        $violations = $validator->validate($dto);

        self::assertGreaterThan(0, $violations->count());
    }

    public function testOffsetMustBePositiveOrZero(): void
    {
        $validator = Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->getValidator();

        $dto = new ListContactMessagesRequestDto();
        $dto->offset = -1;

        $violations = $validator->validate($dto);

        self::assertGreaterThan(0, $violations->count());
    }
}
