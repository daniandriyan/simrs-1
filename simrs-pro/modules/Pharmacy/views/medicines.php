<div class="row mb-4">
    <div class="col-12">
        <h4 class="fw-bold">Manajemen Stok Obat</h4>
        <p class="text-muted">Daftar inventaris obat-obatan farmasi.</p>
    </div>
</div>

<div class="card border-0 shadow-sm rounded-4 overflow-hidden">
    <table class="table table-hover align-middle mb-0">
        <thead class="bg-light">
            <tr>
                <th class="px-4">Nama Obat</th>
                <th>Kategori/Unit</th>
                <th>Harga Satuan</th>
                <th>Stok</th>
                <th class="text-end px-4">Status</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($medicines as $m): ?>
            <tr>
                <td class="px-4 fw-bold"><?= $m['name'] ?></td>
                <td><?= $m['unit'] ?></td>
                <td class="fw-bold text-primary">Rp <?= number_format($m['price'], 0, ',', '.') ?></td>
                <td class="fw-bold"><?= $m['stock'] ?></td>
                <td class="text-end px-4">
                    <?php if($m['stock'] < 10): ?>
                        <span class="badge bg-danger bg-opacity-10 text-danger px-3 py-2">Stok Kritis</span>
                    <?php else: ?>
                        <span class="badge bg-success bg-opacity-10 text-success px-3 py-2">Tersedia</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
