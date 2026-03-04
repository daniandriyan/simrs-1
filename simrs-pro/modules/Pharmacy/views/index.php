<div class="row mb-4">
    <div class="col-12">
        <h4 class="fw-bold">Antrian Resep Digital</h4>
        <p class="text-muted">Proses penyerahan obat berdasarkan resep dokter.</p>
    </div>
</div>

<div class="card border-0 shadow-sm rounded-4 overflow-hidden">
    <table class="table table-hover align-middle mb-0">
        <thead class="bg-light">
            <tr>
                <th class="px-4">Pasien</th>
                <th>Nama Obat</th>
                <th>Qty</th>
                <th>Instruksi</th>
                <th>Status</th>
                <th class="text-end px-4">Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($prescriptions as $p): ?>
            <tr>
                <td class="px-4">
                    <div class="fw-bold"><?= $p['patient_name'] ?></div>
                    <div class="small text-muted"><?= $p['no_rm'] ?></div>
                </td>
                <td class="fw-bold text-primary"><?= $p['med_name'] ?></td>
                <td><?= $p['qty'] ?></td>
                <td><small><?= $p['instruction'] ?></small></td>
                <td><span class="badge bg-info bg-opacity-10 text-info px-3 py-2">Pending</span></td>
                <td class="text-end px-4">
                    <button class="btn btn-success btn-sm px-3">Selesaikan</button>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if(empty($prescriptions)): ?>
                <tr><td colspan="6" class="text-center py-5 text-muted">Tidak ada antrian resep saat ini.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
