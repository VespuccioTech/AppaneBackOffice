<?php
session_start();

// --- NUOVO CONTROLLO ACCESSI BACKOFFICE ---
// Otteniamo il nome della pagina corrente
$pagina_corrente = basename($_SERVER['PHP_SELF']);

// Se non siamo sulla pagina di login e l'admin non è loggato, caccialo fuori
if ($pagina_corrente !== 'login.php' && !isset($_SESSION['admin_loggato'])) {
    header("Location: login.php");
    exit;
}
// ------------------------------------------

$host = 'localhost'; $db = 'appane_vespa'; $user = 'root'; $pass = '';
try { 
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
} 
catch (\PDOException $e) { 
    die("Errore DB: " . $e->getMessage()); 
}
?>