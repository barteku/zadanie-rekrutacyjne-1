<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Photo;
use App\Entity\User;
use App\Integration\PhoenixApi\InvalidTokenException;
use App\Integration\PhoenixApi\PhoenixApiClient;
use App\Integration\PhoenixApi\PhoenixApiException;
use App\Repository\PhotoRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ProfileController extends AbstractController
{
    #[Route('/profile', name: 'profile')]
    public function profile(Request $request, EntityManagerInterface $em): Response
    {
        $user = $this->getCurrentUserFromSession($request, $em);
        if (!$user instanceof User) {
            return $this->redirectToRoute('home');
        }

        return $this->render('profile/index.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route('/profile/phoenix-token', name: 'profile_save_phoenix_token', methods: ['POST'])]
    public function savePhoenixToken(Request $request, EntityManagerInterface $em): Response
    {
        $user = $this->getCurrentUserFromSession($request, $em);
        if (!$user instanceof User) {
            return $this->redirectToRoute('home');
        }

        if (!$this->isCsrfTokenValid('phoenix-token', (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Invalid request token.');
            return $this->redirectToRoute('profile');
        }

        $token = trim((string) $request->request->get('phoenix_api_token'));
        $user->setPhoenixApiToken($token !== '' ? $token : null);
        $em->persist($user);
        $em->flush();

        $this->addFlash('success', 'PhoenixApi token updated.');
        return $this->redirectToRoute('profile');
    }

    #[Route('/profile/import-photos', name: 'profile_import_photos', methods: ['POST'])]
    public function importPhotos(
        Request $request,
        EntityManagerInterface $em,
        PhoenixApiClient $phoenixApiClient,
        PhotoRepository $photoRepository
    ): Response {
        $user = $this->getCurrentUserFromSession($request, $em);
        if (!$user instanceof User) {
            return $this->redirectToRoute('home');
        }

        if (!$this->isCsrfTokenValid('import-photos', (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Invalid request token.');
            return $this->redirectToRoute('profile');
        }

        $token = $user->getPhoenixApiToken();
        if ($token === null || trim($token) === '') {
            $this->addFlash('error', 'Please save your PhoenixApi token first.');
            return $this->redirectToRoute('profile');
        }

        try {
            $remotePhotos = $phoenixApiClient->fetchPhotos($token);
        } catch (InvalidTokenException $exception) {
            $this->addFlash('error', $exception->getMessage());
            return $this->redirectToRoute('profile');
        } catch (PhoenixApiException $exception) {
            $this->addFlash('error', $exception->getMessage());
            return $this->redirectToRoute('profile');
        }

        $existingUrls = array_flip($photoRepository->findImageUrlsForUser($user));
        $importedCounter = 0;

        foreach ($remotePhotos as $remotePhoto) {
            $imageUrl = trim((string) ($remotePhoto['photo_url'] ?? ''));
            if ($imageUrl === '' || isset($existingUrls[$imageUrl])) {
                continue;
            }

            $photo = new Photo();
            $photo->setUser($user);
            $photo->setImageUrl($imageUrl);
            $photo->setCamera($this->normalizeNullableString($remotePhoto['camera'] ?? null));
            $photo->setDescription($this->normalizeNullableString($remotePhoto['description'] ?? null));
            $photo->setLocation($this->normalizeNullableString($remotePhoto['location'] ?? null));
            $photo->setTakenAt($this->parseTakenAt($remotePhoto['taken_at'] ?? null));

            $em->persist($photo);
            $existingUrls[$imageUrl] = true;
            $importedCounter++;
        }

        $em->flush();

        $this->addFlash('success', sprintf('Imported %d new photo(s).', $importedCounter));
        return $this->redirectToRoute('profile');
    }

    private function getCurrentUserFromSession(Request $request, EntityManagerInterface $em): ?User
    {
        $session = $request->getSession();
        $userId = $session->get('user_id');
        if (!$userId) {
            return null;
        }

        $user = $em->getRepository(User::class)->find($userId);
        if (!$user instanceof User) {
            $session->clear();
            return null;
        }

        return $user;
    }

    private function normalizeNullableString(mixed $value): ?string
    {
        $normalized = trim((string) $value);
        return $normalized !== '' ? $normalized : null;
    }

    private function parseTakenAt(mixed $value): ?\DateTimeImmutable
    {
        $rawDate = trim((string) $value);
        if ($rawDate === '') {
            return null;
        }

        try {
            return new \DateTimeImmutable($rawDate);
        } catch (\Exception) {
            return null;
        }
    }
}
