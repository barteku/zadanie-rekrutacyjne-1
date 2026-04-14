<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use App\Form\Model\PhotoFilterData;
use App\Form\PhotoFilterType;
use App\Likes\LikeRepositoryInterface;
use App\Repository\PhotoRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class HomeController extends AbstractController
{
    #[Route('/', name: 'home')]
    public function index(
        Request $request,
        EntityManagerInterface $em,
        PhotoRepository $photoRepository,
        LikeRepositoryInterface $likeRepository
    ): Response
    {
        $filterData = new PhotoFilterData();
        $filterForm = $this->createForm(PhotoFilterType::class, $filterData, [
            'method' => 'GET',
        ]);
        $filterForm->handleRequest($request);

        $filters = [
            'location' => trim((string) ($filterData->getLocation() ?? '')),
            'camera' => trim((string) ($filterData->getCamera() ?? '')),
            'description' => trim((string) ($filterData->getDescription() ?? '')),
            'taken_at' => $filterData->getTakenAt()?->format('Y-m-d') ?? '',
            'username' => trim((string) ($filterData->getUsername() ?? '')),
        ];
        $photos = $photoRepository->findFilteredWithUsers($filters);

        $session = $request->getSession();
        $userId = $session->get('user_id');
        $currentUser = null;
        $userLikes = [];

        if ($userId) {
            $currentUser = $em->getRepository(User::class)->find($userId);

            if ($currentUser) {
                $photoIds = array_map(static fn ($photo): int => (int) $photo->getId(), $photos);
                $userLikes = $likeRepository->findLikedMapForPhotoIds($currentUser, $photoIds);
            }
        }

        return $this->render('home/index.html.twig', [
            'photos' => $photos,
            'currentUser' => $currentUser,
            'userLikes' => $userLikes,
            'filterForm' => $filterForm->createView(),
        ]);
    }
}
