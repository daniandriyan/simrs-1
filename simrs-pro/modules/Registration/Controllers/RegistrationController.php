<?php
declare(strict_types=1);

namespace Modules\Registration\Controllers;

use App\Core\BaseController;

class RegistrationController extends BaseController
{
    public function index(): string
    {
        $sql = "SELECT r.*, p.fullname as patient_name, p.no_rm, c.name as clinic_name, d.fullname as doctor_name 
                FROM registrations r 
                JOIN patients p ON r.patient_id = p.id
                JOIN clinics c ON r.clinic_id = c.id
                JOIN doctors d ON r.doctor_id = d.id
                WHERE r.registration_date = CURDATE()
                ORDER BY r.no_reg ASC";
        return $this->render('Registration', 'index', [
            'title' => 'Antrian Registrasi',
            'registrations' => $this->db->query($sql)->fetchAll()
        ]);
    }

    public function create(): string
    {
        $patients = $this->db->query("SELECT id, no_rm, fullname FROM patients ORDER BY no_rm DESC LIMIT 50")->fetchAll();
        $clinics = $this->db->query("SELECT * FROM clinics ORDER BY name ASC")->fetchAll();
        $doctors = $this->db->query("SELECT d.*, c.name as clinic_name FROM doctors d JOIN clinics c ON d.clinic_id = c.id")->fetchAll();

        return $this->render('Registration', 'create', [
            'title' => 'Pendaftaran Layanan',
            'patients' => $patients,
            'clinics' => $clinics,
            'doctors' => $doctors
        ]);
    }
}
