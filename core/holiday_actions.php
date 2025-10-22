<?php
session_start();
require_once __DIR__ . '/../config/config.php';

// Proteksi: hanya admin yang bisa melakukan aksi
if ($_SESSION['user_role'] !== 'admin') {
    $_SESSION['flash_message'] = [
        'type' => 'danger',
        'message' => 'Akses ditolak.'
    ];
    header('Location: ../manage_holidays.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {

    // Aksi Tambah Hari Libur
    if ($_POST['action'] === 'add_holiday') {
        $holiday_date = $_POST['holiday_date'] ?? '';
        $description = trim($_POST['description'] ?? '');

        if (empty($holiday_date) || empty($description)) {
            $_SESSION['flash_message'] = ['type' => 'danger', 'message' => 'Tanggal dan keterangan tidak boleh kosong.'];
        } else {
            try {
                $stmt = $pdo->prepare("INSERT INTO holidays (holiday_date, description) VALUES (:holiday_date, :description)");
                $stmt->execute([':holiday_date' => $holiday_date, ':description' => $description]);
                $_SESSION['flash_message'] = ['type' => 'success', 'message' => 'Hari libur berhasil ditambahkan.'];
            } catch (PDOException $e) {
                if ($e->getCode() == 23000) { // Duplicate entry
                    $_SESSION['flash_message'] = ['type' => 'danger', 'message' => 'Tanggal hari libur sudah ada.'];
                } else {
                    $_SESSION['flash_message'] = ['type' => 'danger', 'message' => 'Gagal menambahkan hari libur: ' . $e->getMessage()];
                }
            }
        }
    }

    // Aksi Hapus Hari Libur
    elseif ($_POST['action'] === 'delete_holiday') {
        $holiday_id = $_POST['holiday_id'] ?? 0;

        if ($holiday_id > 0) {
            try {
                $stmt = $pdo->prepare("DELETE FROM holidays WHERE id = :id");
                $stmt->execute([':id' => $holiday_id]);
                $_SESSION['flash_message'] = ['type' => 'success', 'message' => 'Hari libur berhasil dihapus.'];
            } catch (PDOException $e) {
                $_SESSION['flash_message'] = ['type' => 'danger', 'message' => 'Gagal menghapus hari libur: ' . $e->getMessage()];
            }
        } else {
            $_SESSION['flash_message'] = ['type' => 'danger', 'message' => 'ID hari libur tidak valid.'];
        }
    }
}

header('Location: ../manage_holidays.php');
exit;
