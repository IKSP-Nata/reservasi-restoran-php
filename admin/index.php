<?php
require_once __DIR__ . '/../config/kuis_db.php';

$successMsg = '';
$errorMsg = '';

// Handling POST actions (Update Status atau Hapus Reservasi)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = isset($_POST['action']) ? $_POST['action'] : '';

    if ($action === 'update_status') {
        $idReservasi = isset($_POST['id_reservasi']) ? filter_var($_POST['id_reservasi'], FILTER_VALIDATE_INT) : 0;
        $statusBaru = isset($_POST['status_booking']) ? trim($_POST['status_booking']) : '';
        $allowedStatus = ['Pending', 'Dikonfirmasi', 'Selesai', 'Batal'];

        if ($idReservasi && in_array($statusBaru, $allowedStatus)) {
            // Ambil data reservasi untuk mendapatkan id_meja
            $stmtGetMeja = $pdo->prepare("SELECT id_meja FROM reservasi WHERE id_reservasi = :id");
            $stmtGetMeja->execute([':id' => $idReservasi]);
            $res = $stmtGetMeja->fetch();

            $stmtUpdate = $pdo->prepare("UPDATE reservasi SET status_booking = :status WHERE id_reservasi = :id");
            $stmtUpdate->execute([':status' => $statusBaru, ':id' => $idReservasi]);

            // Sinkronkan status meja
            if ($res) {
                $mejaStatus = in_array($statusBaru, ['Pending', 'Dikonfirmasi']) ? 'Terisi' : 'Tersedia';
                $stmtUpdateMeja = $pdo->prepare("UPDATE meja SET status = :status WHERE id_meja = :id_meja");
                $stmtUpdateMeja->execute([':status' => $mejaStatus, ':id_meja' => $res['id_meja']]);
            }

            $successMsg = "Status reservasi #RSV-" . str_pad($idReservasi, 5, '0', STR_PAD_LEFT) . " berhasil diperbarui menjadi {$statusBaru}.";
        } else {
            $errorMsg = "Gagal memperbarui status: Data tidak valid.";
        }
    } elseif ($action === 'hapus') {
        $idReservasi = isset($_POST['id_reservasi']) ? filter_var($_POST['id_reservasi'], FILTER_VALIDATE_INT) : 0;
        if ($idReservasi) {
            // Ambil id_meja sebelum dihapus
            $stmtGetMeja = $pdo->prepare("SELECT id_meja FROM reservasi WHERE id_reservasi = :id");
            $stmtGetMeja->execute([':id' => $idReservasi]);
            $res = $stmtGetMeja->fetch();

            $stmtDelete = $pdo->prepare("DELETE FROM reservasi WHERE id_reservasi = :id");
            $stmtDelete->execute([':id' => $idReservasi]);

            // Kembalikan status meja menjadi 'Tersedia'
            if ($res) {
                $stmtUpdateMeja = $pdo->prepare("UPDATE meja SET status = 'Tersedia' WHERE id_meja = :id_meja");
                $stmtUpdateMeja->execute([':id_meja' => $res['id_meja']]);
            }

            $successMsg = "Data reservasi #RSV-" . str_pad($idReservasi, 5, '0', STR_PAD_LEFT) . " telah dihapus dari sistem.";
        } else {
            $errorMsg = "Gagal menghapus data: ID Reservasi tidak valid.";
        }
    }
}

// Filter Status
$filterStatus = isset($_GET['status']) ? trim($_GET['status']) : '';
$whereClause = "";
$params = [];

if (in_array($filterStatus, ['Pending', 'Dikonfirmasi', 'Selesai', 'Batal'])) {
    $whereClause = "WHERE r.status_booking = :status";
    $params[':status'] = $filterStatus;
}

// Query Daftar Reservasi
$sql = "
    SELECT r.*, m.nomor_meja, m.lokasi 
    FROM reservasi r
    JOIN meja m ON r.id_meja = m.id_meja
    {$whereClause}
    ORDER BY r.waktu_reservasi DESC
";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$daftarReservasi = $stmt->fetchAll();

