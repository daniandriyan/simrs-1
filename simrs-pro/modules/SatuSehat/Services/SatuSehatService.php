<?php
declare(strict_types=1);

namespace Modules\SatuSehat\Services;

use App\Core\Config;

class SatuSehatService
{
    private string $baseUrl;
    private string $authUrl;
    private string $clientId;
    private string $clientSecret;

    public function __construct()
    {
        $this->baseUrl = Config::get('SATUSEHAT_BASE_URL', '');
        $this->authUrl = Config::get('SATUSEHAT_AUTH_URL', '');
        $this->clientId = Config::get('SATUSEHAT_CLIENT_ID', '');
        $this->clientSecret = Config::get('SATUSEHAT_CLIENT_SECRET', '');
    }

    public function getToken(): ?string
    {
        $ch = curl_init($this->authUrl . '/accesstoken?grant_type=client_credentials');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret
        ]));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);

        $response = curl_exec($ch);
        $data = json_decode($response, true);
        curl_close($ch);

        return $data['access_token'] ?? null;
    }

    public function sendResource(string $resourceType, array $payload, string $method = 'POST'): array
    {
        $token = $this->getToken();
        if (!$token) return ['status' => 'error', 'message' => 'Failed to get Access Token'];

        $url = $this->baseUrl . '/fhir-r4/v1/' . $resourceType;
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $token,
            'Content-Type: application/json'
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return [
            'status_code' => $httpCode,
            'response' => json_decode($response, true)
        ];
    }
}
