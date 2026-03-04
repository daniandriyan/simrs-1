<?php
declare(strict_types=1);

namespace Modules\Reporting\Models;

use App\Core\Database;
use PDO;

class ReportRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = (new Database())->connect();
    }

    public function getMonthlyRevenue(): array
    {
        $sql = "SELECT DATE(paid_at) as date, SUM(total_amount) as total FROM billings GROUP BY DATE(paid_at) LIMIT 30";
        return $this->db->query($sql)->fetchAll();
    }

    public function getServiceDistribution(): array
    {
        $sql = "SELECT c.name, COUNT(r.id) as total FROM registrations r JOIN clinics c ON r.clinic_id = c.id GROUP BY c.name";
        return $this->db->query($sql)->fetchAll();
    }

    public function getSummaryStats(): array
    {
        return [
            'total_revenue' => $this->db->query("SELECT SUM(total_amount) FROM billings")->fetchColumn() ?: 0,
            'total_patients' => $this->db->query("SELECT COUNT(*) FROM patients")->fetchColumn(),
            'total_visits' => $this->db->query("SELECT COUNT(*) FROM registrations")->fetchColumn(),
            'active_inpatient' => $this->db->query("SELECT COUNT(*) FROM inpatient_admissions WHERE status = 'Active'")->fetchColumn() ?: 0
        ];
    }
}
