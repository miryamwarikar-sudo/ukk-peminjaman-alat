<?php
$koneksi = new mysqli("localhost", "root", "", "peminjaman_alat");

if ($koneksi->connect_error) {
    die("Koneksi gagal: " . $koneksi->connect_error);
}

$nama = "Admin";
$username = "admin";
$password = password_hash("Admin123!", PASSWORD_DEFAULT);
$role = "admin";

$stmt = $koneksi->prepare(
    "INSERT INTO users (nama, username, password, role)
     VALUES (?, ?, ?, ?)"
);

$stmt->bind_param("ssss", $nama, $username, $password, $role);

if ($stmt->execute()) {
    echo "Akun admin berhasil dibuat!";
} else {
    echo "Gagal membuat akun: " . $stmt->error;
}

$stmt->close();
$koneksi->close();
?>