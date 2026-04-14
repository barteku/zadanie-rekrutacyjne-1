<?php

declare(strict_types=1);

namespace App\Likes;

use App\Entity\Photo;
use App\Entity\User;

class LikeService
{
    public function __construct(
        private LikeRepositoryInterface $likeRepository
    ) {}

    public function toggle(Photo $photo, User $user): bool
    {
        return $this->likeRepository->toggleLike($photo, $user);
    }
}
