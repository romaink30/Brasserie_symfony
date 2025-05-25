<?php

namespace App\Controller;

use App\Service\SupabaseService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\RedirectResponse;

class ReservationController extends AbstractController
{
    #[Route('/reservations', name: 'reservation_index')]
    public function index(Request $request, SupabaseService $supabase): Response
    {
        $jwtToken = $request->getSession()->get('access_token');
        $reservations = $supabase->getAllReservations($jwtToken);
        $users = $supabase->getAuthUsers();

        $userEmails = [];
        foreach ($users as $user) {
            $userEmails[$user['id']] = $user['email'] ?? 'Email inconnu';
        }

        return $this->render('reservation/index.html.twig', [
            'reservations' => $reservations,
            'userEmails' => $userEmails,
        ]);
    }

    #[Route('/reservations/new', name: 'reservation_new', methods: ['GET', 'POST'])]
    public function new(Request $request, SupabaseService $supabase): Response
    {
        if ($request->isMethod('POST')) {
            $jwtToken = $request->getSession()->get('access_token');

            $data = [
                'user_id' => $request->request->get('user_id'),
                'product_id' => $request->request->get('product_id'),
                'quantity' => (int) $request->request->get('quantity'),
                'created_at' => (new \DateTime())->format('c'),
            ];

            $result = $supabase->createReservation($data, $jwtToken);

            if (isset($result['error'])) {
                $this->addFlash('error', $result['error']);
                return $this->redirectToRoute('reservation_new');
            }

            $this->addFlash('success', 'Réservation créée avec succès.');

            return $this->redirectToRoute('reservation_index');
        }

        return $this->render('reservation/new.html.twig');
    }

    #[Route('/reservations/edit/{id}', name: 'reservation_edit', methods: ['GET', 'POST'])]
    public function edit(string $id, Request $request, SupabaseService $supabase): Response
    {
        $jwtToken = $request->getSession()->get('access_token');

        $reservation = $supabase->getReservationById($id, $jwtToken);

        if (!$reservation) {
            throw $this->createNotFoundException('Réservation non trouvée.');
        }

        if ($request->isMethod('POST')) {
            $data = [
                'user_id' => $request->request->get('user_id'),
                'product_id' => $request->request->get('product_id'),
                'quantity' => (int) $request->request->get('quantity'),
            ];

            $result = $supabase->updateReservation($id, $data, $jwtToken);

            if (isset($result['error'])) {
                $this->addFlash('error', $result['error']);
                return $this->redirectToRoute('reservation_edit', ['id' => $id]);
            }

            $this->addFlash('success', 'Réservation mise à jour.');

            return $this->redirectToRoute('reservation_index');
        }

        return $this->render('reservation/edit.html.twig', [
            'reservation' => $reservation,
        ]);
    }

    #[Route('/reservations/delete/{id}', name: 'reservation_delete', methods: ['POST'])]
    public function delete(string $id, Request $request, SupabaseService $supabase): RedirectResponse
    {
        $token = $request->request->get('_token');

        if (!$this->isCsrfTokenValid('delete' . $id, $token)) {
            $this->addFlash('error', 'Token CSRF invalide.');
            return $this->redirectToRoute('reservation_index');
        }

        $jwtToken = $request->getSession()->get('access_token');
        $result = $supabase->deleteReservation($id, $jwtToken);

        if (isset($result['error'])) {
            $this->addFlash('error', $result['error']);
        } else {
            $this->addFlash('success', 'Réservation supprimée.');
        }

        return $this->redirectToRoute('reservation_index');
    }
}
