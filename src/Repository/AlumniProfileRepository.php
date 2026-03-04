<?php

namespace App\Repository;

use App\Entity\AlumniProfile;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class AlumniProfileRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AlumniProfile::class);
    }

    // 💡 Find profile by user
    public function findByUser(User $user): ?AlumniProfile
    {
        return $this->findOneBy(['user' => $user]);
    }

    // 💡 Find profiles by graduation year
    public function findByGraduationYear(int $year): array
    {
        return $this->createQueryBuilder('p')
            ->join('p.user', 'u')
            ->where('p.graduationYear = :year')
            ->andWhere('u.isActive = :active')
            ->setParameter('year', $year)
            ->setParameter('active', true)
            ->orderBy('u.lastName', 'ASC')
            ->getQuery()
            ->getResult();
    }

    // 💡 Find profiles by company
    public function findByCompany(string $company): array
    {
        return $this->createQueryBuilder('p')
            ->join('p.user', 'u')
            ->where('p.company LIKE :company')
            ->andWhere('u.isActive = :active')
            ->setParameter('company', '%' . $company . '%')
            ->setParameter('active', true)
            ->getQuery()
            ->getResult();
    }
}