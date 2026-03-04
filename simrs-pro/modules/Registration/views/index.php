<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="fw-bold">Antrian Registrasi Hari Ini</h4>
    <a href="/registration/create" class="btn btn-primary px-4 shadow-sm"><i class="bi bi-plus-lg"></i> Daftar Layanan</a>
</div>

<div class="card border-0 shadow-sm rounded-4 overflow-hidden">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="bg-light">
                <tr>
                    <th class="px-4">No. Antri</th>
                    <th>No. Rawat</th>
                    <th>Pasien</th>
                    <th>Poli / Dokter</th>
                    <th>Status</th>
                    <th class="text-end px-4">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($registrations as $r): ?>
                <tr>
                    <td class="px-4"><span class="badge bg-primary rounded-pill px-3"><?= $r['no_reg'] ?></span></td>
                    <td class="small fw-bold text-muted"><?= $r['no_rawat'] ?></td>
                    <td>
                        <div class="fw-bold"><?= $r['patient_name'] ?></div>
                        <div class="small text-muted"><?= $r['no_rm'] ?></div>
                    </td>
                    <td>
                        <div class="fw-medium text-primary"><?= $r['clinic_name'] ?></div>
                        <div class="small text-muted"><?= $r['doctor_name'] ?></div>
                    </td>
                    <td>
                        <span class="badge bg-warning bg-opacity-10 text-warning rounded-pill px-3"><?= $r['status'] ?></span>
                    </td>
                    <td class="text-end px-4">
                        <button class="btn btn-sm btn-light border">Batal</button>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if(empty($registrations)): ?>
                    <tr><td colspan="6" class="text-center py-5 text-muted">Belum ada antrian registrasi hari ini.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
