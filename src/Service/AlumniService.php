<?php

namespace App\Service;

use App\DTO\UpdateProfileRequest;
use App\Entity\AlumniProfile;
use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;

class AlumniService
{
    public function __construct(
        private EntityManagerInterface $em,
        private UserRepository $userRepository
    ) {}

    // 💡 Update profile — all logic in one place
    public function updateProfile(User $user, UpdateProfileRequest $dto, array $data): void
    {
        $profile = $user->getProfile();

        if (isset($data['firstName'])) $user->setFirstName($dto->firstName);
        if (isset($data['lastName'])) $user->setLastName($dto->lastName);
        if (isset($data['graduationYear'])) $profile->setGraduationYear($dto->graduationYear);
        if (isset($data['program'])) $profile->setProgram($dto->program);
        if (isset($data['company'])) $profile->setCompany($dto->company);
        if (isset($data['jobTitle'])) $profile->setJobTitle($dto->jobTitle);
        if (isset($data['location'])) $profile->setLocation($dto->location);
        if (isset($data['bio'])) $profile->setBio($dto->bio);
        if (isset($data['linkedinUrl'])) $profile->setLinkedinUrl($dto->linkedinUrl);

        $this->em->flush();
    }

    // 💡 Format profile data — moved out of controller
    public function formatProfile(User $user, ?AlumniProfile $profile): array
    {
        return [
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'fullName' => $user->getFullName(),
            'firstName' => $user->getFirstName(),
            'lastName' => $user->getLastName(),
            'graduationYear' => $profile?->getGraduationYear(),
            'program' => $profile?->getProgram(),
            'company' => $profile?->getCompany(),
            'jobTitle' => $profile?->getJobTitle(),
            'location' => $profile?->getLocation(),
            'bio' => $profile?->getBio(),
            'linkedinUrl' => $profile?->getLinkedinUrl(),
            'memberSince' => $user->getCreatedAt()->format('Y-m-d')
        ];
    }

    // 💡 Get paginated alumni
    public function getPaginatedAlumni(int $page, int $limit, ?string $search): array
    {
        return $this->userRepository->findPaginatedAlumni($page, $limit, $search);
    }
}