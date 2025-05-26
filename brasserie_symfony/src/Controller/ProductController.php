<?php

namespace App\Controller;

use App\Service\SupabaseService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ProductController extends AbstractController
{
    #[Route('/products', name: 'product_index')]
    public function index(Request $request, SupabaseService $supabase): Response
    {
        $jwtToken = $request->getSession()->get('access_token');

        if (!$jwtToken) {
            return $this->render('product/index.html.twig', [
                'products' => null,
                'errorMessage' => 'Il faut être connecté pour accéder à cette page.'
            ]);
        }

        $products = $supabase->getProducts($jwtToken);

        return $this->render('product/index.html.twig', [
            'products' => $products,
            'errorMessage' => null,
        ]);
    }

    #[Route('/products/new', name: 'product_new', methods: ['GET', 'POST'])]
    public function new(Request $request, SupabaseService $supabase): Response
    {
        $jwtToken = $request->getSession()->get('access_token');
        if (!$jwtToken) {
            $this->addFlash('error', 'Vous devez être connecté pour créer un produit.');
            return $this->redirectToRoute('product_index');
        }

        if ($request->isMethod('POST')) {
            $data = $request->request->all();
            $result = $supabase->createProduct($data, $jwtToken);

            if (isset($result['error'])) {
                $this->addFlash('error', 'Erreur lors de l’ajout du produit : ' . $result['error']);
                return $this->redirectToRoute('product_new');
            }

            $this->addFlash('success', 'Produit ajouté avec succès.');
            return $this->redirectToRoute('product_index');
        }

        return $this->render('product/new.html.twig');
    }

    #[Route('/products/edit/{id}', name: 'product_edit', methods: ['GET', 'POST'])]
    public function edit(string $id, Request $request, SupabaseService $supabase): Response
    {
        $jwtToken = $request->getSession()->get('access_token');
        if (!$jwtToken) {
            $this->addFlash('error', 'Vous devez être connecté pour modifier un produit.');
            return $this->redirectToRoute('product_index');
        }

        $product = $supabase->getProductById($id, $jwtToken);

        if (!$product) {
            throw $this->createNotFoundException('Produit non trouvé.');
        }

        if ($request->isMethod('POST')) {
            $data = $request->request->all();
            $result = $supabase->updateProduct($id, $data, $jwtToken);

            if (isset($result['error'])) {
                $this->addFlash('error', 'Erreur lors de la modification : ' . $result['error']);
                return $this->redirectToRoute('product_edit', ['id' => $id]);
            }

            $this->addFlash('success', 'Produit mis à jour.');
            return $this->redirectToRoute('product_index');
        }

        return $this->render('product/edit.html.twig', ['product' => $product]);
    }

    #[Route('/products/delete/{id}', name: 'product_delete', methods: ['POST'])]
    public function delete(string $id, Request $request, SupabaseService $supabase): Response
    {
        if (!$this->isCsrfTokenValid('delete' . $id, $request->request->get('_token'))) {
            $this->addFlash('error', 'Token CSRF invalide.');
            return $this->redirectToRoute('product_index');
        }

        $jwtToken = $request->getSession()->get('access_token');
        if (!$jwtToken) {
            $this->addFlash('error', 'Vous devez être connecté pour supprimer un produit.');
            return $this->redirectToRoute('product_index');
        }

        $success = $supabase->deleteProduct($id, $jwtToken);

        if ($success) {
            $this->addFlash('success', 'Produit supprimé.');
        } else {
            $this->addFlash('error', 'Erreur lors de la suppression.');
        }

        return $this->redirectToRoute('product_index');
    }
}
