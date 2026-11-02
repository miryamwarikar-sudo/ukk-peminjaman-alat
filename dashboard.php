<?php
session_start();

if (!isset($_SESSION["id_user"]) || $_SESSION["role"] !== "admin") {
    header("Location: index.php");
    exit;
}

require_once "koneksi.php";

$nama = htmlspecialchars($_SESSION["nama"] ?? "Admin");

function hitungData($koneksi, $sql) {
    $hasil = $koneksi->query($sql);

    if (!$hasil) {
        return 0;
    }

    return (int) $hasil->fetch_assoc()["total"];
}

$totalPengguna = hitungData(
    $koneksi,
    "SELECT COUNT(*) AS total FROM users"
);

$totalKategori = hitungData(
    $koneksi,
    "SELECT COUNT(*) AS total FROM kategori"
);

$totalAlat = hitungData(
    $koneksi,
    "SELECT COUNT(*) AS total FROM alat"
);

$totalMenunggu = hitungData(
    $koneksi,
    "SELECT COUNT(*) AS total FROM peminjaman WHERE status = 'menunggu'"
);

$totalDisetujui = hitungData(
    $koneksi,
    "SELECT COUNT(*) AS total FROM peminjaman WHERE status = 'disetujui'"
);

$totalSelesai = hitungData(
    $koneksi,
    "SELECT COUNT(*) AS total FROM peminjaman WHERE status = 'selesai'"
);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - Peminjaman Alat</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #f4f6fb;
            color: #1e293b;
        }

        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            width: 230px;
            height: 100vh;
            padding: 25px 15px;
            background: #1e293b;
            color: white;
        }

        .brand {
            font-size: 19px;
            font-weight: bold;
            margin: 0 10px 35px;
        }

        .sidebar a {
            display: block;
            color: #cbd5e1;
            text-decoration: none;
            padding: 13px 12px;
            margin-bottom: 6px;
            border-radius: 7px;
        }

        .sidebar a.active,
        .sidebar a:hover {
            background: #334155;
            color: white;
        }

        .main {
            margin-left: 230px;
            padding: 30px;
        }

        .topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
            flex-wrap: wrap;
            margin-bottom: 30px;
        }

        .topbar h1 {
            margin: 0 0 8px;
            font-size: 26px;
        }

        .muted {
            color: #64748b;
            font-size: 14px;
        }

        .logout {
            color: #dc2626;
            text-decoration: none;
            background: white;
            border: 1px solid #e2e8f0;
            padding: 10px 15px;
            border-radius: 8px;
        }

        .welcome {
            background: #2563eb;
            color: white;
            padding: 25px;
            border-radius: 12px;
            margin-bottom: 22px;
        }

        .welcome h2 {
            margin-top: 0;
        }

        .welcome p {
            margin-bottom: 0;
        }

        .cards {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 18px;
        }

        .card {
            background: white;
            padding: 22px;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
        }

        .card p {
            color: #64748b;
            margin: 0 0 15px;
        }

        .number {
            font-size: 30px;
            font-weight: bold;
            color: #2563eb;
        }

        @media (max-width: 750px) {
            .sidebar {
                position: static;
                width: 100%;
                height: auto;
            }

            .main {
                margin-left: 0;
                padding: 20px;
            }

            .cards {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>

<aside class="sidebar">
    <div class="brand">Peminjaman Alat</div>

    <a href="dashboard.php" class="active">Dashboard</a>
    <a href="pengguna.php">Data Pengguna</a>
    <a href="kategori.php">Data Kategori</a>
    <a href="alat.php">Data Alat</a>
    <a href="peminjaman.php">Data Peminjaman</a>
    <a href="pengembalian.php">Pengembalian</a>
    <a href="laporan.php">Laporan</a>
    <a href="log_aktivitas.php">Log Aktivitas</a>

</aside>

<main class="main">

    <div class="topbar">
        <div>
            <h1>Dashboard</h1>
            <div class="muted">
                Sistem Informasi Peminjaman Alat
            </div>
        </div>

        <a class="logout" href="logout.php">Keluar</a>
    </div>

    <section class="welcome">
        <h2>Selamat datang, <?= $nama ?>!</h2>
        <p>Selamat bekerja di halaman admin aplikasi peminjaman alat.</p>
    </section>

    <section class="cards">

        <div class="card">
            <p>Total Pengguna</p>
            <div class="number"><?= $totalPengguna ?></div>
        </div>

        <div class="card">
            <p>Total Kategori</p>
            <div class="number"><?= $totalKategori ?></div>
        </div>

        <div class="card">
            <p>Total Alat</p>
            <div class="number"><?= $totalAlat ?></div>
        </div>

        <div class="card">
            <p>Peminjaman Menunggu</p>
            <div class="number"><?= $totalMenunggu ?></div>
        </div>

        <div class="card">
            <p>Peminjaman Disetujui</p>
            <div class="number"><?= $totalDisetujui ?></div>
        </div>

        <div class="card">
            <p>Peminjaman Selesai</p>
            <div class="number"><?= $totalSelesai ?></div>
        </div>

    </section>

</main>

</body>
</html>