<?php
// Include Composer's autoloader
require_once __DIR__ . '/../vendor/autoload.php';

// Enable error reporting for development
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Timezone Setting
date_default_timezone_set('Asia/Jakarta');

// Database Credentials
define('DB_HOST', '127.0.0.1'); // atau 'localhost'
define('DB_USER', 'root');
define('DB_PASS', ''); // Sesuaikan dengan password database Anda
define('DB_NAME', 'pkl_digital_app');

// Application URL
define('BASE_URL', 'http://localhost/pkl-digital'); // Sesuaikan dengan URL proyek Anda

// PDO Database Connection
try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    // Set the PDO error mode to exception
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e){
    die("ERROR: Could not connect. " . $e->getMessage());
}

// Start Session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Load School Settings
// Set default values first as a fallback
$default_settings = [
    'school_name' => 'PKL Digital',
    'school_address' => 'Alamat Sekolah Belum Diatur',
    'school_logo' => ''
];

$db_settings = [];
try {
    // Fetch settings from DB directly into a key-value pair array
    $stmt = $pdo->query("SELECT setting_key, setting_value FROM school_settings");
    $db_settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
} catch (PDOException $e) {
    // If table doesn't exist or another DB error, $db_settings will remain empty.
    // This makes the app resilient during initial setup.
}

// Merge database settings over default settings.
// Values from $db_settings will overwrite values from $default_settings.
$app_settings = array_merge($default_settings, $db_settings);

// Load Active Academic Year
$active_year = null;
try {
    $stmt_year = $pdo->prepare("SELECT * FROM academic_years WHERE status = 'active' LIMIT 1");
    $stmt_year->execute();
    $result = $stmt_year->fetch(PDO::FETCH_ASSOC);
    if ($result) {
        $active_year = $result;
    } else {
        // Fallback jika tidak ada yang aktif, ambil yang terakhir dibuat
        $stmt_last_year = $pdo->query("SELECT * FROM academic_years ORDER BY id DESC LIMIT 1");
        $active_year = $stmt_last_year->fetch(PDO::FETCH_ASSOC) ?: null;
    }
} catch (PDOException $e) {
    // Biarkan $active_year null jika ada error
}

// Automatically set the selected academic year in session if not already set
// This ensures all pages have a default active year.
if (isset($_SESSION['user_id']) && !isset($_SESSION['selected_academic_year_id'])) {
    if ($active_year) {
        $_SESSION['selected_academic_year_id'] = $active_year['id'];
        $_SESSION['selected_academic_year_name'] = $active_year['year_name'];
    }
}
?>