<div class="row mb-4">
    <div class="col-12">
        <h4 class="fw-bold">Antrian Pemeriksaan Dokter</h4>
        <p class="text-muted">Daftar pasien yang menunggu pemeriksaan hari ini.</p>
    </div>
</div>

<div class="card border-0 shadow-sm rounded-4 overflow-hidden">
    <table class="table table-hover align-middle mb-0">
        <thead class="bg-light">
            <tr>
                <th class="px-4">No. Antri</th>
                <th>Nama Pasien</th>
                <th>No. RM</th>
                <th>Poliklinik</th>
                <th>Status</th>
                <th class="text-end px-4">Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($patients as $p): ?>
            <tr>
                <td class="px-4"><span class="badge bg-primary rounded-pill px-3"><?= $p['no_reg'] ?></span></td>
                <td class="fw-bold"><?= $p['patient_name'] ?></td>
                <td class="font-monospace text-muted small"><?= $p['no_rm'] ?></td>
                <td><?= $p['clinic_name'] ?></td>
                <td><span class="badge bg-warning bg-opacity-10 text-warning px-3 py-2">Menunggu</span></td>
                <td class="text-end px-4">
                    <a href="/emr/examine/<?= $p['id'] ?>" class="btn btn-primary btn-sm px-3">Periksa</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
