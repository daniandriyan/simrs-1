<div class="row g-4 mb-4">
    <div class="col-md-3">
        <div class="card border-0 shadow-sm rounded-4 p-4 bg-white">
            <small class="text-muted d-block mb-1">Total Pendapatan</small>
            <h4 class="fw-bold text-primary mb-0">Rp <?= number_format((float)$summary['total_revenue'], 0, ',', '.') ?></h4>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm rounded-4 p-4 bg-white">
            <small class="text-muted d-block mb-1">Total Pasien</small>
            <h4 class="fw-bold text-dark mb-0"><?= number_format($summary['total_patients']) ?></h4>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm rounded-4 p-4 bg-white">
            <small class="text-muted d-block mb-1">Total Kunjungan</small>
            <h4 class="fw-bold text-dark mb-0"><?= number_format($summary['total_visits']) ?></h4>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm rounded-4 p-4 bg-white">
            <small class="text-muted d-block mb-1">Rawat Inap Aktif</small>
            <h4 class="fw-bold text-danger mb-0"><?= $summary['active_inpatient'] ?></h4>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm rounded-4 p-4 bg-white h-100">
            <h6 class="fw-bold mb-4">Tren Pendapatan</h6>
            <canvas id="revenueChart" height="250"></canvas>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm rounded-4 p-4 bg-white h-100">
            <h6 class="fw-bold mb-4">Kunjungan per Poliklinik</h6>
            <canvas id="serviceChart"></canvas>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const revCtx = document.getElementById('revenueChart').getContext('2d');
new Chart(revCtx, {
    type: 'line',
    data: {
        labels: [<?php foreach($revenue as $r) echo "'" . date('d/m', strtotime($r['date'])) . "',"; ?>],
        datasets: [{
            label: 'Pendapatan (Rp)',
            data: [<?php foreach($revenue as $r) echo $r['total'] . ","; ?>],
            borderColor: '#0d6efd',
            backgroundColor: 'rgba(13, 110, 253, 0.1)',
            fill: true,
            tension: 0.4
        }]
    },
    options: { plugins: { legend: { display: false } } }
});

const serCtx = document.getElementById('serviceChart').getContext('2d');
new Chart(serCtx, {
    type: 'doughnut',
    data: {
        labels: [<?php foreach($services as $s) echo "'" . $s['name'] . "',"; ?>],
        datasets: [{
            data: [<?php foreach($services as $s) echo $s['total'] . ","; ?>],
            backgroundColor: ['#0d6efd', '#198754', '#ffc107', '#dc3545', '#6610f2']
        }]
    }
});
</script>
