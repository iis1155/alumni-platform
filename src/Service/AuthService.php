<?php

namespace App\Service;

use App\DTO\RegisterRequest;
use App\Entity\AlumniProfile;
use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AuthService
{
    public function __construct(
        private EntityManagerInterface $em,
        private UserRepository $userRepository,
        private UserPasswordHasherInterface $passwordHasher
        )
    {
    }

    // 💡 All registration logic in one place
    public function register(RegisterRequest $dto): User
    {
        $user = new User();
        $user->setEmail($dto->email);
        $user->setFirstName($dto->firstName);
        $user->setLastName($dto->lastName);
        $user->setPassword(
            $this->passwordHasher->hashPassword($user, $dto->password)
        );

        // Create empty profile automatically
        $profile = new AlumniProfile();
        $profile->setUser($user);

        $this->em->persist($user);
        $this->em->persist($profile);
        $this->em->flush();

        return $user;
    }

    // 💡 Verify credentials — returns user or null
    public function verifyCredentials(string $email, string $password): ?User
    {
        $user = $this->userRepository->findByEmail($email);

        // 🔒 return null for wrong email OR wrong password
        // never reveal which one is wrong
        if (!$user || !$this->passwordHasher->isPasswordValid($user, $password)) {
            return null;
        }

        return $user;
    }

    public function isEmailTaken(string $email): bool
    {
        return $this->userRepository->findByEmail($email) !== null;
    }
}