<?php
$koneksi = new mysqli(
    "localhost",
    "root",
    "",
    "peminjaman_alat"
);

if ($koneksi->connect_error) {
    die("Koneksi database gagal.");
}
$koneksi->set_charset("utf8mb4");
?>