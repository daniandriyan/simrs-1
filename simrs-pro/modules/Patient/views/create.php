<div class="container-fluid">
    <div class="row">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-header bg-white py-3 border-bottom border-light">
                    <h5 class="fw-bold mb-0 text-primary">Registrasi Pasien Baru</h5>
                </div>
                <div class="card-body p-4">
                    <form action="/patient/store" method="POST">
                        <div class="row g-3">
                            <div class="col-md-12">
                                <label class="form-label small fw-bold">Nama Lengkap Pasien</label>
                                <input type="text" name="fullname" class="form-control" placeholder="Input Nama Sesuai Identitas" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">NIK (No. KTP)</label>
                                <input type="text" name="nik" class="form-control" placeholder="16 Digit NIK" required maxlength="16">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">Jenis Kelamin</label>
                                <select name="gender" class="form-select" required>
                                    <option value="">Pilih...</option>
                                    <option value="L">Laki-Laki</option>
                                    <option value="P">Perempuan</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">Tanggal Lahir</label>
                                <input type="date" name="birth_date" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">No. Telepon/WA</label>
                                <input type="text" name="phone" class="form-control" placeholder="08xxxxxxxxxx">
                            </div>
                            <div class="col-md-12">
                                <label class="form-label small fw-bold">Alamat Lengkap</label>
                                <textarea name="address" class="form-control" rows="3" placeholder="Alamat Domisili"></textarea>
                            </div>
                        </div>
                        <div class="mt-4 pt-3 border-top d-flex justify-content-end gap-2">
                            <a href="/patient" class="btn btn-light px-4">Batal</a>
                            <button type="submit" class="btn btn-primary px-5 shadow-sm">Simpan Pasien</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
