<?php
session_start();

if (
    !isset($_SESSION["id_user"]) ||
    !in_array($_SESSION["role"], ["admin", "petugas"], true)
) {
    header("Location: index.php");
    exit;
}

require_once "koneksi.php";

$isAdmin = $_SESSION["role"] === "admin";
$pesan = "";
$jenisPesan = "success";

function aman($teks) {
    return htmlspecialchars((string) $teks, ENT_QUOTES, "UTF-8");
}

// Tambah pengajuan hanya untuk admin
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["tambah"])) {
    if (!$isAdmin) {
        header("Location: peminjaman.php");
        exit;
    }

    $idUser = (int) ($_POST["id_user"] ?? 0);
    $idAlat = (int) ($_POST["id_alat"] ?? 0);
    $jumlah = (int) ($_POST["jumlah"] ?? 0);
    $tanggalPinjam = $_POST["tanggal_pinjam"] ?? "";
    $tanggalKembali = $_POST["tanggal_kembali_rencana"] ?? "";

    if (
        $idUser < 1 ||
        $idAlat < 1 ||
        $jumlah < 1 ||
        $tanggalPinjam === "" ||
        $tanggalKembali === "" ||
        $tanggalKembali < $tanggalPinjam
    ) {
        $pesan = "Periksa kembali data peminjaman dan tanggalnya.";
        $jenisPesan = "error";
    } else {
        $stmt = $koneksi->prepare(
            "SELECT id_user FROM users
             WHERE id_user = ? AND role = 'peminjam'"
        );
        $stmt->bind_param("i", $idUser);
        $stmt->execute();
        $validPeminjam = $stmt->get_result()->num_rows > 0;
        $stmt->close();

        $stmt = $koneksi->prepare(
            "SELECT id_alat FROM alat WHERE id_alat = ?"
        );
        $stmt->bind_param("i", $idAlat);
        $stmt->execute();
        $validAlat = $stmt->get_result()->num_rows > 0;
        $stmt->close();

        if (!$validPeminjam || !$validAlat) {
            $pesan = "Akun peminjam atau alat tidak ditemukan.";
            $jenisPesan = "error";
        } else {
            $stmt = $koneksi->prepare(
                "INSERT INTO peminjaman
                 (id_user, id_alat, jumlah, tanggal_pinjam,
                  tanggal_kembali_rencana, status)
                 VALUES (?, ?, ?, ?, ?, 'menunggu')"
            );

            $stmt->bind_param(
                "iiiss",
                $idUser,
                $idAlat,
                $jumlah,
                $tanggalPinjam,
                $tanggalKembali
            );

            if ($stmt->execute()) {
                $pesan = "Pengajuan peminjaman berhasil ditambahkan.";
            } else {
                $pesan = "Pengajuan gagal disimpan.";
                $jenisPesan = "error";
            }

            $stmt->close();
        }
    }
}

// Persetujuan peminjaman oleh admin atau petugas
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["setujui"])) {
    $idPeminjaman = (int) ($_POST["id_peminjaman"] ?? 0);

    try {
        $koneksi->begin_transaction();

        $stmt = $koneksi->prepare(
            "SELECT status FROM peminjaman
             WHERE id_peminjaman = ? FOR UPDATE"
        );
        $stmt->bind_param("i", $idPeminjaman);
        $stmt->execute();
        $pinjaman = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$pinjaman || $pinjaman["status"] !== "menunggu") {
            throw new Exception(
                "Pengajuan tidak ditemukan atau sudah diproses."
            );
        }

        // Perubahan status akan mengaktifkan trigger database.
        $stmt = $koneksi->prepare(
            "UPDATE peminjaman
             SET status = 'disetujui'
             WHERE id_peminjaman = ? AND status = 'menunggu'"
        );
        $stmt->bind_param("i", $idPeminjaman);
        $stmt->execute();
        $berhasil = $stmt->affected_rows;
        $stmt->close();

        if ($berhasil !== 1) {
            throw new Exception("Status peminjaman gagal diperbarui.");
        }

        $stmt = $koneksi->prepare(
            "INSERT INTO log_aktivitas (id_user, aktivitas)
             VALUES (?, ?)"
        );
        $aktivitas = "Menyetujui peminjaman nomor " . $idPeminjaman;
        $idPetugas = (int) $_SESSION["id_user"];
        $stmt->bind_param("is", $idPetugas, $aktivitas);
        $stmt->execute();
        $stmt->close();

        $koneksi->commit();
        $pesan = "Peminjaman berhasil disetujui.";
    } catch (Throwable $e) {
        $koneksi->rollback();
        $pesan = $e->getMessage();
        $jenisPesan = "error";
    }
}

// Data untuk form admin
$dataPeminjam = $koneksi->query(
    "SELECT id_user, nama, username
     FROM users
     WHERE role = 'peminjam'
     ORDER BY nama"
);

$dataAlatForm = $koneksi->query(
    "SELECT id_alat, nama_alat, stok
     FROM alat
     ORDER BY nama_alat"
);

// Daftar peminjaman
$dataPinjaman = $koneksi->query(
    "SELECT p.id_peminjaman, p.jumlah, p.tanggal_pinjam,
            p.tanggal_kembali_rencana, p.status,
            u.nama, a.nama_alat
     FROM peminjaman p
     JOIN users u ON p.id_user = u.id_user
     JOIN alat a ON p.id_alat = a.id_alat
     ORDER BY p.id_peminjaman DESC"
);

