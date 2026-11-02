<?php
session_start();

$koneksi = new mysqli("localhost", "root", "", "peminjaman_alat");

if ($koneksi->connect_error) {
    die("Koneksi database gagal.");
}

$koneksi->set_charset("utf8mb4");

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: index.php");
    exit;
}

$username = trim($_POST["username"] ?? "");
$password = $_POST["password"] ?? "";

$stmt = $koneksi->prepare(
    "SELECT id_user, nama, username, password, role
     FROM users
     WHERE username = ?"
);

$stmt->bind_param("s", $username);
$stmt->execute();

$hasil = $stmt->get_result();
$user = $hasil->fetch_assoc();

if ($user && password_verify($password, $user["password"])) {
    session_regenerate_id(true);

    $_SESSION["id_user"] = $user["id_user"];
    $_SESSION["nama"] = $user["nama"];
    $_SESSION["role"] = $user["role"];

    $stmt->close();
    $koneksi->close();

    if ($user["role"] === "admin") {
        header("Location: dashboard.php");
    } elseif ($user["role"] === "petugas") {
        header("Location: dashboard_petugas.php");
    } else {
        header("Location: dashboard_peminjam.php");
    }

    exit;
}

$stmt->close();
$koneksi->close();

header("Location: index.php?error=1");
exit;
?>