<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class HomeController extends AbstractController
{
    #[Route('/', name: 'home')]
    public function index(Request $request): Response
    {
        $session = $request->getSession();
        $user = $session->get('user');
        $email = $user['email'] ?? null;

        return $this->render('home/index.html.twig', [
            'user_email' => $email,
        ]);
    }
}
