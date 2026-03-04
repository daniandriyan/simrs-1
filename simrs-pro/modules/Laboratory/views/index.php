<div class="row mb-4">
    <div class="col-12">
        <h4 class="fw-bold">Antrian Pemeriksaan Laboratorium</h4>
        <p class="text-muted small">Daftar permintaan tes diagnostik dari poli/IGD.</p>
    </div>
</div>

<div class="card border-0 shadow-sm rounded-4 overflow-hidden">
    <table class="table table-hover align-middle mb-0">
        <thead class="bg-light text-muted small text-uppercase fw-bold">
            <tr>
                <th class="px-4 py-3">Waktu Order</th>
                <th class="py-3">Pasien</th>
                <th class="py-3">Pemeriksaan</th>
                <th class="py-3">Kategori</th>
                <th class="py-3 text-end px-4">Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($orders as $o): ?>
            <tr>
                <td class="px-4 small"><?= date('H:i', strtotime($o['ordered_at'])) ?> WIB</td>
                <td>
                    <div class="fw-bold"><?= $o['patient_name'] ?></div>
                    <div class="small text-muted"><?= $o['no_rm'] ?></div>
                </td>
                <td class="fw-bold text-primary"><?= $o['test_name'] ?></td>
                <td><span class="badge bg-secondary bg-opacity-10 text-secondary"><?= $o['category'] ?></span></td>
                <td class="text-end px-4">
                    <a href="/laboratory/input/<?= $o['id'] ?>" class="btn btn-primary btn-sm px-3 shadow-sm">Input Hasil</a>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if(empty($orders)): ?>
                <tr><td colspan="5" class="text-center py-5 text-muted">Tidak ada antrian permintaan lab.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
