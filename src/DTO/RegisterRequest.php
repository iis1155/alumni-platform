<?php

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class RegisterRequest
{
    // 🔒 Security: email format + max length to prevent overflow attacks
    #[Assert\NotBlank(message: 'Email is required')]
    #[Assert\Email(message: 'Invalid email format')]
    #[Assert\Length(max: 180, maxMessage: 'Email cannot exceed 180 characters')]
    public string $email = '';

    // 🔒 Security: enforce strong password rules
    #[Assert\NotBlank(message: 'Password is required')]
    #[Assert\Length(
        min: 8,
        max: 72,  // 🔒 bcrypt silently truncates at 72 chars — be explicit
        minMessage: 'Password must be at least 8 characters',
        maxMessage: 'Password cannot exceed 72 characters'
    )]
    #[Assert\Regex(
        pattern: '/[A-Z]/',
        message: 'Password must contain at least one uppercase letter'
    )]
    #[Assert\Regex(
        pattern: '/[0-9]/',
        message: 'Password must contain at least one number'
    )]
    public string $password = '';

    #[Assert\NotBlank(message: 'First name is required')]
    #[Assert\Length(
        min: 2,
        max: 100,
        minMessage: 'First name must be at least 2 characters',
        maxMessage: 'First name cannot exceed 100 characters'
    )]
    public string $firstName = '';

    #[Assert\NotBlank(message: 'Last name is required')]
    #[Assert\Length(
        min: 2,
        max: 100,
        minMessage: 'Last name must be at least 2 characters',
        maxMessage: 'Last name cannot exceed 100 characters'
    )]
    public string $lastName = '';
}