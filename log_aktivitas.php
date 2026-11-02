<?php
session_start();

if (
    !isset($_SESSION["id_user"]) ||
    ($_SESSION["role"] ?? "") !== "admin"
) {
    header("Location: index.php");
    exit;
}

require_once "koneksi.php";

function aman($teks) {
    return htmlspecialchars((string) $teks, ENT_QUOTES, "UTF-8");
}

$dataLog = $koneksi->query(
    "SELECT l.id_log, l.aktivitas, l.waktu, u.nama, u.username, u.role
     FROM log_aktivitas l
     JOIN users u ON l.id_user = u.id_user
     ORDER BY l.waktu DESC, l.id_log DESC"
);

if (!$dataLog) {
    die("Gagal mengambil data log aktivitas.");
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Log Aktivitas</title>

    <style>
        * { box-sizing: border-box; }

        body {
            margin: 0;
            padding: 25px;
            font-family: Arial, sans-serif;
            background: #f1f5f9;
            color: #1e293b;
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

        .link {
            display: inline-block;
            padding: 10px 15px;
            border-radius: 6px;
            background: #2563eb;
            color: white;
            text-decoration: none;
        }

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

        .kosong {
            text-align: center;
            color: #64748b;
        }

        @media (max-width: 600px) {
            body { padding: 12px; }
            .panel { padding: 15px; }
        }
    </style>
</head>

<body>
<div class="container">

    <div class="panel">
        <h1>Log Aktivitas</h1>
        <p>Riwayat aktivitas yang tercatat di dalam aplikasi.</p>
        <a href="dashboard.php" class="link">Kembali ke Dashboard</a>
    </div>

    <div class="panel">
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>No.</th>
                        <th>Nama Pengguna</th>
                        <th>Username</th>
                        <th>Role</th>
                        <th>Aktivitas</th>
                        <th>Waktu</th>
                    </tr>
                </thead>

                <tbody>
                <?php if ($dataLog->num_rows > 0): ?>
                    <?php $no = 1; ?>
                    <?php while ($log = $dataLog->fetch_assoc()): ?>
                        <tr>
                            <td><?= $no++ ?></td>
                            <td><?= aman($log["nama"]) ?></td>
                            <td><?= aman($log["username"]) ?></td>
                            <td><?= aman(ucfirst($log["role"])) ?></td>
                            <td><?= aman($log["aktivitas"]) ?></td>
                            <td><?= aman($log["waktu"]) ?></td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" class="kosong">
                            Belum ada aktivitas yang tercatat.
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