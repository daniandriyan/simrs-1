<?php
declare(strict_types=1);
namespace Modules\Patient\Controllers;
use App\Core\BaseController;

class PatientController extends BaseController {
    public function index(): string {
        $stmt = $this->db->query("SELECT * FROM patients ORDER BY no_rm DESC LIMIT 50");
        return $this->render('Patient', 'index', ['patients' => $stmt->fetchAll(), 'title' => 'Data Pasien']);
    }
    public function create(): string {
        return $this->render('Patient', 'create', ['title' => 'Tambah Pasien']);
    }
}
