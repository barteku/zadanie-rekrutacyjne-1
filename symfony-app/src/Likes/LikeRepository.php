<?php

declare(strict_types=1);

namespace App\Likes;

use App\Entity\Photo;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\DBAL\LockMode;
use Doctrine\Persistence\ManagerRegistry;

final class LikeRepository extends ServiceEntityRepository implements LikeRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Like::class);
    }

    #[\Override]
    public function toggleLike(Photo $photo, User $user): bool
    {
        $em = $this->getEntityManager();
        $didLike = false;

        $em->wrapInTransaction(function (EntityManagerInterface $entityManager) use ($photo, $user, &$didLike): void {
            $entityManager->lock($photo, LockMode::PESSIMISTIC_WRITE);

            $like = $this->findOneBy([
                'user' => $user,
                'photo' => $photo,
            ]);

            if ($like !== null) {
                $entityManager->remove($like);
                $photo->setLikeCounter(max(0, $photo->getLikeCounter() - 1));
                $didLike = false;
            } else {
                $newLike = new Like();
                $newLike->setUser($user);
                $newLike->setPhoto($photo);
                $entityManager->persist($newLike);
                $photo->setLikeCounter($photo->getLikeCounter() + 1);
                $didLike = true;
            }

            $entityManager->persist($photo);
        });

        return $didLike;
    }

    #[\Override]
    public function findLikedMapForPhotoIds(User $user, array $photoIds): array
    {
        if ($photoIds === []) {
            return [];
        }

        $rows = $this->createQueryBuilder('l')
            ->select('IDENTITY(l.photo) AS photoId')
            ->where('l.user = :user')
            ->andWhere('l.photo IN (:photoIds)')
            ->setParameter('user', $user)
            ->setParameter('photoIds', $photoIds)
            ->getQuery()
            ->getArrayResult();

        $likedMap = [];

        foreach ($rows as $row) {
            $photoId = (int) $row['photoId'];
            $likedMap[$photoId] = true;
        }

        return $likedMap;
    }
}