// Hitung Statistik
$stats = [
    'Total' => $pdo->query("SELECT COUNT(*) FROM reservasi")->fetchColumn(),
    'Pending' => $pdo->query("SELECT COUNT(*) FROM reservasi WHERE status_booking = 'Pending'")->fetchColumn(),
    'Dikonfirmasi' => $pdo->query("SELECT COUNT(*) FROM reservasi WHERE status_booking = 'Dikonfirmasi'")->fetchColumn(),
    'Selesai' => $pdo->query("SELECT COUNT(*) FROM reservasi WHERE status_booking = 'Selesai'")->fetchColumn(),
    'Batal' => $pdo->query("SELECT COUNT(*) FROM reservasi WHERE status_booking = 'Batal'")->fetchColumn()
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Kelola Reservasi</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body>

    <!-- Admin Navbar -->
    <nav class="navbar navbar-expand-lg navbar-custom sticky-top py-3">
        <div class="container">
            <a class="navbar-brand" href="index.php">Maido di Lima <span class="badge bg-secondary font-monospace" style="font-size: 0.75rem;">ADMIN</span></a>
            <button class="navbar-toggler border-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#adminNavbar">
                <span class="navbar-toggler-icon" style="filter: invert(1);"></span>
            </button>
            <div class="collapse navbar-collapse" id="adminNavbar">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="index.php">Kelola Reservasi</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="meja.php">Kelola Meja</a>
                    </li>
                    <li class="nav-item ms-lg-3">
                        <a href="../index.php" class="btn btn-outline-custom btn-sm">Lihat Website Publik</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container py-4">
        
        <!-- Dashboard Header -->
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 pb-2 border-bottom border-secondary">
            <div>
                <h1 class="h2 fw-bold mb-1">Kelola Reservasi Restoran</h1>
                <p class="text-muted-custom mb-0">Manajemen data pemesanan meja dan status konfirmasi pelanggan.</p>
            </div>
            <div class="mt-3 mt-md-0">
                <a href="tambah_reservasi.php" class="btn btn-primary-custom">
                    + Tambah Booking Manual
                </a>
            </div>
        </div>

        <!-- Alert Notification -->
        <?php if (!empty($successMsg)): ?>
            <div class="alert alert-success glass-card border-success text-white mb-4 alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($successMsg) ?>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if (!empty($errorMsg)): ?>
            <div class="alert alert-danger glass-card border-danger text-white mb-4 alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($errorMsg) ?>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <!-- Statistics Counter Cards -->
        <div class="row g-3 mb-4">
            <div class="col-6 col-md-2-4">
                <a href="index.php" class="text-decoration-none">
                    <div class="glass-card p-3 text-center">
                        <div class="text-muted-custom mb-1" style="font-size: 0.8rem;">TOTAL RESERVASI</div>
                        <h2 class="h3 fw-bold text-white mb-0"><?= $stats['Total'] ?></h2>
                    </div>
                </a>
            </div>
            <div class="col-6 col-md-2-4">
                <a href="index.php?status=Pending" class="text-decoration-none">
                    <div class="glass-card p-3 text-center">
                        <div class="text-light mb-1" style="font-size: 0.8rem;">PENDING</div>
                        <h2 class="h3 fw-bold text-light mb-0"><?= $stats['Pending'] ?></h2>
                    </div>
                </a>
            </div>
            <div class="col-6 col-md-2-4">
                <a href="index.php?status=Dikonfirmasi" class="text-decoration-none">
                    <div class="glass-card p-3 text-center">
                        <div class="text-info mb-1" style="font-size: 0.8rem;">DIKONFIRMASI</div>
                        <h2 class="h3 fw-bold text-info mb-0"><?= $stats['Dikonfirmasi'] ?></h2>
                    </div>
                </a>
            </div>
            <div class="col-6 col-md-2-4">
                <a href="index.php?status=Selesai" class="text-decoration-none">
                    <div class="glass-card p-3 text-center">
                        <div class="text-success mb-1" style="font-size: 0.8rem;">SELESAI</div>
                        <h2 class="h3 fw-bold text-success mb-0"><?= $stats['Selesai'] ?></h2>
                    </div>
                </a>
            </div>
            <div class="col-6 col-md-2-4">
                <a href="index.php?status=Batal" class="text-decoration-none">
                    <div class="glass-card p-3 text-center">
                        <div class="text-danger mb-1" style="font-size: 0.8rem;">BATAL</div>
                        <h2 class="h3 fw-bold text-danger mb-0"><?= $stats['Batal'] ?></h2>
                    </div>
                </a>
            </div>
        </div>

        <!-- Filter Tab Navigation -->
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2 class="h5 fw-bold mb-0">Daftar Pemesanan <?= !empty($filterStatus) ? "({$filterStatus})" : "(Semua)" ?></h2>
            <div>
                <a href="index.php" class="btn btn-sm <?= empty($filterStatus) ? 'btn-light' : 'btn-outline-secondary text-white' ?> me-1">Semua</a>
                <a href="index.php?status=Pending" class="btn btn-sm <?= $filterStatus === 'Pending' ? 'btn-secondary' : 'btn-outline-secondary text-white' ?> me-1">Pending</a>
                <a href="index.php?status=Dikonfirmasi" class="btn btn-sm <?= $filterStatus === 'Dikonfirmasi' ? 'btn-info' : 'btn-outline-info' ?> me-1">Dikonfirmasi</a>
                <a href="index.php?status=Selesai" class="btn btn-sm <?= $filterStatus === 'Selesai' ? 'btn-success' : 'btn-outline-success' ?> me-1">Selesai</a>
                <a href="index.php?status=Batal" class="btn btn-sm <?= $filterStatus === 'Batal' ? 'btn-danger' : 'btn-outline-danger' ?>">Batal</a>
            </div>
        </div>

        <!-- Tabel Daftar Reservasi -->
        <div class="glass-card">
            <div class="table-responsive">
                <table class="table table-custom mb-0">
                    <thead>
                        <tr>
                            <th>Kode & Tanggal</th>
                            <th>Nama Pelanggan</th>
                            <th>Kontak</th>
                            <th>Meja & Lokasi</th>
                            <th>Waktu Reservasi</th>
                            <th>Jumlah Tamu</th>
                            <th>Status</th>
                            <th class="text-center">Aksi / Ubah Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($daftarReservasi) > 0): ?>
                            <?php foreach ($daftarReservasi as $r): ?>
                                <?php
                                    $kode = 'RSV-' . str_pad($r['id_reservasi'], 5, '0', STR_PAD_LEFT);
                                    $statusClass = 'badge-pending';
                                    if ($r['status_booking'] === 'Dikonfirmasi') $statusClass = 'badge-dikonfirmasi';
                                    elseif ($r['status_booking'] === 'Selesai') $statusClass = 'badge-selesai';
                                    elseif ($r['status_booking'] === 'Batal') $statusClass = 'badge-batal';
                                ?>
                                <tr>
                                    <td>
                                        <strong class="font-monospace text-light"><?= htmlspecialchars($kode) ?></strong>
                                        <div class="text-muted-custom" style="font-size: 0.75rem;"><?= date('d/m/Y H:i', strtotime($r['created_at'])) ?></div>
                                    </td>
                                    <td>
                                        <span class="customer-name-tag"><?= htmlspecialchars($r['nama_pemesan']) ?></span>
                                    </td>
                                    <td>
                                        <div><?= htmlspecialchars($r['no_hp']) ?></div>
                                        <?php if (!empty($r['email'])): ?>
                                            <div class="text-muted-custom" style="font-size: 0.8rem;"><?= htmlspecialchars($r['email']) ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <strong class="text-accent">Meja <?= htmlspecialchars($r['nomor_meja']) ?></strong>
                                        <div class="text-muted-custom" style="font-size: 0.8rem;"><?= htmlspecialchars($r['lokasi']) ?></div>
                                    </td>
                                    <td>
                                        <div><?= date('d M Y', strtotime($r['waktu_reservasi'])) ?></div>
                                        <div class="fw-bold text-light"><?= date('H:i', strtotime($r['waktu_reservasi'])) ?> WIB</div>
                                    </td>
                                    <td><?= htmlspecialchars($r['jumlah_tamu']) ?> Orang</td>
                                    <td>
                                        <span class="badge-status <?= $statusClass ?>">
                                            <?= htmlspecialchars($r['status_booking']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="d-flex justify-content-center align-items-center gap-2">
                                            <!-- Form Direct Update Status -->
                                            <form action="index.php<?= !empty($filterStatus) ? '?status=' . urlencode($filterStatus) : '' ?>" method="POST" class="d-inline-block">
                                                <input type="hidden" name="action" value="update_status">
                                                <input type="hidden" name="id_reservasi" value="<?= htmlspecialchars($r['id_reservasi']) ?>">
                                                <select name="status_booking" class="form-select form-select-sm d-inline-block w-auto" onchange="this.form.submit()">
                                                    <option value="Pending" <?= $r['status_booking'] === 'Pending' ? 'selected' : '' ?>>Pending</option>
                                                    <option value="Dikonfirmasi" <?= $r['status_booking'] === 'Dikonfirmasi' ? 'selected' : '' ?>>Dikonfirmasi</option>
                                                    <option value="Selesai" <?= $r['status_booking'] === 'Selesai' ? 'selected' : '' ?>>Selesai</option>
                                                    <option value="Batal" <?= $r['status_booking'] === 'Batal' ? 'selected' : '' ?>>Batal</option>
                                                </select>
                                            </form>

                                            <!-- Form Hapus -->
                                            <form action="index.php<?= !empty($filterStatus) ? '?status=' . urlencode($filterStatus) : '' ?>" method="POST" class="d-inline-block" onsubmit="return confirm('Apakah Anda yakin ingin menghapus data reservasi ini?');">
                                                <input type="hidden" name="action" value="hapus">
                                                <input type="hidden" name="id_reservasi" value="<?= htmlspecialchars($r['id_reservasi']) ?>">
                                                <button type="submit" class="btn btn-outline-danger btn-sm">Hapus</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="text-center py-4 text-muted-custom">
                                    Tidak ada data reservasi ditemukan.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
    <!--Bootstrap index.php -->
    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
