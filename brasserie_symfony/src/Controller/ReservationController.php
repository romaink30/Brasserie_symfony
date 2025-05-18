<?php

namespace App\Controller;

use App\Service\SupabaseService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ReservationController extends AbstractController
{
    #[Route('/reservations', name: 'reservation_index')]
    public function index(Request $request, SupabaseService $supabase): Response
    {
        $jwtToken = $request->getSession()->get('access_token');
        $reservations = $supabase->getReservations($jwtToken);

        return $this->render('reservation/index.html.twig', [
            'reservations' => $reservations,
        ]);
    }

    #[Route('/reservations/new', name: 'reservation_new')]
    public function new(Request $request, SupabaseService $supabase): Response
    {
        if ($request->isMethod('POST')) {
            $data = [
                'user_id' => $request->request->get('user_id'),
                'product_id' => $request->request->get('product_id'),
                'quantity' => (int) $request->request->get('quantity'),
                'created_at' => (new \DateTime())->format('c'),
            ];

            $supabase->createReservation($data);

            return $this->redirectToRoute('reservation_index');
        }

        return $this->render('reservation/new.html.twig');
    }

    #[Route('/reservations/edit/{id}', name: 'reservation_edit')]
    public function edit(string $id, Request $request, SupabaseService $supabase): Response
    {
        $reservation = $supabase->getReservationById($id);

        if (!$reservation) {
            throw $this->createNotFoundException('Réservation non trouvée.');
        }

        if ($request->isMethod('POST')) {
            $data = [
                'user_id' => $request->request->get('user_id'),
                'product_id' => $request->request->get('product_id'),
                'quantity' => (int) $request->request->get('quantity'),
            ];

            $supabase->updateReservation($id, $data);

            return $this->redirectToRoute('reservation_index');
        }

        return $this->render('reservation/edit.html.twig', [
            'reservation' => $reservation,
        ]);
    }

    #[Route('/reservations/delete/{id}', name: 'reservation_delete')]
    public function delete(string $id, SupabaseService $supabase): Response
    {
        $supabase->deleteReservation($id);

        return $this->redirectToRoute('reservation_index');
    }
}
