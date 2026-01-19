<?php

declare(strict_types=1);

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

final class ListContactMessagesRequestDto
{
    #[Assert\Positive]
    #[Assert\LessThanOrEqual(100)]
    public int $limit = 20;

    #[Assert\PositiveOrZero]
    public int $offset = 0;

    public function getOffset(): ?int
    {
        return $this->offset;
    }

    public function getLimit(): ?int
    {
        return $this->limit;
    }
}
