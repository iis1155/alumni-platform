<?php

namespace App\Tests\Unit\Service;

use App\DTO\RegisterRequest;
use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\AuthService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AuthServiceTest extends TestCase
{
    private AuthService $authService;
    private MockObject $em;
    private MockObject $userRepository;
    private MockObject $passwordHasher;

    // 💡 setUp runs before every test method
    protected function setUp(): void
    {
        // 💡 Mock = fake object that simulates real behavior
        // We don't want tests to hit the real database
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->userRepository = $this->createMock(UserRepository::class);
        $this->passwordHasher = $this->createMock(UserPasswordHasherInterface::class);

        $this->authService = new AuthService(
            $this->em,
            $this->userRepository,
            $this->passwordHasher
            );
    }

    // 💡 Test method names should describe what they test
    public function testIsEmailTakenReturnsTrueWhenEmailExists(): void
    {
        $user = new User();
        $user->setEmail('existing@alumni.com');

        // Tell the mock: when findByEmail is called → return this user
        $this->userRepository
            ->expects($this->once())
            ->method('findByEmail')
            ->with('existing@alumni.com')
            ->willReturn($user);

        $result = $this->authService->isEmailTaken('existing@alumni.com');

        $this->assertTrue($result);
    }

    public function testIsEmailTakenReturnsFalseWhenEmailNotExists(): void
    {
        // Tell the mock: when findByEmail is called → return null
        $this->userRepository
            ->expects($this->once())
            ->method('findByEmail')
            ->willReturn(null);

        $result = $this->authService->isEmailTaken('new@alumni.com');

        $this->assertFalse($result);
    }

    public function testVerifyCredentialsReturnsNullForWrongPassword(): void
    {
        $user = new User();
        $user->setEmail('test@alumni.com');

        $this->userRepository
            ->method('findByEmail')
            ->willReturn($user);

        // 🔒 Mock password check — returns false (wrong password)
        $this->passwordHasher
            ->method('isPasswordValid')
            ->willReturn(false);

        $result = $this->authService->verifyCredentials('test@alumni.com', 'wrongpassword');

        $this->assertNull($result);
    }

    public function testVerifyCredentialsReturnsNullForWrongEmail(): void
    {
        // Email not found
        $this->userRepository
            ->method('findByEmail')
            ->willReturn(null);

        $result = $this->authService->verifyCredentials('nobody@alumni.com', 'password');

        $this->assertNull($result);
    }

    public function testVerifyCredentialsReturnsUserForValidCredentials(): void
    {
        $user = new User();
        $user->setEmail('test@alumni.com');

        $this->userRepository
            ->method('findByEmail')
            ->willReturn($user);

        // Mock password check — returns true (correct password)
        $this->passwordHasher
            ->method('isPasswordValid')
            ->willReturn(true);

        $result = $this->authService->verifyCredentials('test@alumni.com', 'correctpassword');

        $this->assertSame($user, $result);
        $this->assertEquals('test@alumni.com', $result->getEmail());
    }
}