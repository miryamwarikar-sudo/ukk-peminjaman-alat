<?php
session_start();

if (
    !isset($_SESSION["id_user"]) ||
    ($_SESSION["role"] ?? "") !== "petugas"
) {
    header("Location: index.php");
    exit;
}

require_once "koneksi.php";

function hitungData($koneksi, $sql) {
    $hasil = $koneksi->query($sql);
    if (!$hasil) {
        return 0;
    }

    return (int) $hasil->fetch_assoc()["total"];
}

$menunggu = hitungData(
    $koneksi,
    "SELECT COUNT(*) AS total FROM peminjaman WHERE status = 'menunggu'"
);

$disetujui = hitungData(
    $koneksi,
    "SELECT COUNT(*) AS total FROM peminjaman WHERE status = 'disetujui'"
);

$selesai = hitungData(
    $koneksi,
    "SELECT COUNT(*) AS total FROM peminjaman WHERE status = 'selesai'"
);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Petugas</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #f3f4f6;
            color: #222;
        }

        header {
            background: #2563eb;
            color: white;
            padding: 22px;
        }

        header h1 {
            margin: 0 0 8px;
        }

        nav {
            background: white;
            padding: 15px 22px;
            display: flex;
            flex-wrap: wrap;
            gap: 18px;
        }

        nav a {
            color: #2563eb;
            text-decoration: none;
            font-weight: bold;
        }

        nav a:hover {
            text-decoration: underline;
        }

        main {
            padding: 25px;
        }

        .kartu {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 18px;
            margin: 20px 0;
        }

        .item {
            background: white;
            padding: 22px;
            border-radius: 10px;
            box-shadow: 0 2px 7px #00000010;
        }

        .item h3 {
            margin-top: 0;
            font-size: 16px;
        }

        .angka {
            font-size: 32px;
            font-weight: bold;
            color: #2563eb;
        }

        .info {
            background: white;
            padding: 20px;
            border-radius: 10px;
        }

        @media (max-width: 500px) {
            main {
                padding: 15px;
            }
        }
    </style>
</head>

<body>
    <header>
        <h1>Dashboard Petugas</h1>
        <p>Selamat datang, <?= htmlspecialchars($_SESSION["nama"]) ?>!</p>
    </header>

    <nav>
        <a href="peminjaman.php">Persetujuan Peminjaman</a>
        <a href="pengembalian.php">Pemantauan Pengembalian</a>
        <a href="laporan.php">Cetak Laporan</a>
        <a href="logout.php">Logout</a>
    </nav>

    <main>
        <h2>Ringkasan Peminjaman</h2>

        <div class="kartu">
            <div class="item">
                <h3>Menunggu Persetujuan</h3>
                <div class="angka"><?= $menunggu ?></div>
            </div>

            <div class="item">
                <h3>Sedang Dipinjam</h3>
                <div class="angka"><?= $disetujui ?></div>
            </div>

            <div class="item">
                <h3>Peminjaman Selesai</h3>
                <div class="angka"><?= $selesai ?></div>
            </div>
        </div>

        <div class="info">
            <h3>Tugas Petugas</h3>
            <p>Memeriksa dan menyetujui pengajuan peminjaman alat.</p>
            <p>Memantau alat yang belum dikembalikan.</p>
            <p>Melihat dan mencetak laporan peminjaman.</p>
        </div>
    </main>
</body>
</html>