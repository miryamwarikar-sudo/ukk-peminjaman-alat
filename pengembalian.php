<?php
session_start();

if (
    !isset($_SESSION["id_user"]) ||
    !in_array($_SESSION["role"] ?? "", ["admin", "petugas"], true)
) {
    header("Location: index.php");
    exit;
}

require_once "koneksi.php";

$isAdmin = $_SESSION["role"] === "admin";
$dashboard = $isAdmin ? "dashboard.php" : "dashboard_petugas.php";

function aman($teks) {
    return htmlspecialchars((string) $teks, ENT_QUOTES, "UTF-8");
}

function rupiah($angka) {
    return "Rp" . number_format((int) $angka, 0, ",", ".");
}

// Daftar peminjaman yang masih berlangsung
$dataPinjaman = $koneksi->query(
    "SELECT p.id_peminjaman, p.jumlah,
            p.tanggal_pinjam, p.tanggal_kembali_rencana,
            u.nama, a.nama_alat
     FROM peminjaman p
     JOIN users u ON p.id_user = u.id_user
     JOIN alat a ON p.id_alat = a.id_alat
     WHERE p.status = 'disetujui'
     ORDER BY p.tanggal_kembali_rencana ASC"
);

if (!$dataPinjaman) {
    die("Gagal mengambil data peminjaman.");
}

// Riwayat pengembalian yang sudah selesai
$riwayat = $koneksi->query(
    "SELECT p.id_peminjaman, p.jumlah,
            p.tanggal_kembali_rencana,
            p.tanggal_kembali_real, p.denda,
            u.nama, a.nama_alat
     FROM peminjaman p
     JOIN users u ON p.id_user = u.id_user
     JOIN alat a ON p.id_alat = a.id_alat
     WHERE p.status = 'selesai'
     ORDER BY p.tanggal_kembali_real DESC,
              p.id_peminjaman DESC"
);

if (!$riwayat) {
    die("Gagal mengambil riwayat pengembalian.");
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Pemantauan Pengembalian Alat</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 25px;
            font-family: Arial, sans-serif;
            background: #f1f5f9;
            color: #1e293b;
        }

        .container {
            max-width: 1150px;
            margin: auto;
        }

        .panel {
            background: white;
            padding: 22px;
            border-radius: 12px;
            margin-bottom: 20px;
            box-shadow: 0 3px 12px rgba(0, 0, 0, 0.05);
        }

        h1, h2 {
            margin-top: 0;
        }

        .link {
            display: inline-block;
            padding: 10px 15px;
            border-radius: 6px;
            background: #2563eb;
            color: white;
            text-decoration: none;
        }

        .link:hover {
            background: #1d4ed8;
        }

        .table-wrapper {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #e2e8f0;
            white-space: nowrap;
        }

        th {
            background: #eff6ff;
        }

        .status {
            display: inline-block;
            padding: 5px 9px;
            border-radius: 5px;
            background: #dbeafe;
            color: #1d4ed8;
        }

        .selesai {
            background: #dcfce7;
            color: #166534;
        }

        .kosong {
            text-align: center;
            color: #64748b;
        }

        @media (max-width: 600px) {
            body {
                padding: 12px;
            }

            .panel {
                padding: 15px;
            }
        }
    </style>
</head>

<body>
<div class="container">

    <div class="panel">
        <h1>Pemantauan Pengembalian Alat</h1>

        <p>
            Pantau alat yang masih dipinjam dan riwayat
            pengembalian alat.
        </p>

        <a href="<?= aman($dashboard) ?>" class="link">
            Kembali ke Dashboard
        </a>
    </div>

    <div class="panel">
        <h2>Peminjaman Belum Dikembalikan</h2>

        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>No.</th>
                        <th>Peminjam</th>
                        <th>Nama Alat</th>
                        <th>Jumlah</th>
                        <th>Tanggal Pinjam</th>
                        <th>Rencana Kembali</th>
                        <th>Status</th>
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
                                <span class="status">Sedang Dipinjam</span>
                            </td>
                        </tr>
                    <?php endwhile; ?>

                <?php else: ?>
                    <tr>
                        <td colspan="7" class="kosong">
                            Tidak ada alat yang sedang dipinjam.
                        </td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="panel">
        <h2>Riwayat Pengembalian</h2>

        <p>Tarif denda keterlambatan: Rp5.000 per hari.</p>

        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>No.</th>
                        <th>Peminjam</th>
                        <th>Nama Alat</th>
                        <th>Jumlah</th>
                        <th>Rencana Kembali</th>
                        <th>Tanggal Kembali</th>
                        <th>Denda</th>
                        <th>Status</th>
                    </tr>
                </thead>

                <tbody>
                <?php if ($riwayat->num_rows > 0): ?>
                    <?php $no = 1; ?>

                    <?php while ($r = $riwayat->fetch_assoc()): ?>
                        <tr>
                            <td><?= $no++ ?></td>
                            <td><?= aman($r["nama"]) ?></td>
                            <td><?= aman($r["nama_alat"]) ?></td>
                            <td><?= (int) $r["jumlah"] ?></td>
                            <td><?= aman($r["tanggal_kembali_rencana"]) ?></td>
                            <td><?= aman($r["tanggal_kembali_real"]) ?></td>
                            <td><?= rupiah($r["denda"]) ?></td>
                            <td>
                                <span class="status selesai">Selesai</span>
                            </td>
                        </tr>
                    <?php endwhile; ?>

                <?php else: ?>
                    <tr>
                        <td colspan="8" class="kosong">
                            Belum ada riwayat pengembalian.
                        </td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>
</body>
</html>