<?php

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class UpdateProfileRequest
{
    #[Assert\Length(max: 100, maxMessage: 'First name cannot exceed 100 characters')]
    public ?string $firstName = null;

    #[Assert\Length(max: 100, maxMessage: 'Last name cannot exceed 100 characters')]
    public ?string $lastName = null;

    #[Assert\Range(
        min: 1950,
        max: 2030,
        notInRangeMessage: 'Graduation year must be between 1950 and 2030'
    )]
    public ?int $graduationYear = null;

    #[Assert\Length(max: 100, maxMessage: 'Program cannot exceed 100 characters')]
    public ?string $program = null;

    #[Assert\Length(max: 150, maxMessage: 'Company cannot exceed 150 characters')]
    public ?string $company = null;

    #[Assert\Length(max: 150, maxMessage: 'Job title cannot exceed 150 characters')]
    public ?string $jobTitle = null;

    #[Assert\Length(max: 150, maxMessage: 'Location cannot exceed 150 characters')]
    public ?string $location = null;

    #[Assert\Length(max: 1000, maxMessage: 'Bio cannot exceed 1000 characters')]
    public ?string $bio = null;

    // 🔒 Security: validate URL format
    #[Assert\Url(message: 'LinkedIn URL must be a valid URL')]
    #[Assert\Length(max: 255, maxMessage: 'LinkedIn URL cannot exceed 255 characters')]
    public ?string $linkedinUrl = null;
}