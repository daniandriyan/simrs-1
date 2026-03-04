<?php
declare(strict_types=1);

namespace Modules\SatuSehat\Controllers;

use App\Core\BaseController;
use Modules\SatuSehat\Services\SatuSehatService;

class SatuSehatController extends BaseController
{
    private SatuSehatService $service;

    public function __construct()
    {
        parent::__construct();
        $this->service = new SatuSehatService();
    }

    public function index(): string
    {
        $sql = "SELECT p.*, sm.satusehat_id 
                FROM patients p 
                LEFT JOIN satusehat_mapping sm ON sm.local_id = p.id AND sm.resource_type = 'Patient'
                ORDER BY p.id DESC LIMIT 50";
        
        return $this->render('SatuSehat', 'index', [
            'title' => 'SatuSehat Monitoring',
            'patients' => $this->db->query($sql)->fetchAll()
        ]);
    }

    public function syncPatient(int $id): void
    {
        // 1. Get Local Data
        $stmt = $this->db->prepare("SELECT * FROM patients WHERE id = ?");
        $stmt->execute([$id]);
        $p = $stmt->fetch();

        // 2. Map to FHIR JSON
        $payload = [
            "resourceType" => "Patient",
            "identifier" => [
                [
                    "use" => "official",
                    "system" => "https://fhir.kemkes.go.id/id/nik",
                    "value" => $p['nik']
                ]
            ],
            "name" => [
                ["use" => "official", "text" => $p['fullname']]
            ],
            "gender" => ($p['gender'] == 'L' ? 'male' : 'female'),
            "birthDate" => $p['birth_date']
        ];

        // 3. Send to API
        $result = $this->service->sendResource('Patient', $payload);

        if ($result['status_code'] == 201 || $result['status_code'] == 200) {
            $ssId = $result['response']['id'];
            // Save Mapping
            $stmt = $this->db->prepare("INSERT INTO satusehat_mapping (resource_type, local_id, satusehat_id) VALUES ('Patient', ?, ?) ON DUPLICATE KEY UPDATE satusehat_id = ?");
            $stmt->execute([$id, $ssId, $ssId]);
            $_SESSION['flash'] = ['type' => 'success', 'message' => 'Berhasil sinkronisasi ke SatuSehat!'];
        } else {
            $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Gagal: ' . ($result['response']['issue'][0]['details']['text'] ?? 'Unknown Error')];
        }

        header("Location: /satusehat");
    }
}
