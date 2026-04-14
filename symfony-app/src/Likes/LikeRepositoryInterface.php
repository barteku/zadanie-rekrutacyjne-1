<?php
declare(strict_types=1);

namespace App\Likes;

use App\Entity\Photo;
use App\Entity\User;

interface LikeRepositoryInterface
{
    public function toggleLike(Photo $photo, User $user): bool;

    /**
     * @param array<int, int> $photoIds
     * @return array<int, bool>
     */
    public function findLikedMapForPhotoIds(User $user, array $photoIds): array;
}