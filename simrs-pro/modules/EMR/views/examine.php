<div class="row g-4">
    <div class="col-lg-12">
        <h4 class="fw-bold mb-4">Input Pemeriksaan Medis (SOAP)</h4>
        <div class="alert alert-primary border-0 rounded-4 shadow-sm mb-4">
            <h6 class="fw-bold">Informasi Pasien:</h6>
            <div class="d-flex gap-4">
                <span>Nama: <strong><?= $patient['patient_name'] ?></strong></span>
                <span>No. RM: <strong><?= $patient['no_rm'] ?></strong></span>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="card-header bg-white py-3 border-bottom border-light">
                <h6 class="fw-bold mb-0">SOAP Pasien</h6>
            </div>
            <div class="card-body p-4">
                <form action="/emr/save" method="POST">
                    <input type="hidden" name="registration_id" value="<?= $patient['id'] ?>">
                    
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-primary">Subjective (Keluhan)</label>
                            <textarea name="subjective" class="form-control" rows="3" placeholder="Apa yang dirasakan pasien?"></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-primary">Objective (Pemeriksaan)</label>
                            <textarea name="objective" class="form-control" rows="3" placeholder="Hasil pemeriksaan fisik/laboratorium"></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-primary">Assessment (Diagnosa)</label>
                            <textarea name="assessment" class="form-control" rows="3" placeholder="Tuliskan diagnosa medis"></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-primary">Plan (Rencana)</label>
                            <textarea name="plan" class="form-control" rows="3" placeholder="Tindakan atau terapi medis"></textarea>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-header bg-white py-3 border-bottom border-light">
                <h6 class="fw-bold mb-0">E-Resep Obat</h6>
            </div>
            <div class="card-body p-4">
                <div class="row g-3 align-items-end">
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Pilih Obat</label>
                        <select class="form-select">
                            <?php foreach($medicines as $m): ?>
                                <option value="<?= $m['id'] ?>"><?= $m['name'] ?> (Stok: <?= $m['stock'] ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-bold">Jumlah</label>
                        <input type="number" class="form-control">
                    </div>
                    <div class="col-md-3">
                        <button class="btn btn-outline-primary w-100">Tambah Obat</button>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="mt-4 d-flex justify-content-end gap-2">
            <button class="btn btn-primary px-5 shadow-sm">Simpan Pemeriksaan & Resep</button>
        </div>
    </div>
</div>
