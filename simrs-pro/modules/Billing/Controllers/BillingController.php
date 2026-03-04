<?php
declare(strict_types=1);

namespace Modules\Billing\Controllers;

use App\Core\BaseController;

class BillingController extends BaseController
{
    public function index(): string
    {
        $sql = "SELECT r.*, p.fullname as patient_name, p.no_rm, 
                (SELECT SUM(m.price * pr.qty) FROM prescriptions pr JOIN medicines m ON pr.medicine_id = m.id WHERE pr.registration_id = r.id) as total_obat
                FROM registrations r
                JOIN patients p ON r.patient_id = p.id
                WHERE r.status = 'Completed' AND r.id NOT IN (SELECT registration_id FROM billings)";
        return $this->render('Billing', 'index', [
            'title' => 'Billing Pembayaran',
            'bills' => $this->db->query($sql)->fetchAll()
        ]);
    }

    public function pay(int $id): string
    {
        return "Proses Pembayaran ID: " . $id;
    }
}
