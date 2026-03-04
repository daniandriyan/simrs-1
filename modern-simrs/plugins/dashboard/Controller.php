<?php
namespace Plugins\Dashboard;

class Controller
{
    protected $core;

    public function __construct($core)
    {
        $this->core = $core;
    }

    public function index()
    {
        // Data dummy untuk statistik dashboard
        $stats = [
            'total_pasien' => 1250,
            'pasien_hari_ini' => 45,
            'kunjungan_igd' => 12,
            'stok_obat_kritis' => 8
        ];

        require_once __DIR__ . '/view/index.php';
    }
}
