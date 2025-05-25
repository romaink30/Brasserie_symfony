<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class SupabaseService
{
    private HttpClientInterface $client;
    private string $baseUrl;
    private string $authUrl;
    private string $apiKey;

    public function __construct(HttpClientInterface $client, string $supabaseUrl, string $supabaseApiKey)
    {
        $this->client = $client;
        $this->baseUrl = rtrim($supabaseUrl, '/') . '/rest/v1';
        $this->authUrl = rtrim($supabaseUrl, '/') . '/auth/v1';
        $this->apiKey = $supabaseApiKey;
    }

    private function getDefaultHeaders(?string $accessToken = null): array
    {
        return [
            'apikey' => $this->apiKey,
            'Authorization' => 'Bearer ' . ($accessToken ?? $this->apiKey),
            'Accept' => 'application/json',
        ];
    }

    // ========== USERS ==========

    public function getAuthUsers(): array
    {
        $response = $this->client->request('POST', "{$this->baseUrl}/rpc/get_auth_users", [
            'headers' => $this->getDefaultHeaders(),
        ]);
        return $response->toArray();
    }

    public function getUserById(string $id): ?array
    {
        $response = $this->client->request('GET', "{$this->baseUrl}/users", [
            'headers' => $this->getDefaultHeaders(),
            'query' => ['id' => "eq.$id"],
        ]);
        $users = $response->toArray();
        return $users[0] ?? null;
    }

    public function createUser(string $email, string $password, string $role = 'user'): array
    {
        $response = $this->client->request('POST', "{$this->authUrl}/signup", [
            'headers' => [
                'apikey' => $this->apiKey,
                'Content-Type' => 'application/json',
            ],
            'json' => [
                'email' => $email,
                'password' => $password,
                'data' => ['role' => $role],
            ],
        ]);

        $data = $response->toArray(false);

        if ($response->getStatusCode() >= 400 || isset($data['error'])) {
            return ['error' => $data['error_description'] ?? $data['msg'] ?? 'Erreur lors de la création utilisateur.'];
        }

        return $data;
    }

    public function updateUser(string $id, string $email, string $role): array
    {
        $response = $this->client->request('PATCH', "{$this->baseUrl}/users?id=eq.$id", [
            'headers' => array_merge($this->getDefaultHeaders(), [
                'Content-Type' => 'application/json',
                'Prefer' => 'return=representation',
            ]),
            'json' => [
                'email' => $email,
                'role' => $role,
            ],
        ]);
        return $response->toArray(false);
    }

    public function deleteUser(string $id): array
    {
        try {
            $response = $this->client->request('POST', "{$this->baseUrl}/rpc/delete_user", [
                'headers' => $this->getDefaultHeaders(),
                'json' => ['user_id' => $id],
            ]);
            return $response->getStatusCode() === 204
                ? ['success' => true]
                : ['error' => $response->toArray(false)['message'] ?? 'Erreur lors de la suppression utilisateur.'];
        } catch (\Throwable $e) {
            return ['error' => $e->getMessage()];
        }
    }

    // ========== PRODUCTS ==========

    public function getProducts(?string $accessToken = null): array
    {
        $response = $this->client->request('GET', "{$this->baseUrl}/products", [
            'headers' => $this->getDefaultHeaders($accessToken),
        ]);
        return $response->toArray();
    }

    public function getProductById(string $id, ?string $accessToken = null): ?array
    {
        $response = $this->client->request('GET', "{$this->baseUrl}/products", [
            'headers' => $this->getDefaultHeaders($accessToken),
            'query' => ['id' => "eq.$id"],
        ]);
        $products = $response->toArray();
        return $products[0] ?? null;
    }

    public function CreateProduct(array $productData, ?string $accessToken = null): array
{
    try {
        $response = $this->client->request('POST', "{$this->baseUrl}/products", [
            'headers' => array_merge($this->getDefaultHeaders($accessToken), [
                'Prefer' => 'return=representation',
                'Content-Type' => 'application/json',
            ]),
            'json' => $productData,
        ]);

        return in_array($response->getStatusCode(), [200, 201])
            ? ['success' => true, 'data' => $response->toArray()]
            : ['error' => $response->toArray(false)['message'] ?? 'Erreur lors de l’ajout du produit.'];
    } catch (\Throwable $e) {
        return ['error' => $e->getMessage()];
    }
}



public function updateProduct(string $id, array $productData, ?string $accessToken = null): array
{
    try {
        $response = $this->client->request('PATCH', "{$this->baseUrl}/products?id=eq.$id", [
            'headers' => array_merge($this->getDefaultHeaders($accessToken), [
                'Prefer' => 'return=representation',
                'Content-Type' => 'application/json',
            ]),
            'json' => $productData,
        ]);

        return in_array($response->getStatusCode(), [200, 204])
            ? ['success' => true, 'data' => $response->toArray(false)]
            : ['error' => $response->toArray(false)['message'] ?? 'Erreur lors de la mise à jour du produit.'];
    } catch (\Throwable $e) {
        return ['error' => $e->getMessage()];
    }
}



    public function deleteProduct(string $id, ?string $accessToken = null): array
    {
        try {
            $response = $this->client->request('DELETE', "{$this->baseUrl}/products?id=eq.$id", [
                'headers' => array_merge($this->getDefaultHeaders($accessToken), [
                    'Prefer' => 'return=minimal',
                ]),
            ]);
            return $response->getStatusCode() === 204
                ? ['success' => true]
                : ['error' => $response->toArray(false)['message'] ?? 'Erreur lors de la suppression du produit.'];
        } catch (\Throwable $e) {
            return ['error' => $e->getMessage()];
        }
    }

    // ========== RESERVATIONS ==========

    public function getAllReservations(?string $accessToken = null): array
    {
        $response = $this->client->request('GET', "{$this->baseUrl}/reservations", [
            'headers' => $this->getDefaultHeaders($accessToken),
        ]);
        return $response->toArray();
    }

    public function getReservationById(string $id, ?string $accessToken = null): ?array
    {
        $response = $this->client->request('GET', "{$this->baseUrl}/reservations", [
            'headers' => $this->getDefaultHeaders($accessToken),
            'query' => ['id' => "eq.$id"],
        ]);
        $reservations = $response->toArray();
        return $reservations[0] ?? null;
    }

    public function createReservation(array $data, ?string $accessToken = null): array
    {
        $response = $this->client->request('POST', "{$this->baseUrl}/reservations", [
            'headers' => array_merge($this->getDefaultHeaders($accessToken), [
                'Content-Type' => 'application/json',
                'Prefer' => 'return=representation',
            ]),
            'json' => $data,
        ]);
        return $response->toArray();
    }

    public function updateReservation(string $id, array $data, ?string $accessToken = null): array
    {
        $response = $this->client->request('PATCH', "{$this->baseUrl}/reservations?id=eq.$id", [
            'headers' => array_merge($this->getDefaultHeaders($accessToken), [
                'Content-Type' => 'application/json',
                'Prefer' => 'return=representation',
            ]),
            'json' => $data,
        ]);
        return $response->toArray();
    }

public function deleteReservation(string $id, ?string $accessToken = null): array
{
    try {
        $response = $this->client->request('DELETE', "{$this->baseUrl}/reservations?id=eq.$id", [
            'headers' => array_merge($this->getDefaultHeaders($accessToken), [
                'Prefer' => 'return=minimal',
            ]),
        ]);

        return $response->getStatusCode() === 204
            ? ['success' => true]
            : ['error' => $response->toArray(false)['message'] ?? 'Erreur lors de la suppression de la réservation.'];
    } catch (\Throwable $e) {
        return ['error' => $e->getMessage()];
    }
}


    // ========== AUTHENTICATION ==========

    public function login(string $email, string $password): array
    {
        $response = $this->client->request('POST', "{$this->authUrl}/token?grant_type=password", [
            'headers' => [
                'apikey' => $this->apiKey,
                'Content-Type' => 'application/json',
            ],
            'json' => [
                'email' => $email,
                'password' => $password,
            ],
        ]);

        $data = $response->toArray(false);

        if ($response->getStatusCode() >= 400 || isset($data['error'])) {
            return ['error' => $data['error_description'] ?? $data['msg'] ?? 'Erreur de connexion.'];
        }

        return [
            'access_token' => $data['access_token'] ?? null,
            'refresh_token' => $data['refresh_token'] ?? null,
            'user' => $data['user'] ?? null,
        ];
    }

    public function getUserFromToken(string $accessToken): ?array
    {
        $response = $this->client->request('GET', "{$this->authUrl}/user", [
            'headers' => [
                'Authorization' => "Bearer $accessToken",
                'apikey' => $this->apiKey,
                'Accept' => 'application/json',
            ],
        ]);

        return $response->getStatusCode() >= 400 ? null : $response->toArray();
    }
}
