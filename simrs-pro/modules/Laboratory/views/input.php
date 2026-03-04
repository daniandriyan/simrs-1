<div class="row">
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-header bg-white py-3 border-bottom border-light">
                <h5 class="fw-bold mb-0 text-primary">Input Hasil Laboratorium</h5>
            </div>
            <div class="card-body p-4">
                <div class="alert alert-primary border-0 rounded-4 mb-4">
                    <small class="d-block text-uppercase fw-bold opacity-75">Pasien:</small>
                    <h6 class="fw-bold mb-0"><?= $order['patient_name'] ?> (<?= $order['no_rm'] ?>)</h6>
                </div>

                <form action="/laboratory/save" method="POST">
                    <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                    
                    <div class="mb-4">
                        <label class="form-label small fw-bold">Jenis Pemeriksaan</label>
                        <input type="text" class="form-control bg-light" value="<?= $order['test_name'] ?>" readonly>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Hasil Pemeriksaan</label>
                            <div class="input-group">
                                <input type="text" name="result_value" class="form-control border-primary" placeholder="Contoh: 14.5" required autofocus>
                                <span class="input-group-text bg-primary text-white border-primary"><?= $order['unit'] ?></span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Nilai Normal</label>
                            <input type="text" class="form-control bg-light" value="<?= $order['normal_range'] ?>" readonly>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label small fw-bold">Catatan / Kesan</label>
                        <textarea name="notes" class="form-control" rows="3" placeholder="Tuliskan interpretasi hasil jika perlu..."></textarea>
                    </div>

                    <div class="pt-3 border-top d-flex justify-content-end gap-2">
                        <a href="/laboratory" class="btn btn-light px-4">Batal</a>
                        <button type="submit" class="btn btn-primary px-5 shadow-sm">Simpan Hasil</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
