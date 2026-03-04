<div class="row mb-4">
    <div class="col-12">
        <h4 class="fw-bold">SatuSehat Integration Monitoring</h4>
        <p class="text-muted small">Kelola sinkronisasi data rekam medis ke platform SatuSehat Kemenkes (HL7 FHIR).</p>
    </div>
</div>

<div class="card border-0 shadow-sm rounded-4 overflow-hidden">
    <table class="table table-hover align-middle mb-0">
        <thead class="bg-light text-muted small text-uppercase fw-bold">
            <tr>
                <th class="px-4 py-3">Nama Pasien</th>
                <th class="py-3">NIK</th>
                <th class="py-3 text-center">Status Sinkron</th>
                <th class="py-3">UUID SatuSehat</th>
                <th class="py-3 text-end px-4">Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($patients as $p): ?>
            <tr>
                <td class="px-4">
                    <div class="fw-bold"><?= $p['fullname'] ?></div>
                    <div class="small text-muted"><?= $p['no_rm'] ?></div>
                </td>
                <td><span class="font-monospace"><?= $p['nik'] ?></span></td>
                <td class="text-center">
                    <?php if($p['satusehat_id']): ?>
                        <span class="badge bg-success bg-opacity-10 text-success rounded-pill px-3 py-2">
                            <i class="bi bi-check-circle-fill me-1"></i> Terhubung
                        </span>
                    <?php else: ?>
                        <span class="badge bg-warning bg-opacity-10 text-warning rounded-pill px-3 py-2">
                            <i class="bi bi-exclamation-triangle-fill me-1"></i> Belum Sinkron
                        </span>
                    <?php endif; ?>
                </td>
                <td>
                    <small class="text-muted font-monospace"><?= $p['satusehat_id'] ?: '-' ?></small>
                </td>
                <td class="text-end px-4">
                    <?php if(!$p['satusehat_id']): ?>
                        <a href="/satusehat/sync/<?= $p['id'] ?>" class="btn btn-primary btn-sm px-3 shadow-sm">
                            <i class="bi bi-arrow-repeat me-1"></i> Sinkronkan
                        </a>
                    <?php else: ?>
                        <button class="btn btn-light btn-sm px-3 border" disabled>Update Data</button>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
