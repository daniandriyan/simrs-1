<div class="row mb-4">
    <div class="col-12">
        <h3 class="fw-bold">Dashboard SIMRS Pro</h3>
        <p class="text-muted">Selamat datang di sistem manajemen kesehatan modern.</p>
    </div>
</div>

<div class="row g-4">
    <div class="col-md-3">
        <div class="card p-4 border-0 shadow-sm rounded-4 bg-primary text-white">
            <h6 class="opacity-75">Total Pasien</h6>
            <h2 class="fw-bold mb-0"><?= $stats['pasien'] ?></h2>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card p-4 border-0 shadow-sm rounded-4 bg-white">
            <h6 class="text-muted">Antrian Hari Ini</h6>
            <h2 class="fw-bold mb-0 text-dark"><?= $stats['antrian'] ?></h2>
        </div>
    </div>
</div>
