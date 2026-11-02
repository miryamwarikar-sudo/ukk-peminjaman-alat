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

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $idAlat = (int) ($_POST["id_alat"] ?? 0);
    $jumlah = (int) ($_POST["jumlah"] ?? 0);
    $tanggalKembali = $_POST["tanggal_kembali"] ?? "";
    $tanggalPinjam = date("Y-m-d");

    if ($idAlat <= 0 || $jumlah <= 0 || $tanggalKembali === "") {
        $pesan = "Lengkapi semua data dengan benar.";
        $jenisPesan = "error";
    } elseif ($tanggalKembali < $tanggalPinjam) {
        $pesan = "Tanggal kembali tidak boleh sebelum hari ini.";
        $jenisPesan = "error";
    } else {
        // Panggil stored procedure sp_pinjam_alat
        $stmt = $koneksi->prepare(
            "CALL sp_pinjam_alat(?, ?, ?, ?, ?)"
        );

        if (!$stmt) {
            $pesan = "Gagal menyiapkan pengajuan peminjaman.";
            $jenisPesan = "error";
        } else {
            $idUser = (int) $_SESSION["id_user"];

            $stmt->bind_param(
                "iiiss",
                $idUser,
                $idAlat,
                $jumlah,
                $tanggalPinjam,
                $tanggalKembali
            );

            try {
                if ($stmt->execute()) {
                    $pesan = "Pengajuan berhasil dikirim dan menunggu persetujuan petugas.";
                } else {
                    throw new Exception("Pengajuan gagal disimpan.");
                }
            } catch (Throwable $e) {
                $pesan = "Pengajuan gagal: " . $e->getMessage();
                $jenisPesan = "error";
            }

            $stmt->close();

            // Bersihkan hasil CALL agar koneksi siap digunakan lagi
            while ($koneksi->more_results()) {
                $koneksi->next_result();
                $hasil = $koneksi->store_result();

                if ($hasil instanceof mysqli_result) {
                    $hasil->free();
                }
            }
        }
    }
}

$dataAlat = $koneksi->query(
    "SELECT id_alat, nama_alat, stok
     FROM alat
     WHERE stok > 0
     ORDER BY nama_alat ASC"
);

if (!$dataAlat) {
    die("Gagal mengambil data alat.");
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengajuan Peminjaman</title>

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
            max-width: 650px;
            padding: 24px;
            background: white;
            border-radius: 12px;
        }

        label {
            display: block;
            margin-top: 16px;
        }

        input, select, button {
            width: 100%;
            padding: 12px;
            margin-top: 7px;
            font: inherit;
        }

        input, select {
            border: 1px solid #cbd5e1;
            border-radius: 6px;
        }

        button {
            margin-top: 22px;
            border: 0;
            border-radius: 6px;
            color: white;
            background: #1d4ed8;
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
    <h1>Pengajuan Peminjaman Alat</h1>
    <p>Halo, <?= aman($_SESSION["nama"] ?? "Peminjam") ?>!</p>
</header>

<nav>
    <a href="dashboard_peminjam.php">Dashboard</a>
    <a href="alat.php">Daftar Alat</a>
    <a href="pengajuan.php">Ajukan Peminjaman</a>
    <a href="logout.php">Logout</a>
</nav>

<main>
    <section class="panel">
        <h2>Form Pengajuan</h2>

        <?php if ($pesan !== ""): ?>
            <div class="pesan <?= $jenisPesan === "error" ? "error" : "" ?>">
                <?= aman($pesan) ?>
            </div>
        <?php endif; ?>

        <?php if ($dataAlat->num_rows > 0): ?>
            <form method="POST">
                <label for="id_alat">Pilih alat</label>

                <select name="id_alat" id="id_alat" required>
                    <option value="">-- Pilih alat --</option>

                    <?php while ($alat = $dataAlat->fetch_assoc()): ?>
                        <option value="<?= (int) $alat["id_alat"] ?>">
                            <?= aman($alat["nama_alat"]) ?>
                            (Stok: <?= (int) $alat["stok"] ?>)
                        </option>
                    <?php endwhile; ?>
                </select>

                <label for="jumlah">Jumlah yang dipinjam</label>
                <input
                    type="number"
                    id="jumlah"
                    name="jumlah"
                    min="1"
                    required
                >

                <label for="tanggal_kembali">Rencana tanggal kembali</label>
                <input
                    type="date"
                    id="tanggal_kembali"
                    name="tanggal_kembali"
                    min="<?= date("Y-m-d") ?>"
                    required
                >

                <button type="submit">Ajukan Peminjaman</button>
            </form>
        <?php else: ?>
            <p>Belum ada alat dengan stok tersedia.</p>
        <?php endif; ?>
    </section>
</main>
</body>
</html>