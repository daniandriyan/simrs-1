<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="fw-bold">Data Pasien</h4>
    <a href="/patient/create" class="btn btn-primary px-4 shadow-sm"><i class="bi bi-plus-lg"></i> Tambah Pasien</a>
</div>

<div class="card border-0 shadow-sm rounded-4 overflow-hidden">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="bg-light">
                <tr>
                    <th class="px-4">No. RM</th>
                    <th>Nama Pasien</th>
                    <th>NIK</th>
                    <th>Jenis Kelamin</th>
                    <th>Tanggal Lahir</th>
                    <th class="text-end px-4">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if(empty($patients)): ?>
                <tr>
                    <td colspan="6" class="text-center py-5 text-muted">Belum ada data pasien.</td>
                </tr>
                <?php else: ?>
                    <?php foreach($patients as $p): ?>
                    <tr>
                        <td class="px-4 font-monospace text-primary fw-bold"><?= $p['no_rm'] ?></td>
                        <td class="fw-bold"><?= $p['fullname'] ?></td>
                        <td><?= $p['nik'] ?></td>
                        <td><?= $p['gender'] == 'L' ? 'Laki-Laki' : 'Perempuan' ?></td>
                        <td><?= $p['birth_date'] ?></td>
                        <td class="text-end px-4">
                            <button class="btn btn-sm btn-light border">Edit</button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
