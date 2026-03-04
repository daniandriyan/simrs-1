<?php
declare(strict_types=1);

namespace Modules\Laboratory\Controllers;

use App\Core\BaseController;

class LaboratoryController extends BaseController
{
    public function index(): string
    {
        $sql = "SELECT lo.*, lt.name as test_name, lt.category, p.fullname as patient_name, p.no_rm 
                FROM lab_orders lo
                JOIN lab_tests lt ON lo.test_id = lt.id
                JOIN registrations r ON lo.registration_id = r.id
                JOIN patients p ON r.patient_id = p.id
                WHERE lo.status = 'Pending'
                ORDER BY lo.ordered_at ASC";
        
        return $this->render('Laboratory', 'index', [
            'title' => 'Antrian Laboratorium',
            'orders' => $this->db->query($sql)->fetchAll()
        ]);
    }

    public function input(int $orderId): string
    {
        $sql = "SELECT lo.*, lt.name as test_name, lt.normal_range, lt.unit, p.fullname as patient_name, p.no_rm 
                FROM lab_orders lo
                JOIN lab_tests lt ON lo.test_id = lt.id
                JOIN registrations r ON lo.registration_id = r.id
                JOIN patients p ON r.patient_id = p.id
                WHERE lo.id = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$orderId]);
        $order = $stmt->fetch();

        return $this->render('Laboratory', 'input', [
            'title' => 'Input Hasil Lab',
            'order' => $order
        ]);
    }

    public function save(): void
    {
        $orderId = (int)$_POST['order_id'];
        $value = $_POST['result_value'];
        $notes = $_POST['notes'];

        $this->db->beginTransaction();
        try {
            // 1. Simpan Hasil
            $stmt = $this->db->prepare("INSERT INTO lab_results (order_id, result_value, notes) VALUES (?,?,?)");
            $stmt->execute([$orderId, $value, $notes]);

            // 2. Update Status Order
            $this->db->prepare("UPDATE lab_orders SET status = 'Completed' WHERE id = ?")->execute([$orderId]);

            $this->db->commit();
            header("Location: /laboratory");
        } catch (\Exception $e) {
            $this->db->rollBack();
            die($e->getMessage());
        }
    }
}
