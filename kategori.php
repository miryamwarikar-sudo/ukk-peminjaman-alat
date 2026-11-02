<?php
session_start();

if (!isset($_SESSION["id_user"]) || ($_SESSION["role"] ?? "") !== "admin") {
    header("Location: index.php");
    exit;
}

require_once "koneksi.php";

$pesan = "";
$jenisPesan = "success";

// Fungsi mencatat aktivitas
function catatAktivitas($koneksi, $idUser, $aktivitas) {
    $stmt = $koneksi->prepare(
        "INSERT INTO log_aktivitas (id_user, aktivitas) VALUES (?, ?)"
    );

    if ($stmt) {
        $stmt->bind_param("is", $idUser, $aktivitas);
        $stmt->execute();
        $stmt->close();
    }
}

// Menambah kategori
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["tambah"])) {
    $namaKategori = trim($_POST["nama_kategori"] ?? "");

    if ($namaKategori === "") {
        $pesan = "Nama kategori wajib diisi.";
        $jenisPesan = "error";
    } else {
        $stmt = $koneksi->prepare(
            "INSERT INTO kategori (nama_kategori) VALUES (?)"
        );
        $stmt->bind_param("s", $namaKategori);

        if ($stmt->execute()) {
            catatAktivitas(
                $koneksi,
                (int) $_SESSION["id_user"],
                "Menambahkan kategori: " . $namaKategori
            );

            $pesan = "Kategori berhasil ditambahkan.";
        } else {
            $pesan = "Kategori gagal ditambahkan.";
            $jenisPesan = "error";
        }

        $stmt->close();
    }
}

// Menghapus kategori
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["hapus"])) {
    $idKategori = (int) ($_POST["id_kategori"] ?? 0);

    // Ambil nama kategori sebelum dihapus
    $stmt = $koneksi->prepare(
        "SELECT nama_kategori FROM kategori WHERE id_kategori = ?"
    );
    $stmt->bind_param("i", $idKategori);
    $stmt->execute();
    $hasilKategori = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($hasilKategori) {
        $namaKategori = $hasilKategori["nama_kategori"];

        $stmt = $koneksi->prepare(
            "DELETE FROM kategori WHERE id_kategori = ?"
        );
        $stmt->bind_param("i", $idKategori);

        if ($stmt->execute() && $stmt->affected_rows > 0) {
            catatAktivitas(
                $koneksi,
                (int) $_SESSION["id_user"],
                "Menghapus kategori: " . $namaKategori
            );

            $pesan = "Kategori berhasil dihapus.";
        } else {
            $pesan = "Kategori tidak bisa dihapus. Pastikan kategori belum digunakan oleh data alat.";
            $jenisPesan = "error";
        }

        $stmt->close();
    } else {
        $pesan = "Kategori tidak ditemukan.";
        $jenisPesan = "error";
    }
}

// Mengambil data kategori
$dataKategori = $koneksi->query(
    "SELECT id_kategori, nama_kategori
     FROM kategori
     ORDER BY id_kategori DESC"
);

function aman($teks) {
    return htmlspecialchars((string) $teks, ENT_QUOTES, "UTF-8");
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Kategori - Peminjaman Alat</title>

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

        .main {
            max-width: 1000px;
            margin: auto;
            padding: 30px 20px;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 12px;
            margin-bottom: 25px;
        }

        h1 {
            margin: 0 0 8px;
            font-size: 27px;
        }

        .muted {
            color: #64748b;
            font-size: 14px;
        }

        .back {
            color: #2563eb;
            text-decoration: none;
        }

        .panel {
            background: white;
            padding: 24px;
            margin-bottom: 22px;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
        }

        input {
            width: 100%;
            padding: 12px;
            border: 1px solid #cbd5e1;
            border-radius: 7px;
            margin-bottom: 14px;
        }

        button {
            padding: 11px 16px;
            border: none;
            border-radius: 7px;
            background: #2563eb;
            color: white;
            cursor: pointer;
        }

        .delete {
            background: #dc2626;
        }

        .message {
            padding: 12px;
            border-radius: 7px;
            margin-bottom: 20px;
        }

        .success {
            background: #dcfce7;
            color: #166534;
        }

        .error {
            background: #fee2e2;
            color: #991b1b;
        }

        .table-wrap {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            padding: 14px;
            border-bottom: 1px solid #e2e8f0;
            text-align: left;
        }

        th {
            background: #f8fafc;
        }

        .empty {
            color: #64748b;
            text-align: center;
        }
    </style>
</head>

<body>
<main class="main">

    <div class="header">
        <div>
            <h1>Data Kategori</h1>
            <div class="muted">Kelola kategori alat yang tersedia.</div>
        </div>

        <a class="back" href="dashboard.php">← Kembali ke Dashboard</a>
    </div>

    <?php if ($pesan !== ""): ?>
        <div class="message <?= aman($jenisPesan) ?>">
            <?= aman($pesan) ?>
        </div>
    <?php endif; ?>

    <section class="panel">
        <h2>Tambah Kategori</h2>

        <form method="POST">
            <label for="nama_kategori">Nama Kategori</label>

            <input
                type="text"
                id="nama_kategori"
                name="nama_kategori"
                placeholder="Contoh: Elektronik"
                maxlength="100"
                required
            >

            <button type="submit" name="tambah">
                + Tambah Kategori
            </button>
        </form>
    </section>

    <section class="panel">
        <h2>Daftar Kategori</h2>

        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>No.</th>
                        <th>Nama Kategori</th>
                        <th>Aksi</th>
                    </tr>
                </thead>

                <tbody>
                <?php if ($dataKategori && $dataKategori->num_rows > 0): ?>
                    <?php $no = 1; ?>
                    <?php while ($kategori = $dataKategori->fetch_assoc()): ?>
                        <tr>
                            <td><?= $no++ ?></td>
                            <td><?= aman($kategori["nama_kategori"]) ?></td>
                            <td>
                                <form method="POST"
                                      onsubmit="return confirm('Yakin ingin menghapus kategori ini?')">
                                    <input
                                        type="hidden"
                                        name="id_kategori"
                                        value="<?= (int) $kategori["id_kategori"] ?>"
                                    >

                                    <button
                                        class="delete"
                                        type="submit"
                                        name="hapus"
                                    >
                                        Hapus
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="3" class="empty">
                            Belum ada kategori. Silakan tambahkan kategori baru.
                        </td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>

</main>
</body>
</html>