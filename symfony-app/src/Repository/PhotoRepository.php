<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Photo;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class PhotoRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Photo::class);
    }

    /**
     * @param array<string, string> $filters
     */
    public function findFilteredWithUsers(array $filters = []): array
    {
        $queryBuilder = $this->createQueryBuilder('p')
            ->leftJoin('p.user', 'u')
            ->addSelect('u')
            ->orderBy('p.id', 'ASC');

        if (($filters['location'] ?? '') !== '') {
            $queryBuilder
                ->andWhere('LOWER(p.location) LIKE :location')
                ->setParameter('location', '%' . mb_strtolower($filters['location']) . '%');
        }

        if (($filters['camera'] ?? '') !== '') {
            $queryBuilder
                ->andWhere('LOWER(p.camera) LIKE :camera')
                ->setParameter('camera', '%' . mb_strtolower($filters['camera']) . '%');
        }

        if (($filters['description'] ?? '') !== '') {
            $queryBuilder
                ->andWhere('LOWER(p.description) LIKE :description')
                ->setParameter('description', '%' . mb_strtolower($filters['description']) . '%');
        }

        if (($filters['username'] ?? '') !== '') {
            $queryBuilder
                ->andWhere(
                    'LOWER(u.username) LIKE :username OR LOWER(COALESCE(u.name, \'\')) LIKE :username OR LOWER(COALESCE(u.lastName, \'\')) LIKE :username'
                )
                ->setParameter('username', '%' . mb_strtolower($filters['username']) . '%');
        }

        if (($filters['taken_at'] ?? '') !== '') {
            try {
                $date = new \DateTimeImmutable($filters['taken_at']);
                $dayStart = $date->setTime(0, 0, 0);
                $dayEnd = $dayStart->modify('+1 day');
                $queryBuilder
                    ->andWhere('p.takenAt >= :takenAtStart')
                    ->andWhere('p.takenAt < :takenAtEnd')
                    ->setParameter('takenAtStart', $dayStart)
                    ->setParameter('takenAtEnd', $dayEnd);
            } catch (\Exception) {
                // Ignore invalid date values and show unfiltered results.
            }
        }

        return $queryBuilder->getQuery()->getResult();
    }

    /**
     * @return array<int, string>
     */
    public function findImageUrlsForUser(User $user): array
    {
        $rows = $this->createQueryBuilder('p')
            ->select('p.imageUrl')
            ->where('p.user = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getArrayResult();

        return array_map(static fn (array $row): string => (string) $row['imageUrl'], $rows);
    }
}
