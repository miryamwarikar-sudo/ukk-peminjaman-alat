<?php
session_start();

if (!isset($_SESSION["id_user"]) || $_SESSION["role"] !== "peminjam") {
    header("Location: index.php");
    exit;
}

require_once "koneksi.php";

$nama = htmlspecialchars($_SESSION["nama"] ?? "Peminjam");

$sql = "SELECT COUNT(*) AS total
        FROM peminjaman
        WHERE id_user = ? AND status = 'menunggu'";
$stmt = $koneksi->prepare($sql);
$stmt->bind_param("i", $_SESSION["id_user"]);
$stmt->execute();
$totalMenunggu = (int) $stmt->get_result()->fetch_assoc()["total"];
$stmt->close();

$sql = "SELECT COUNT(*) AS total
        FROM peminjaman
        WHERE id_user = ? AND status = 'disetujui'";
$stmt = $koneksi->prepare($sql);
$stmt->bind_param("i", $_SESSION["id_user"]);
$stmt->execute();
$totalDipinjam = (int) $stmt->get_result()->fetch_assoc()["total"];
$stmt->close();

$sql = "SELECT COUNT(*) AS total
        FROM peminjaman
        WHERE id_user = ? AND status = 'selesai'";
$stmt = $koneksi->prepare($sql);
$stmt->bind_param("i", $_SESSION["id_user"]);
$stmt->execute();
$totalSelesai = (int) $stmt->get_result()->fetch_assoc()["total"];
$stmt->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Peminjam</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #f1f5f9;
            color: #1e293b;
        }

        header {
            background: #1d4ed8;
            color: white;
            padding: 24px;
        }

        header h1 {
            margin: 0 0 8px;
        }

        nav {
            background: white;
            padding: 16px 24px;
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
        }

        nav a {
            color: #1d4ed8;
            text-decoration: none;
            font-weight: bold;
        }

        main {
            padding: 24px;
        }

        .kartu {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 18px;
        }

        .item {
            background: white;
            padding: 24px;
            border-radius: 12px;
            box-shadow: 0 3px 10px #00000008;
        }

        .item h3 {
            margin-top: 0;
            color: #64748b;
            font-size: 16px;
        }

        .angka {
            color: #1d4ed8;
            font-size: 32px;
            font-weight: bold;
        }

        .petunjuk {
            margin-top: 24px;
            padding: 22px;
            background: white;
            border-radius: 12px;
            line-height: 1.8;
        }

        .keluar {
            color: #dc2626;
        }
    </style>
</head>

<body>
    <header>
        <h1>Dashboard Peminjam</h1>
        <p>Selamat datang, <?= $nama ?>!</p>
    </header>

    <nav>
        
        <a href="alat.php">Daftar Alat</a>
        <a href="pengajuan.php">Ajukan Peminjaman</a>
        <a href="pengembalian_peminjam.php">Pengembalian Alat</a>
        <a href="logout.php" class="keluar">Logout</a>
    </nav>

    <main>
        <h2>Ringkasan Peminjaman Saya</h2>

        <div class="kartu">
            <div class="item">
                <h3>Menunggu Persetujuan</h3>
                <div class="angka"><?= $totalMenunggu ?></div>
            </div>

            <div class="item">
                <h3>Sedang Dipinjam</h3>
                <div class="angka"><?= $totalDipinjam ?></div>
            </div>

            <div class="item">
                <h3>Peminjaman Selesai</h3>
                <div class="angka"><?= $totalSelesai ?></div>
            </div>
        </div>

        <div class="petunjuk">
            <h3>Menu Peminjam</h3>
            <p>1. Melihat daftar alat yang tersedia.</p>
            <p>2. Mengajukan peminjaman alat.</p>
            <p>3. Melakukan pengajuan pengembalian alat.</p>
        </div>
    </main>
</body>
</html>