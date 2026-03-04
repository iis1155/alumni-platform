<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

class UserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    // 💡 Find user by email — used in login, register duplicate check
    public function findByEmail(string $email): ?User
    {
        return $this->findOneBy(['email' => strtolower(trim($email))]);
    }

    // 💡 Find active user by email
    public function findActiveByEmail(string $email): ?User
    {
        return $this->createQueryBuilder('u')
            ->where('u.email = :email')
            ->andWhere('u.isActive = :active')
            ->setParameter('email', strtolower(trim($email)))
            ->setParameter('active', true)
            ->getQuery()
            ->getOneOrNullResult();
    }

    // 💡 Get paginated alumni list with optional search
    public function findPaginatedAlumni(int $page, int $limit, ?string $search = null): array
    {
        $qb = $this->createActiveQueryBuilder();

        if ($search) {
            $this->applySearch($qb, $search);
        }

        $total = (clone $qb)
            ->select('COUNT(u.id)')
            ->getQuery()
            ->getSingleScalarResult();

        $users = $qb
            ->select('u')
            ->orderBy('u.createdAt', 'DESC')
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        return [
            'data' => $users,
            'total' => (int)$total,
            'pages' => (int)ceil($total / $limit)
        ];
    }

    // 💡 Find active user by ID
    public function findActiveById(int $id): ?User
    {
        return $this->createQueryBuilder('u')
            ->where('u.id = :id')
            ->andWhere('u.isActive = :active')
            ->setParameter('id', $id)
            ->setParameter('active', true)
            ->getQuery()
            ->getOneOrNullResult();
    }

    // 💡 Private helpers — reusable query building blocks
    private function createActiveQueryBuilder(): QueryBuilder
    {
        return $this->createQueryBuilder('u')
            ->leftJoin('u.profile', 'p')
            ->where('u.isActive = :active')
            ->setParameter('active', true);
    }

    private function applySearch(QueryBuilder $qb, string $search): void
    {
        $qb->andWhere(
            'u.firstName LIKE :search 
            OR u.lastName LIKE :search 
            OR p.company LIKE :search
            OR p.jobTitle LIKE :search'
        )
            ->setParameter('search', '%' . $search . '%');
    }

    // 💡 Admin only — get ALL users including inactive
    public function findPaginatedAll(int $page, int $limit): array
    {
        $qb = $this->createQueryBuilder('u')
            ->orderBy('u.createdAt', 'DESC');

        $total = (clone $qb)
            ->select('COUNT(u.id)')
            ->getQuery()
            ->getSingleScalarResult();

        $users = $qb
            ->select('u')
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        return [
            'data' => $users,
            'total' => (int)$total,
            'pages' => (int)ceil($total / $limit)
        ];
    }
}