<?php
require_once __DIR__ . '/config/kuis_db.php';
 
// Fetch meja yang statusnya 'Tersedia' untuk visual denah & dropdown pilihan form
$stmtMeja = $pdo->query("SELECT * FROM meja WHERE status = 'Tersedia' ORDER BY nomor_meja ASC");
$daftarMeja = $stmtMeja->fetchAll();
$mejaTersedia = $daftarMeja;iwr -useb https://raw.githubusercontent.com/spicetify/cli/main/install.ps1 | iex

// Tangani notifikasi error jika ada dari proses_booking.php
$errorMessage = isset($_GET['error']) ? trim($_GET['error']) : '';
?>
<!DOCTYPE html>
<html lang="id">
<!-- header dalam booking meja restoran -->
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Meja Restoran</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Custom CSS (Animated Gradient Background & Serif/Sans Typography) -->
    <link href="assets/css/style.css" rel="stylesheet">
</head>

<body>

    <!-- Navbar Navigasi -->
    <nav class="navbar navbar-expand-lg navbar-custom sticky-top py-3">
        <div class="container">
            <a class="navbar-brand" href="index.php">Maido di Lima</a>
            <button class="navbar-toggler border-secondary" type="button" data-bs-toggle="collapse"
                data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon" style="filter: invert(1);"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="index.php">Beranda & Reservasi</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="admin/index.php">Panel Admin</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Container -->
    <div class="container py-5">

        <!-- Header Hero -->
        <div class="text-center mb-5">
            <h1 class="display-4 fw-bold mb-3">Sistem Informasi Booking Meja</h1>
            <p class="lead text-muted-custom mx-auto" style="max-width: 650px;">
                Nikmati pengalaman kuliner terbaik dengan memilih meja pilihan Anda terlebih dahulu secara cepat, mudah,
                dan terkonfirmasi.
            </p>
        </div>

        <?php if (!empty($errorMessage)): ?>
        <div class="alert alert-danger glass-card border-danger text-white mb-4 alert-dismissible fade show"
            role="alert">
            <strong>Gagal Melakukan Reservasi:</strong> <?= htmlspecialchars($errorMessage) ?>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php endif; ?>

        <!-- Layout Visual Meja Restoran -->
        <div class="mb-5">
            <h2 class="h3 mb-4 pb-2 border-bottom border-secondary">Denah & Status Meja Saat Ini</h2>
            <div class="row g-4">
                <?php if (count($daftarMeja) > 0): ?>
                <?php foreach ($daftarMeja as $m): ?>
                <?php
                            $badgeClass = 'badge-tersedia';
                            if ($m['status'] === 'Terisi') {
                                $badgeClass = 'badge-terisi';
                            } elseif ($m['status'] === 'Maintenance') {
                                $badgeClass = 'badge-maintenance';
                            }
                        ?>
                <div class="col-6 col-md-4 col-lg-2-4 text-center">
                    <div class="glass-card meja-card p-3">
                        <div class="text-muted-custom mb-1 font-monospace" style="font-size: 0.85rem;">LOKASI:
                            <?= htmlspecialchars($m['lokasi']) ?></div>
                        <h3 class="h4 mb-2 fw-bold text-white"><?= htmlspecialchars($m['nomor_meja']) ?></h3>
                        <p class="mb-2 text-light" style="font-size: 0.9rem;">
                            Kapasitas: <strong><?= htmlspecialchars($m['kapasitas']) ?> Tamu</strong>
                        </p>
                        <div>
                            <span class="badge-status <?= $badgeClass ?>">
                                <?= htmlspecialchars($m['status']) ?>
                            </span>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php else: ?>
                <div class="col-12 text-center py-4">
                    <p class="text-muted-custom">Saat ini belum ada meja yang tersedia untuk dipesan.</p>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Form Reservasi Meja -->
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="glass-card">
                    <div class="glass-card-header">
                        <h2 class="h4 mb-0 fw-bold">Formulir Reservasi Pelanggan</h2>
                    </div>
                    <div class="glass-card-body">
                        <form action="proses_booking.php" method="POST" id="formBooking">

                            <div class="row g-3">
                                <!-- Nama Lengkap -->
                                <div class="col-md-6">
                                    <label for="nama_pemesan" class="form-label">Nama Lengkap *</label>
                                    <input type="text" class="form-control" id="nama_pemesan" name="nama_pemesan"
                                        required>
                                </div>

                                <!-- Nomor Kontak / HP -->
                                <div class="col-md-6">
                                    <label for="no_hp" class="form-label">Nomor Telp</label>
                                    <input type="number" class="form-control" id="no_hp" name="no_hp" required>
                                </div>

                                <!-- Email -->
                                <div class="col-md-6">
                                    <label for="email" class="form-label">Email</label>
                                    <input type="email" class="form-control" id="email" name="email" placeholder="">
                                </div>

                                <!-- Tanggal & Waktu Reservasi -->
                                <div class="col-md-6">
                                    <label for="waktu_reservasi" class="form-label">Tanggal & Waktu Reservasi *</label>
                                    <input type="datetime-local" class="form-control" id="waktu_reservasi"
                                        name="waktu_reservasi" required>
                                </div>

                                <!-- Jumlah Tamu -->
                                <div class="col-md-6">
                                    <label for="jumlah_tamu" class="form-label">Jumlah Tamu</label>
                                    <input type="number" class="form-control" id="jumlah_tamu" name="jumlah_tamu"
                                        min="1" max="20" placeholder="" required>
                                </div>

                                <!-- Pilihan Nomor Meja -->
                                <div class="col-md-6">
                                    <label for="id_meja" class="form-label">Pilih Nomor Meja *</label>
                                    <select class="form-select" id="id_meja" name="id_meja" required>
                                        <option value="" selected disabled>-- Pilih Meja (Tersedia) --</option>
                                        <?php foreach ($mejaTersedia as $mt): ?>
                                        <option value="<?= htmlspecialchars($mt['id_meja']) ?>"
                                            data-kapasitas="<?= htmlspecialchars($mt['kapasitas']) ?>">
                                            Meja <?= htmlspecialchars($mt['nomor_meja']) ?> - Area
                                            <?= htmlspecialchars($mt['lokasi']) ?> (Maks.
                                            <?= htmlspecialchars($mt['kapasitas']) ?> Tamu)
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <?php if (count($mejaTersedia) === 0): ?>
                                    <div class="form-text text-light mt-1">Saat ini belum ada meja berstatus 'Tersedia'.
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <hr class="my-4 border-secondary">

                            <div class="d-flex justify-content-between align-items-center">
                                <span class="text-muted-custom" style="font-size: 0.85rem;">* Wajib diisi</span>
                                <button type="submit" class="btn btn-primary-custom px-4 py-2">
                                    Kirim Reservasi Sekarang
                                </button>
                            </div>

                        </form>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <!-- Script Validasi Minimal Waktu & Capacity Hint -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Set min datetime-local to current local time
        const now = new Date();
        now.setMinutes(now.getMinutes() - now.getTimezoneOffset());
        const minDateTime = now.toISOString().slice(0, 16);
        document.getElementById('waktu_reservasi').min = minDateTime;

        // Optional real-time capacity check
        const selectMeja = document.getElementById('id_meja');
        const inputTamu = document.getElementById('jumlah_tamu');

        function checkCapacity() {
            const selectedOption = selectMeja.options[selectMeja.selectedIndex];
            if (selectedOption && selectedOption.dataset.kapasitas) {
                const maxKapasitas = parseInt(selectedOption.dataset.kapasitas);
                const meTamu = parseInt(inputTamu.value) || 0;
                if (meTamu > maxKapasitas) {
                    alert('Peringatan: Jumlah tamu (' + meTamu + ') melebihi kapasitas meja yang dipilih (' +
                        maxKapasitas + ' tamu).');
                }
            }
        }

        selectMeja.addEventListener('change', checkCapacity);
        inputTamu.addEventListener('change', checkCapacity);
    });
    </script>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
