<?php

declare(strict_types=1);

namespace App\Tests\Likes;

use App\Entity\Photo;
use App\Entity\User;
use App\Likes\LikeRepositoryInterface;
use App\Likes\LikeService;
use PHPUnit\Framework\TestCase;

class LikeServiceTest extends TestCase
{
    public function testToggleDelegatesToRepository(): void
    {
        $photo = new Photo();
        $photo->setImageUrl('https://example.com/photo.jpg');
        $photo->setUser((new User())->setUsername('jane')->setEmail('jane@example.com'));
        $user = (new User())->setUsername('john')->setEmail('john@example.com');

        $repository = $this->createMock(LikeRepositoryInterface::class);
        $repository
            ->expects(self::once())
            ->method('toggleLike')
            ->with($photo, $user)
            ->willReturn(true);

        $service = new LikeService($repository);

        self::assertTrue($service->toggle($photo, $user));
    }
}
