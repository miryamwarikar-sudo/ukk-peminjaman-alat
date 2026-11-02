<?php
session_start();

if (
    !isset($_SESSION["id_user"]) ||
    $_SESSION["role"] !== "peminjam"
) {
    header("Location: index.php");
    exit;
}

require_once "koneksi.php";

$pesan = "";
$jenisPesan = "success";

function aman($teks) {
    return htmlspecialchars((string) $teks, ENT_QUOTES, "UTF-8");
}

// Memproses pengembalian
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $idPeminjaman = (int) ($_POST["id_peminjaman"] ?? 0);
    $idUser = (int) $_SESSION["id_user"];

    try {
        $koneksi->begin_transaction();

        // Pastikan pinjaman milik pengguna dan masih aktif
        $stmt = $koneksi->prepare(
            "SELECT id_peminjaman, tanggal_kembali_rencana, status
             FROM peminjaman
             WHERE id_peminjaman = ? AND id_user = ?
             FOR UPDATE"
        );

        $stmt->bind_param("ii", $idPeminjaman, $idUser);
        $stmt->execute();
        $pinjaman = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$pinjaman || $pinjaman["status"] !== "disetujui") {
            throw new Exception(
                "Peminjaman tidak ditemukan atau sudah dikembalikan."
            );
        }

        $tanggalKembali = date("Y-m-d");

        // Hitung denda melalui function database
        $stmt = $koneksi->prepare(
            "SELECT hitung_denda(?, ?) AS denda"
        );

        $stmt->bind_param(
            "ss",
            $pinjaman["tanggal_kembali_rencana"],
            $tanggalKembali
        );

        $stmt->execute();
        $hasilDenda = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        $denda = (int) $hasilDenda["denda"];

        // Status selesai akan mengaktifkan trigger penambahan stok
        $stmt = $koneksi->prepare(
            "UPDATE peminjaman
             SET status = 'selesai',
                 tanggal_kembali_real = ?,
                 denda = ?
             WHERE id_peminjaman = ?
               AND id_user = ?
               AND status = 'disetujui'"
        );

        $stmt->bind_param(
            "siii",
            $tanggalKembali,
            $denda,
            $idPeminjaman,
            $idUser
        );

        $stmt->execute();
        $berhasil = $stmt->affected_rows;
        $stmt->close();

        if ($berhasil !== 1) {
            throw new Exception("Pengembalian gagal diproses.");
        }

        // Catat aktivitas
        $stmt = $koneksi->prepare(
            "INSERT INTO log_aktivitas (id_user, aktivitas)
             VALUES (?, ?)"
        );

        $aktivitas = "Mengembalikan peminjaman nomor " . $idPeminjaman;
        $stmt->bind_param("is", $idUser, $aktivitas);
        $stmt->execute();
        $stmt->close();

        $koneksi->commit();

        $pesan = "Pengembalian berhasil dicatat. Denda: Rp"
            . number_format($denda, 0, ",", ".");
    } catch (Throwable $e) {
        $koneksi->rollback();
        $pesan = $e->getMessage();
        $jenisPesan = "error";
    }
}

// Daftar pinjaman aktif milik pengguna
$stmt = $koneksi->prepare(
    "SELECT p.id_peminjaman, a.nama_alat, p.jumlah,
            p.tanggal_pinjam, p.tanggal_kembali_rencana
     FROM peminjaman p
     JOIN alat a ON p.id_alat = a.id_alat
     WHERE p.id_user = ? AND p.status = 'disetujui'
     ORDER BY p.tanggal_kembali_rencana ASC"
);

$stmt->bind_param("i", $_SESSION["id_user"]);
$stmt->execute();
$dataPinjaman = $stmt->get_result();
$stmt->close();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengembalian Alat</title>

    <style>
        * { box-sizing: border-box; }

        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #f1f5f9;
            color: #1e293b;
        }

        header {
            padding: 22px;
            background: #1d4ed8;
            color: white;
        }

        header h1 { margin: 0 0 8px; }

        nav {
            display: flex;
            flex-wrap: wrap;
            gap: 18px;
            padding: 15px 22px;
            background: white;
        }

        nav a {
            color: #1d4ed8;
            text-decoration: none;
            font-weight: bold;
        }

        main { padding: 24px; }

        .panel {
            background: white;
            border-radius: 12px;
            padding: 22px;
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 650px;
        }

        th, td {
            padding: 12px;
            border-bottom: 1px solid #e2e8f0;
            text-align: left;
        }

        th { background: #eff6ff; }

        button {
            padding: 9px 12px;
            background: #1d4ed8;
            color: white;
            border: 0;
            border-radius: 6px;
            cursor: pointer;
        }

        .pesan {
            padding: 12px;
            margin-bottom: 16px;
            border-radius: 6px;
            background: #dcfce7;
            overflow-wrap: anywhere;
        }

        .error { background: #fee2e2; }
    </style>
</head>

<body>
<header>
    <h1>Pengembalian Alat</h1>
    <p>Halo, <?= aman($_SESSION["nama"] ?? "Peminjam") ?>!</p>
</header>

<nav>
    <a href="dashboard_peminjam.php">Dashboard</a>
    <a href="alat.php">Daftar Alat</a>
    <a href="pengajuan.php">Ajukan Peminjaman</a>
    <a href="pengembalian_peminjam.php">Pengembalian</a>
    <a href="logout.php">Logout</a>
</nav>

<main>
    <section class="panel">
        <h2>Alat yang Masih Dipinjam</h2>

        <?php if ($pesan !== ""): ?>
            <div class="pesan <?= $jenisPesan === "error" ? "error" : "" ?>">
                <?= aman($pesan) ?>
            </div>
        <?php endif; ?>

        <?php if ($dataPinjaman->num_rows > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>No.</th>
                        <th>Nama Alat</th>
                        <th>Jumlah</th>
                        <th>Tanggal Pinjam</th>
                        <th>Rencana Kembali</th>
                        <th>Aksi</th>
                    </tr>
                </thead>

                <tbody>
                <?php $no = 1; ?>

                <?php while ($p = $dataPinjaman->fetch_assoc()): ?>
                    <tr>
                        <td><?= $no++ ?></td>
                        <td><?= aman($p["nama_alat"]) ?></td>
                        <td><?= (int) $p["jumlah"] ?></td>
                        <td><?= aman($p["tanggal_pinjam"]) ?></td>
                        <td><?= aman($p["tanggal_kembali_rencana"]) ?></td>
                        <td>
                            <form method="POST"
                                  onsubmit="return confirm('Catat pengembalian alat ini?')">
                                <input
                                    type="hidden"
                                    name="id_peminjaman"
                                    value="<?= (int) $p["id_peminjaman"] ?>"
                                >
                                <button type="submit">Kembalikan</button>
                            </form>
                        </td>
                    </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>Tidak ada alat yang sedang dipinjam.</p>
        <?php endif; ?>
    </section>
</main>
</body>
</html>