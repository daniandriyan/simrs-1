<?php
declare(strict_types=1);

namespace Modules\EMR\Controllers;

use App\Core\BaseController;

class EMRController extends BaseController
{
    public function index(): string
    {
        $sql = "SELECT r.*, p.fullname as patient_name, p.no_rm, c.name as clinic_name 
                FROM registrations r 
                JOIN patients p ON r.patient_id = p.id
                JOIN clinics c ON r.clinic_id = c.id
                WHERE r.status != 'Completed' AND r.registration_date = CURDATE()";
        return $this->render('EMR', 'index', [
            'title' => 'Pemeriksaan Medis',
            'patients' => $this->db->query($sql)->fetchAll()
        ]);
    }

    public function examine(int $id): string
    {
        $stmt = $this->db->prepare("SELECT r.*, p.fullname as patient_name, p.no_rm FROM registrations r JOIN patients p ON r.patient_id = p.id WHERE r.id = ?");
        $stmt->execute([$id]);
        $patient = $stmt->fetch();

        $medicines = $this->db->query("SELECT * FROM medicines ORDER BY name ASC")->fetchAll();

        return $this->render('EMR', 'examine', [
            'title' => 'Proses SOAP',
            'patient' => $patient,
            'medicines' => $medicines
        ]);
    }
}