if (!$dataPeminjam || !$dataAlatForm || !$dataPinjaman) {
    die("Gagal mengambil data. Periksa struktur tabel database.");
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Peminjaman</title>

    <style>
        * { box-sizing: border-box; }

        body {
            font-family: Arial, sans-serif;
            background: #f1f5f9;
            color: #1e293b;
            margin: 0;
            padding: 25px;
        }

        .container {
            max-width: 1100px;
            margin: auto;
        }

        .panel {
            background: white;
            padding: 22px;
            border-radius: 12px;
            margin-bottom: 20px;
            box-shadow: 0 3px 12px rgba(0,0,0,0.05);
        }

        h1, h2 { margin-top: 0; }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 12px;
        }

        input, select {
            width: 100%;
            padding: 11px;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            background: white;
        }

        button, .link {
            display: inline-block;
            padding: 10px 15px;
            border: none;
            border-radius: 6px;
            background: #2563eb;
            color: white;
            text-decoration: none;
            cursor: pointer;
        }

        button:hover, .link:hover { background: #1d4ed8; }

        .pesan {
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 18px;
        }

        .success { background: #dcfce7; color: #166534; }
        .error { background: #fee2e2; color: #991b1b; }

        .table-wrapper { overflow-x: auto; }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #e2e8f0;
        }

        th { background: #eff6ff; }

        .status {
            display: inline-block;
            padding: 5px 8px;
            background: #fef3c7;
            border-radius: 5px;
        }

        @media (max-width: 600px) {
            body { padding: 12px; }
        }
    </style>
</head>

<body>
<div class="container">

    <div class="panel">
        <h1>Data Peminjaman</h1>
        <p>Kelola pengajuan dan status peminjaman alat.</p>

        <?php if ($isAdmin): ?>
            <a href="dashboard.php" class="link">Kembali ke Dashboard</a>
        <?php else: ?>
            <a href="dashboard_petugas.php" class="link">Kembali ke Dashboard</a>
        <?php endif; ?>
    </div>

    <?php if ($pesan !== ""): ?>
        <div class="pesan <?= aman($jenisPesan) ?>">
            <?= aman($pesan) ?>
        </div>
    <?php endif; ?>

    <?php if ($isAdmin): ?>
    <div class="panel">
        <h2>Tambah Peminjaman</h2>

        <?php if ($dataPeminjam->num_rows > 0 && $dataAlatForm->num_rows > 0): ?>
            <form method="POST">
                <div class="form-grid">
                    <select name="id_user" required>
                        <option value="">Pilih peminjam</option>
                        <?php while ($u = $dataPeminjam->fetch_assoc()): ?>
                            <option value="<?= (int) $u["id_user"] ?>">
                                <?= aman($u["nama"]) ?>
                                (<?= aman($u["username"]) ?>)
                            </option>
                        <?php endwhile; ?>
                    </select>

                    <select name="id_alat" required>
                        <option value="">Pilih alat</option>
                        <?php while ($a = $dataAlatForm->fetch_assoc()): ?>
                            <option value="<?= (int) $a["id_alat"] ?>">
                                <?= aman($a["nama_alat"]) ?>
                                — stok <?= (int) $a["stok"] ?>
                            </option>
                        <?php endwhile; ?>
                    </select>

                    <input type="number" name="jumlah" min="1"
                           placeholder="Jumlah dipinjam" required>

                    <label>
                        Tanggal Pinjam
                        <input type="date" name="tanggal_pinjam" required>
                    </label>

                    <label>
                        Rencana Kembali
                        <input type="date" name="tanggal_kembali_rencana" required>
                    </label>
                </div>

                <br>
                <button type="submit" name="tambah">
                    Ajukan Peminjaman
                </button>
            </form>
        <?php else: ?>
            <p>Tambahkan akun peminjam dan data alat terlebih dahulu.</p>
            <a href="pengguna.php" class="link">Data Pengguna</a>
            <a href="alat.php" class="link">Data Alat</a>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <div class="panel">
        <h2>Daftar Peminjaman</h2>

        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>No.</th>
                        <th>Peminjam</th>
                        <th>Alat</th>
                        <th>Jumlah</th>
                        <th>Tanggal Pinjam</th>
                        <th>Rencana Kembali</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>

                <tbody>
                <?php if ($dataPinjaman->num_rows > 0): ?>
                    <?php $no = 1; ?>
                    <?php while ($p = $dataPinjaman->fetch_assoc()): ?>
                        <tr>
                            <td><?= $no++ ?></td>
                            <td><?= aman($p["nama"]) ?></td>
                            <td><?= aman($p["nama_alat"]) ?></td>
                            <td><?= (int) $p["jumlah"] ?></td>
                            <td><?= aman($p["tanggal_pinjam"]) ?></td>
                            <td><?= aman($p["tanggal_kembali_rencana"]) ?></td>
                            <td>
                                <span class="status">
                                    <?= aman(ucfirst($p["status"])) ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($p["status"] === "menunggu"): ?>
                                    <form method="POST"
                                          onsubmit="return confirm('Setujui peminjaman ini? Stok akan berkurang.');">
                                        <input type="hidden"
                                               name="id_peminjaman"
                                               value="<?= (int) $p["id_peminjaman"] ?>">
                                        <button type="submit" name="setujui">
                                            Setujui
                                        </button>
                                    </form>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="8">Belum ada data peminjaman.</td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>
</body>
</html>