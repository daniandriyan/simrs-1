<?php
declare(strict_types=1);

namespace Modules\Pharmacy\Controllers;

use App\Core\BaseController;

class PharmacyController extends BaseController
{
    public function index(): string
    {
        $sql = "SELECT p.*, m.name as med_name, pat.fullname as patient_name, pat.no_rm 
                FROM prescriptions p
                JOIN medicines m ON p.medicine_id = m.id
                JOIN registrations r ON p.registration_id = r.id
                JOIN patients pat ON r.patient_id = pat.id
                WHERE p.status = 'Pending'";
        return $this->render('Pharmacy', 'index', [
            'title' => 'Antrian Resep Farmasi',
            'prescriptions' => $this->db->query($sql)->fetchAll()
        ]);
    }

    public function medicines(): string
    {
        $medicines = $this->db->query("SELECT * FROM medicines ORDER BY name ASC")->fetchAll();
        return $this->render('Pharmacy', 'medicines', [
            'title' => 'Stok Obat',
            'medicines' => $medicines
        ]);
    }
}
