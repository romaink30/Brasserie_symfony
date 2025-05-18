<?php

namespace App\Controller;

use App\Service\SupabaseService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class LoginController extends AbstractController
{
    #[Route('/login', name: 'app_login', methods: ['GET', 'POST'])]
    public function login(Request $request, SupabaseService $supabaseService): Response
    {
        $error = null;

        if ($request->isMethod('POST')) {
            $email = $request->request->get('email');
            $password = $request->request->get('password');

            $result = $supabaseService->login($email, $password);

            if (isset($result['error'])) {
                $error = $result['error'];
            } elseif (!isset($result['access_token'])) {
                $error = 'Connexion échouée.';
            } else {
                $userData = $supabaseService->getUserFromToken($result['access_token']);

                $role = $userData['app_metadata']['role'] ?? null;

                if ($role !== 'admin') {
                    $error = 'Accès refusé : vous n\'êtes pas administrateur.';
                } else {
                    $session = $request->getSession();
                    $session->set('user', $userData);
                    $session->set('access_token', $result['access_token']);
                    return $this->redirectToRoute('home');
                }
            }
        }

        return $this->render('login/login.html.twig', [
            'error' => $error,
        ]);
    }

    #[Route('/logout', name: 'app_logout')]
    public function logout(Request $request): Response
    {
        $request->getSession()->clear();
        return $this->redirectToRoute('app_login');
    }
}
