<?php

namespace App\Controller;

use App\Service\SupabaseService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class UserController extends AbstractController
{
    #[Route('/users', name: 'user_index')]
    public function index(SupabaseService $supabaseService): Response
    {
        $users = $supabaseService->getAuthUsers();

        return $this->render('user/index.html.twig', [
            'users' => $users,
        ]);
    }

    #[Route('/users/create', name: 'user_create', methods: ['GET', 'POST'])]
    public function create(Request $request, SupabaseService $supabaseService): Response
    {
        if ($request->isMethod('POST')) {
            $email = $request->request->get('email');
            $password = $request->request->get('password');
            $role = $request->request->get('role', 'user');

            $result = $supabaseService->createUser($email, $password, $role);

            if (isset($result['error'])) {
                $this->addFlash('error', $result['error']);
            } else {
                $this->addFlash('success', 'Utilisateur créé avec succès.');
                return $this->redirectToRoute('user_index');
            }
        }

        return $this->render('user/create.html.twig');
    }

    #[Route('/users/{id}/edit', name: 'user_edit', methods: ['GET', 'POST'])]
    public function edit(string $id, Request $request, SupabaseService $supabaseService): Response
    {
        $user = $supabaseService->getUserById($id);
        if (!$user) {
            throw $this->createNotFoundException('Utilisateur non trouvé.');
        }

        if ($request->isMethod('POST')) {
            $email = $request->request->get('email');
            $role = $request->request->get('role', $user['app_metadata']['role'] ?? 'user');

            $result = $supabaseService->updateUser($id, $email, $role);

            if (isset($result['error'])) {
                $this->addFlash('error', $result['error']);
            } else {
                $this->addFlash('success', 'Utilisateur modifié avec succès.');
                return $this->redirectToRoute('user_index');
            }
        }

        return $this->render('user/edit.html.twig', ['user' => $user]);
    }

    #[Route('/users/{id}/delete', name: 'user_delete', methods: ['POST'])]
    public function delete(string $id, SupabaseService $supabaseService): Response
    {
        $result = $supabaseService->deleteUser($id);

        if (isset($result['error'])) {
            $this->addFlash('error', $result['error']);
        } elseif (isset($result['success']) && $result['success'] === true) {
            $this->addFlash('success', 'Utilisateur supprimé.');
        } else {
            $this->addFlash('error', 'La suppression a échoué sans erreur spécifique.');
        }

        return $this->redirectToRoute('user_index');
    }
}
