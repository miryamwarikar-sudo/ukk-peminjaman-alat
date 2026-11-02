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

// Filter status laporan
$status = $_GET["status"] ?? "semua";
$statusValid = ["semua", "menunggu", "disetujui", "selesai"];

if (!in_array($status, $statusValid, true)) {
    $status = "semua";
}

if ($status === "semua") {
    $sql = "SELECT p.id_peminjaman, u.nama, a.nama_alat,
                   p.jumlah, p.tanggal_pinjam,
                   p.tanggal_kembali_rencana,
                   p.tanggal_kembali_real, p.status, p.denda
            FROM peminjaman p
            JOIN users u ON p.id_user = u.id_user
            JOIN alat a ON p.id_alat = a.id_alat
            ORDER BY p.id_peminjaman DESC";

    $data = $koneksi->query($sql);
} else {
    $stmt = $koneksi->prepare(
        "SELECT p.id_peminjaman, u.nama, a.nama_alat,
                p.jumlah, p.tanggal_pinjam,
                p.tanggal_kembali_rencana,
                p.tanggal_kembali_real, p.status, p.denda
         FROM peminjaman p
         JOIN users u ON p.id_user = u.id_user
         JOIN alat a ON p.id_alat = a.id_alat
         WHERE p.status = ?
         ORDER BY p.id_peminjaman DESC"
    );

    $stmt->bind_param("s", $status);
    $stmt->execute();
    $data = $stmt->get_result();
}

$totalTransaksi = 0;
$totalDenda = 0;
$barisLaporan = [];

if ($data) {
    while ($row = $data->fetch_assoc()) {
        $barisLaporan[] = $row;
        $totalTransaksi++;
        $totalDenda += (int) $row["denda"];
    }
}

function aman($teks) {
    return htmlspecialchars((string) $teks, ENT_QUOTES, "UTF-8");
}

function rupiah($angka) {
    return "Rp" . number_format((int) $angka, 0, ",", ".");
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Peminjaman</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            background: #f1f5f9;
            color: #1e293b;
            margin: 0;
            padding: 25px;
        }

        .container {
            max-width: 1200px;
            margin: auto;
        }

        .panel {
            background: white;
            padding: 22px;
            border-radius: 12px;
            margin-bottom: 20px;
            box-shadow: 0 3px 12px rgba(0,0,0,0.05);
        }

        h1, h2 {
            margin-top: 0;
        }

        .link, button {
            display: inline-block;
            padding: 10px 15px;
            border: none;
            border-radius: 6px;
            background: #2563eb;
            color: white;
            text-decoration: none;
            cursor: pointer;
        }

        select {
            padding: 10px;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            margin-right: 8px;
        }

        .ringkasan {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 15px;
        }

        .angka {
            font-size: 24px;
            font-weight: bold;
            margin-top: 8px;
        }

        .table-wrapper {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            padding: 11px;
            text-align: left;
            border-bottom: 1px solid #e2e8f0;
            white-space: nowrap;
        }

        th {
            background: #eff6ff;
        }

        .status {
            padding: 5px 8px;
            background: #fef3c7;
            border-radius: 5px;
        }

        @media print {
            body {
                background: white;
                padding: 0;
            }

            .panel {
                box-shadow: none;
                border: 1px solid #ddd;
            }

            .no-print {
                display: none !important;
            }

            .table-wrapper {
                overflow: visible;
            }

            th, td {
                white-space: normal;
                font-size: 10px;
                padding: 6px;
            }
        }
    </style>
</head>

<body>
<div class="container">

    <div class="panel no-print">
        <h1>Laporan Peminjaman Alat</h1>
        <p>Laporan transaksi peminjaman dan pengembalian alat.</p>
        <a
           href="<?= ($_SESSION["role"] === "petugas")
               ? "dashboard_petugas.php"
               : "dashboard.php" ?>"
            class="link"
        >
          Kembali ke Dashboard
           </a>
           
        <button onclick="window.print()">Cetak Laporan</button>
    </div>

    <div class="panel no-print">
        <h2>Filter Laporan</h2>

        <form method="GET">
            <select name="status">
                <option value="semua" <?= $status === "semua" ? "selected" : "" ?>>
                    Semua Status
                </option>
                <option value="menunggu" <?= $status === "menunggu" ? "selected" : "" ?>>
                    Menunggu
                </option>
                <option value="disetujui" <?= $status === "disetujui" ? "selected" : "" ?>>
                    Disetujui
                </option>
                <option value="selesai" <?= $status === "selesai" ? "selected" : "" ?>>
                    Selesai
                </option>
            </select>

            <button type="submit">Tampilkan</button>
        </form>
    </div>

    <div class="panel">
        <h2>Ringkasan Laporan</h2>

        <div class="ringkasan">
            <div>
                <p>Total Transaksi</p>
                <div class="angka"><?= $totalTransaksi ?></div>
            </div>

            <div>
                <p>Total Denda Tercatat</p>
                <div class="angka"><?= rupiah($totalDenda) ?></div>
            </div>
        </div>

        <p>Filter status: <strong><?= ucfirst(aman($status)) ?></strong></p>
        <p>Tanggal cetak: <?= date("d-m-Y") ?></p>
    </div>

    <div class="panel">
        <h2>Daftar Transaksi</h2>

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
                        <th>Tanggal Kembali</th>
                        <th>Status</th>
                        <th>Denda</th>
                    </tr>
                </thead>

                <tbody>
                <?php if (count($barisLaporan) > 0): ?>
                    <?php $no = 1; ?>

                    <?php foreach ($barisLaporan as $row): ?>
                        <tr>
                            <td><?= $no++ ?></td>
                            <td><?= aman($row["nama"]) ?></td>
                            <td><?= aman($row["nama_alat"]) ?></td>
                            <td><?= (int) $row["jumlah"] ?></td>
                            <td><?= aman($row["tanggal_pinjam"]) ?></td>
                            <td><?= aman($row["tanggal_kembali_rencana"]) ?></td>
                            <td>
                                <?= $row["tanggal_kembali_real"]
                                    ? aman($row["tanggal_kembali_real"])
                                    : "-" ?>
                            </td>
                            <td>
                                <span class="status">
                                    <?= ucfirst(aman($row["status"])) ?>
                                </span>
                            </td>
                            <td><?= rupiah($row["denda"]) ?></td>
                        </tr>
                    <?php endforeach; ?>

                <?php else: ?>
                    <tr>
                        <td colspan="9">Belum ada transaksi untuk ditampilkan.</td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>
</body>
</html>