<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;

class SecurityController extends AbstractController
{
    private HttpClientInterface $client;

    public function __construct(HttpClientInterface $client)
    {
        $this->client = $client;
    }

    #[Route('/login', name: 'login', methods: ['GET', 'POST'])]
    public function login(Request $request, SessionInterface $session): Response
    {
        if ($request->isMethod('POST')) {
            $email = $request->request->get('email');
            $password = $request->request->get('password');

            $response = $this->client->request('POST', 'https://eevzihiyvpigfiwsybuy.supabase.co/auth/v1/token?grant_type=password', [
                'headers' => [
                    'apikey' => 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6ImVldnppaGl5dnBpZ2Zpd3N5YnV5Iiwicm9sZSI6ImFub24iLCJpYXQiOjE3NDU1MDkwMzMsImV4cCI6MjA2MTA4NTAzM30.WeUkX7c7wOY2uhQGJ-teADCIKrJEI6lts8RMI_BgNDY',
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'email' => $email,
                    'password' => $password,
                ],
            ]);

            if ($response->getStatusCode() === 200) {
                $data = $response->toArray();
                $session->set('user', $data['user']); 
                $session->set('access_token', $data['access_token']);
                $session->set('email', $data['user']['email']);  
                return $this->redirectToRoute('user_index');
            }

            $this->addFlash('error', 'Identifiants invalides.');
        }

        return $this->render('login/login.html.twig');
    }

    #[Route('/logout', name: 'logout')]
    public function logout(SessionInterface $session): Response
    {
        $session->remove('user');
        $session->remove('access_token');
        $session->remove('email');

        return $this->redirectToRoute('home'); 
    }
}
