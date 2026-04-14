<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Photo;
use App\Entity\User;
use App\Likes\LikeService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class PhotoController extends AbstractController
{
    #[Route('/photo/{id}/like', name: 'photo_like')]
    public function like(Photo $photo, Request $request, EntityManagerInterface $em, LikeService $likeService): Response
    {
        $session = $request->getSession();
        $userId = $session->get('user_id');

        if (!$userId) {
            $this->addFlash('error', 'You must be logged in to like photos.');
            return $this->redirectToRoute('home');
        }

        $user = $em->getRepository(User::class)->find($userId);
        if (!$user instanceof User) {
            $session->clear();
            $this->addFlash('error', 'Your session has expired. Please log in again.');
            return $this->redirectToRoute('home');
        }

        $didLike = $likeService->toggle($photo, $user);
        if ($didLike) {
            $this->addFlash('success', 'Photo liked!');
        } else {
            $this->addFlash('info', 'Photo unliked!');
        }

        return $this->redirectToRoute('home');
    }
}
