<?php
session_start();

if (
    !isset($_SESSION["id_user"]) ||
    !in_array($_SESSION["role"] ?? "", ["admin", "peminjam"], true)
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

// MENCATAT AKTIVITAS
function catatAktivitas($koneksi, $idUser, $aktivitas) {
    $stmt = $koneksi->prepare(
        "INSERT INTO log_aktivitas (id_user, aktivitas)
         VALUES (?, ?)"
    );

    if ($stmt) {
        $stmt->bind_param("is", $idUser, $aktivitas);
        $stmt->execute();
        $stmt->close();
    }
}

// TAMBAH ALAT BARU
if (
    $isAdmin &&
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    isset($_POST["tambah"])
) {
    $namaAlat = trim($_POST["nama_alat"] ?? "");
    $idKategori = (int) ($_POST["id_kategori"] ?? 0);
    $stok = filter_var($_POST["stok"] ?? null, FILTER_VALIDATE_INT);

    if (
        $namaAlat === "" ||
        $idKategori <= 0 ||
        $stok === false ||
        $stok === null ||
        $stok < 0
    ) {
        $pesan = "Lengkapi data alat dengan benar.";
        $jenisPesan = "error";
    } else {
        $cek = $koneksi->prepare(
            "SELECT id_kategori FROM kategori WHERE id_kategori = ?"
        );
        $cek->bind_param("i", $idKategori);
        $cek->execute();
        $kategoriAda = $cek->get_result()->num_rows > 0;
        $cek->close();

        if (!$kategoriAda) {
            $pesan = "Kategori tidak ditemukan.";
            $jenisPesan = "error";
        } else {
            $stmt = $koneksi->prepare(
                "INSERT INTO alat (id_kategori, nama_alat, stok)
                 VALUES (?, ?, ?)"
            );
            $stmt->bind_param("isi", $idKategori, $namaAlat, $stok);

            if ($stmt->execute()) {
                catatAktivitas(
                    $koneksi,
                    (int) $_SESSION["id_user"],
                    "Menambahkan alat baru: " . $namaAlat .
                    " dengan stok " . $stok
                );

                $pesan = "Alat baru berhasil ditambahkan.";
            } else {
                $pesan = "Alat gagal ditambahkan.";
                $jenisPesan = "error";
            }

            $stmt->close();
        }
    }
}

// TAMBAH STOK ALAT
if (
    $isAdmin &&
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    isset($_POST["tambah_stok"])
) {
    $idAlat = (int) ($_POST["id_alat"] ?? 0);
    $jumlahStok = filter_var(
        $_POST["jumlah_stok"] ?? null,
        FILTER_VALIDATE_INT
    );

    if (
        $idAlat <= 0 ||
        $jumlahStok === false ||
        $jumlahStok === null ||
        $jumlahStok <= 0
    ) {
        $pesan = "Pilih alat dan masukkan tambahan stok lebih dari 0.";
        $jenisPesan = "error";
    } else {
        $stmt = $koneksi->prepare(
            "UPDATE alat SET stok = stok + ? WHERE id_alat = ?"
        );
        $stmt->bind_param("ii", $jumlahStok, $idAlat);

        if ($stmt->execute() && $stmt->affected_rows > 0) {
            $cekAlat = $koneksi->prepare(
                "SELECT nama_alat FROM alat WHERE id_alat = ?"
            );
            $cekAlat->bind_param("i", $idAlat);
            $cekAlat->execute();
            $hasilAlat = $cekAlat->get_result()->fetch_assoc();
            $cekAlat->close();

            $namaAlatLog = $hasilAlat["nama_alat"] ?? "Alat";

            catatAktivitas(
                $koneksi,
                (int) $_SESSION["id_user"],
                "Menambahkan stok " . $namaAlatLog .
                " sebanyak " . $jumlahStok
            );

            $pesan = "Stok alat berhasil ditambahkan.";
        } else {
            $pesan = "Stok gagal ditambahkan. Pastikan alat tersedia.";
            $jenisPesan = "error";
        }

        $stmt->close();
    }
}

// HAPUS ALAT
if (
    $isAdmin &&
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    isset($_POST["hapus"])
) {
    $idAlat = (int) ($_POST["id_alat"] ?? 0);

    // Simpan nama alat sebelum dihapus
    $cekAlat = $koneksi->prepare(
        "SELECT nama_alat FROM alat WHERE id_alat = ?"
    );
    $cekAlat->bind_param("i", $idAlat);
    $cekAlat->execute();
    $hasilAlat = $cekAlat->get_result()->fetch_assoc();
    $cekAlat->close();

    $stmt = $koneksi->prepare(
        "DELETE FROM alat WHERE id_alat = ?"
    );
    $stmt->bind_param("i", $idAlat);

    if ($stmt->execute() && $stmt->affected_rows > 0) {
        $namaAlatLog = $hasilAlat["nama_alat"] ?? "Alat";

        catatAktivitas(
            $koneksi,
            (int) $_SESSION["id_user"],
            "Menghapus alat: " . $namaAlatLog
        );

        $pesan = "Alat berhasil dihapus.";
    } else {
        $pesan = "Alat tidak bisa dihapus. Pastikan alat belum digunakan dalam data peminjaman.";
        $jenisPesan = "error";
    }

    $stmt->close();
}

// DATA KATEGORI
$dataKategori = $koneksi->query(
    "SELECT id_kategori, nama_kategori
     FROM kategori
     ORDER BY nama_kategori ASC"
);

// DATA ALAT
$dataAlat = $koneksi->query(
    "SELECT alat.id_alat, alat.nama_alat, alat.stok,
            kategori.nama_kategori
     FROM alat
     JOIN kategori ON alat.id_kategori = kategori.id_kategori
     ORDER BY alat.id_alat DESC"
);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Alat - Peminjaman Alat</title>

    <style>
        * { box-sizing: border-box; }

        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #f1f5f9;
            color: #1e293b;
        }

        header {
            background: #1d4ed8;
            color: white;
            padding: 22px;
        }

        header h1 { margin: 0 0 8px; }

        nav {
            background: white;
            padding: 15px 22px;
            display: flex;
            gap: 18px;
            flex-wrap: wrap;
        }

        nav a {
            color: #1d4ed8;
            text-decoration: none;
            font-weight: bold;
        }

        main { padding: 24px; }

        .panel {
            background: white;
            padding: 22px;
            border-radius: 12px;
            margin-bottom: 22px;
            overflow-x: auto;
        }

        input, select, button {
            padding: 10px;
            margin: 5px 0;
            font: inherit;
        }

        input, select {
            width: 100%;
            max-width: 400px;
        }

        button {
            border: 0;
            border-radius: 6px;
            background: #1d4ed8;
            color: white;
            cursor: pointer;
        }

        .hapus { background: #dc2626; }

        .pesan {
            padding: 12px;
            margin-bottom: 15px;
            border-radius: 6px;
            background: #dcfce7;
        }

        .error { background: #fee2e2; }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 500px;
        }

        th, td {
            border-bottom: 1px solid #e2e8f0;
            padding: 12px;
            text-align: left;
        }

        th { background: #eff6ff; }

        @media (max-width: 600px) {
            main { padding: 14px; }
        }
    </style>
</head>

<body>
<header>
    <h1><?= $isAdmin ? "Kelola Data Alat" : "Daftar Alat" ?></h1>
    <p>Halo, <?= aman($_SESSION["nama"] ?? "") ?>!</p>
</header>

<nav>
    <?php if ($isAdmin): ?>
        <a href="dashboard.php">Dashboard</a>
        <a href="pengguna.php">Data Pengguna</a>
        <a href="kategori.php">Data Kategori</a>
        <a href="alat.php">Data Alat</a>
        <a href="log_aktivitas.php">Log Aktivitas</a>
    <?php else: ?>
        <a href="dashboard_peminjam.php">Dashboard</a>
        <a href="alat.php">Daftar Alat</a>
        <a href="pengajuan.php">Ajukan Peminjaman</a>
    <?php endif; ?>

    <a href="logout.php">Logout</a>
</nav>

<main>
    <?php if ($pesan !== ""): ?>
        <div class="pesan <?= $jenisPesan === "error" ? "error" : "" ?>">
            <?= aman($pesan) ?>
        </div>
    <?php endif; ?>

    <?php if ($isAdmin): ?>
        <section class="panel">
            <h2>Tambah Alat Baru</h2>

            <form method="POST">
                <label>Nama alat</label><br>
                <input type="text" name="nama_alat"
                       placeholder="Contoh: Laptop" required><br>

                <label>Kategori</label><br>
                <select name="id_kategori" required>
                    <option value="">-- Pilih kategori --</option>
                    <?php while ($kategori = $dataKategori->fetch_assoc()): ?>
                        <option value="<?= (int) $kategori["id_kategori"] ?>">
                            <?= aman($kategori["nama_kategori"]) ?>
                        </option>
                    <?php endwhile; ?>
                </select><br>

                <label>Stok awal</label><br>
                <input type="number" name="stok" min="0" step="1"
                       placeholder="Contoh: 5" required><br>

                <button type="submit" name="tambah">Tambah Alat</button>
            </form>
        </section>

        <section class="panel">
            <h2>Tambah Stok Alat</h2>

            <form method="POST">
                <label>Pilih alat</label><br>
                <select name="id_alat" required>
                    <option value="">-- Pilih alat --</option>
                    <?php
                    $pilihanAlat = $koneksi->query(
                        "SELECT id_alat, nama_alat, stok
                         FROM alat ORDER BY nama_alat ASC"
                    );

                    while ($pilihan = $pilihanAlat->fetch_assoc()):
                    ?>
                        <option value="<?= (int) $pilihan["id_alat"] ?>">
                            <?= aman($pilihan["nama_alat"]) ?>
                            (Stok: <?= (int) $pilihan["stok"] ?>)
                        </option>
                    <?php endwhile; ?>
                </select><br>

                <label>Jumlah stok tambahan</label><br>
                <input type="number" name="jumlah_stok" min="1"
                       step="1" placeholder="Contoh: 3" required><br>

                <button type="submit" name="tambah_stok">
                    Tambah Stok
                </button>
            </form>
        </section>
    <?php endif; ?>

    <section class="panel">
        <h2><?= $isAdmin ? "Data Alat" : "Daftar Alat Tersedia" ?></h2>

        <?php if ($dataAlat && $dataAlat->num_rows > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>No.</th>
                        <th>Nama Alat</th>
                        <th>Kategori</th>
                        <th>Stok</th>
                        <?php if ($isAdmin): ?>
                            <th>Aksi</th>
                        <?php endif; ?>
                    </tr>
                </thead>

                <tbody>
                    <?php $no = 1; ?>
                    <?php while ($alat = $dataAlat->fetch_assoc()): ?>
                        <tr>
                            <td><?= $no++ ?></td>
                            <td><?= aman($alat["nama_alat"]) ?></td>
                            <td><?= aman($alat["nama_kategori"]) ?></td>
                            <td><?= (int) $alat["stok"] ?></td>

                            <?php if ($isAdmin): ?>
                                <td>
                                    <form method="POST"
                                        onsubmit="return confirm('Yakin ingin menghapus alat ini?')">
                                        <input type="hidden" name="id_alat"
                                            value="<?= (int) $alat["id_alat"] ?>">

                                        <button class="hapus"
                                            type="submit" name="hapus">
                                            Hapus
                                        </button>
                                    </form>
                                </td>
                            <?php endif; ?>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>Belum ada data alat.</p>
        <?php endif; ?>
    </section>
</main>
</body>
</html>