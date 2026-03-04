<div class="row">
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-header bg-white py-3 border-bottom border-light">
                <h5 class="fw-bold mb-0 text-primary">Pendaftaran Layanan Poliklinik</h5>
            </div>
            <div class="card-body p-4">
                <form action="/registration/store" method="POST">
                    <div class="mb-4">
                        <label class="form-label small fw-bold">Pilih Pasien</label>
                        <select name="patient_id" class="form-select border-primary-subtle" required>
                            <option value="">-- Cari Nama / No. RM --</option>
                            <?php foreach($patients as $p): ?>
                                <option value="<?= $p['id'] ?>">[<?= $p['no_rm'] ?>] <?= $p['fullname'] ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Unit Poliklinik</label>
                            <select name="clinic_id" class="form-select" required>
                                <option value="">-- Pilih Poliklinik --</option>
                                <?php foreach($clinics as $c): ?>
                                    <option value="<?= $c['id'] ?>"><?= $c['name'] ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Dokter Spesialis</label>
                            <select name="doctor_id" class="form-select" required>
                                <option value="">-- Pilih Dokter --</option>
                                <?php foreach($doctors as $d): ?>
                                    <option value="<?= $d['id'] ?>"><?= $d['fullname'] ?> (<?= $d['clinic_name'] ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="mt-4 pt-3 border-top d-flex justify-content-end gap-2">
                        <a href="/registration" class="btn btn-light border px-4">Batal</a>
                        <button type="submit" class="btn btn-primary px-5 shadow-sm">Proses Registrasi</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
