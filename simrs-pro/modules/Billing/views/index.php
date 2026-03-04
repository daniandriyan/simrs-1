<div class="row mb-4">
    <div class="col-12">
        <h4 class="fw-bold">Antrian Pembayaran (Billing)</h4>
        <p class="text-muted">Daftar layanan yang sudah selesai dan menunggu pembayaran.</p>
    </div>
</div>

<div class="card border-0 shadow-sm rounded-4 overflow-hidden">
    <table class="table table-hover align-middle mb-0">
        <thead class="bg-light">
            <tr>
                <th class="px-4">Pasien</th>
                <th>No. Rawat</th>
                <th>Total Obat</th>
                <th>Jasa Medis</th>
                <th>Total Tagihan</th>
                <th class="text-end px-4">Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($bills as $b): ?>
            <tr>
                <td class="px-4">
                    <div class="fw-bold"><?= $b['patient_name'] ?></div>
                    <div class="small text-muted"><?= $b['no_rm'] ?></div>
                </td>
                <td class="small text-muted"><?= $b['no_rawat'] ?></td>
                <td class="fw-bold">Rp <?= number_format((float)$b['total_obat'], 0, ',', '.') ?></td>
                <td class="fw-bold">Rp 50.000</td>
                <td class="fw-bold text-primary">Rp <?= number_format((float)$b['total_obat'] + 50000, 0, ',', '.') ?></td>
                <td class="text-end px-4">
                    <button class="btn btn-primary btn-sm px-4 shadow-sm">Bayar Sekarang</button>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if(empty($bills)): ?>
                <tr><td colspan="6" class="text-center py-5 text-muted">Tidak ada antrian pembayaran saat ini.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
