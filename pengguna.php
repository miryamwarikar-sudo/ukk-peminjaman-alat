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

// Menambahkan pengguna
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["tambah"])) {
    $nama = trim($_POST["nama"] ?? "");
    $username = trim($_POST["username"] ?? "");
    $password = $_POST["password"] ?? "";
    $role = $_POST["role"] ?? "";

    if (
        $nama === "" ||
        $username === "" ||
        $password === "" ||
        !in_array($role, ["admin", "petugas", "peminjam"], true)
    ) {
        $pesan = "Semua data wajib diisi dengan benar.";
        $jenisPesan = "error";
    } elseif (strlen($password) < 8) {
        $pesan = "Password minimal 8 karakter.";
        $jenisPesan = "error";
    } else {
        $cek = $koneksi->prepare(
            "SELECT id_user FROM users WHERE username = ?"
        );
        $cek->bind_param("s", $username);
        $cek->execute();
        $hasilCek = $cek->get_result();

        if ($hasilCek->num_rows > 0) {
            $pesan = "Username sudah digunakan.";
            $jenisPesan = "error";
        } else {
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $koneksi->prepare(
                "INSERT INTO users (nama, username, password, role)
                 VALUES (?, ?, ?, ?)"
            );

            $stmt->bind_param(
                "ssss",
                $nama,
                $username,
                $passwordHash,
                $role
            );

            if ($stmt->execute()) {
                catatAktivitas(
                    $koneksi,
                    (int) $_SESSION["id_user"],
                    "Menambahkan pengguna: " . $nama .
                    " (username: " . $username . ", peran: " . $role . ")"
                );

                $pesan = "Pengguna berhasil ditambahkan.";
            } else {
                $pesan = "Pengguna gagal ditambahkan.";
                $jenisPesan = "error";
            }

            $stmt->close();
        }

        $cek->close();
    }
}

// Menghapus pengguna
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["hapus"])) {
    $idHapus = (int) ($_POST["id_user"] ?? 0);
    $idLogin = (int) $_SESSION["id_user"];

    if ($idHapus === $idLogin) {
        $pesan = "Akun yang sedang digunakan tidak bisa dihapus.";
        $jenisPesan = "error";
    } else {
        // Ambil identitas pengguna sebelum menghapus
        $cek = $koneksi->prepare(
            "SELECT nama, username, role FROM users WHERE id_user = ?"
        );
        $cek->bind_param("i", $idHapus);
        $cek->execute();
        $penggunaHapus = $cek->get_result()->fetch_assoc();
        $cek->close();

        if (!$penggunaHapus) {
            $pesan = "Pengguna tidak ditemukan.";
            $jenisPesan = "error";
        } else {
            $stmt = $koneksi->prepare(
                "DELETE FROM users WHERE id_user = ?"
            );
            $stmt->bind_param("i", $idHapus);

            if ($stmt->execute() && $stmt->affected_rows > 0) {
                catatAktivitas(
                    $koneksi,
                    $idLogin,
                    "Menghapus pengguna: " . $penggunaHapus["nama"] .
                    " (username: " . $penggunaHapus["username"] .
                    ", peran: " . $penggunaHapus["role"] . ")"
                );

                $pesan = "Pengguna berhasil dihapus.";
            } else {
                $pesan = "Pengguna tidak bisa dihapus. Pastikan akun belum digunakan dalam data peminjaman atau log aktivitas.";
                $jenisPesan = "error";
            }

            $stmt->close();
        }
    }
}

// Mengambil daftar pengguna
$dataPengguna = $koneksi->query(
    "SELECT id_user, nama, username, role
     FROM users ORDER BY id_user DESC"
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
    <title>Data Pengguna</title>

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
            max-width: 1000px;
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

        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 12px;
        }

        input, select {
            width: 100%;
            padding: 11px;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            background: white;
        }

        button, .link {
            display: inline-block;
            padding: 10px 15px;
            border: none;
            border-radius: 6px;
            background: #2563eb;
            color: white;
            text-decoration: none;
            cursor: pointer;
        }

        .hapus {
            background: #dc2626;
        }

        .pesan {
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 18px;
        }

        .success {
            background: #dcfce7;
            color: #166534;
        }

        .error {
            background: #fee2e2;
            color: #991b1b;
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
        }

        th {
            background: #eff6ff;
        }

        @media (max-width: 600px) {
            body {
                padding: 12px;
            }
        }
    </style>
</head>

<body>
<div class="container">

    <div class="panel">
        <h1>Data Pengguna</h1>
        <p>Kelola akun admin, petugas, dan peminjam.</p>
        <a href="dashboard.php" class="link">Kembali ke Dashboard</a>
    </div>

    <?php if ($pesan !== ""): ?>
        <div class="pesan <?= aman($jenisPesan) ?>">
            <?= aman($pesan) ?>
        </div>
    <?php endif; ?>

    <div class="panel">
        <h2>Tambah Pengguna</h2>

        <form method="POST">
            <div class="form-grid">
                <input
                    type="text"
                    name="nama"
                    placeholder="Nama lengkap"
                    maxlength="100"
                    required
                >

                <input
                    type="text"
                    name="username"
                    placeholder="Username"
                    maxlength="50"
                    required
                >

                <input
                    type="password"
                    name="password"
                    placeholder="Password (minimal 8 karakter)"
                    minlength="8"
                    required
                >

                <select name="role" required>
                    <option value="">Pilih peran</option>
                    <option value="admin">Admin</option>
                    <option value="petugas">Petugas</option>
                    <option value="peminjam">Peminjam</option>
                </select>
            </div>

            <br>
            <button type="submit" name="tambah">Tambah Pengguna</button>
        </form>
    </div>

    <div class="panel">
        <h2>Daftar Pengguna</h2>

        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>No.</th>
                        <th>Nama</th>
                        <th>Username</th>
                        <th>Peran</th>
                        <th>Aksi</th>
                    </tr>
                </thead>

                <tbody>
                <?php if ($dataPengguna && $dataPengguna->num_rows > 0): ?>
                    <?php $no = 1; ?>
                    <?php while ($pengguna = $dataPengguna->fetch_assoc()): ?>
                        <tr>
                            <td><?= $no++ ?></td>
                            <td><?= aman($pengguna["nama"]) ?></td>
                            <td><?= aman($pengguna["username"]) ?></td>
                            <td><?= ucfirst(aman($pengguna["role"])) ?></td>
                            <td>
                                <?php if ((int) $pengguna["id_user"] !== (int) $_SESSION["id_user"]): ?>
                                    <form method="POST"
                                          onsubmit="return confirm('Yakin ingin menghapus pengguna ini?')">
                                        <input
                                            type="hidden"
                                            name="id_user"
                                            value="<?= (int) $pengguna["id_user"] ?>"
                                        >

                                        <button
                                            type="submit"
                                            name="hapus"
                                            class="hapus"
                                        >
                                            Hapus
                                        </button>
                                    </form>
                                <?php else: ?>
                                    Akun aktif
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5">Belum ada data pengguna.</td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>
</body>
</html>