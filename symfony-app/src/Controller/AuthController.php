<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Doctrine\DBAL\Connection;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AuthController extends AbstractController
{
    #[Route('/auth/{username}/{token}', name: 'auth_login')]
    public function login(string $username, string $token, Connection $connection, Request $request): Response
    {
        $sql = <<<SQL
            SELECT u.id, u.username
            FROM auth_tokens at
            INNER JOIN users u ON u.id = at.user_id
            WHERE at.token = :token AND u.username = :username
            LIMIT 1
        SQL;
        $userData = $connection->executeQuery($sql, [
            'token' => $token,
            'username' => $username,
        ])->fetchAssociative();

        if (!$userData) {
            $this->addFlash('error', 'Invalid token or username.');
            return $this->redirectToRoute('home');
        }

        $session = $request->getSession();
        $session->set('user_id', (int) $userData['id']);
        $session->set('username', $userData['username']);

        $this->addFlash('success', 'Welcome back, ' . $userData['username'] . '!');

        return $this->redirectToRoute('home');
    }

    #[Route('/logout', name: 'logout')]
    public function logout(Request $request): Response
    {
        $session = $request->getSession();
        $session->clear();

        $this->addFlash('info', 'You have been logged out successfully.');

        return $this->redirectToRoute('home');
    }
}
