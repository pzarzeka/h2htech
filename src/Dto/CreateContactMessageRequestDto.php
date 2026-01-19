<?php

declare(strict_types=1);

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

final class CreateContactMessageRequestDto
{
    #[Assert\NotBlank(message: 'Full name is required')]
    #[Assert\Length(
        max: 255,
        maxMessage: 'Full name cannot be longer than {{ limit }} characters'
    )]
    public ?string $fullName;

    #[Assert\NotBlank(message: 'Email is required')]
    #[Assert\Email(message: 'Email is not valid')]
    #[Assert\Length(
        max: 255,
        maxMessage: 'Full name cannot be longer than {{ limit }} characters'
    )]
    public ?string $email;

    #[Assert\NotBlank(message: 'Message is required')]
    #[Assert\Length(
        max: 5000,
        maxMessage: 'Message is too long'
    )]
    public ?string $message;

    #[Assert\NotBlank(message: 'Consent is required')]
    #[Assert\IsTrue(message: 'Consent must be accepted')]
    public ?bool $consent;
}
