<?php
// Includiamo il config.php (che avvierà la sessione, ma non ci bloccherà perché siamo su login.php)
require_once("config.php");

$errore = '';

// Se l'utente è già loggato, lo mandiamo alla dashboard
if (isset($_SESSION['admin_loggato'])) {
    header("Location: index.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    // INSERISCI QUI LE TUE CREDENZIALI AMMINISTRATORE
    $admin_user = 'appane';
    $admin_pass = 'appane'; 

    if ($username === $admin_user && $password === $admin_pass) {
        $_SESSION['admin_loggato'] = true;
        header("Location: index.php");
        exit;
    } else {
        $errore = "Credenziali amministratore non valide.";
    }
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Login Backoffice - Appane</title>
    <link rel="stylesheet" href="style.css">
</head>
<body style="background-color: #5E3A8C;"> <div class="dashboard-wrapper" style="justify-content: center; align-items: center;">
    <main class="content-area" style="display:flex; justify-content:center; align-items:center;">
        <div class="form-container" style="width: 400px; box-shadow: 0 10px 30px rgba(0,0,0,0.3);">
            <div style="text-align: center; margin-bottom: 20px;">
                <img src="appane logo.jpg" alt="Logo Appane" style="height: 80px; border-radius: 8px;">
            </div>
            <h2 style="color: #8B4513; text-align:center; margin-bottom: 20px;">ACCESSO ADMIN</h2>
            
            <?php if($errore) echo "<div class='alert alert-error'>$errore</div>"; ?>
            
            <form method="POST">
                <div class="form-row"><div class="form-col">
                    <label class="form-label">Username Admin</label>
                    <input type="text" name="username" class="form-control" required>
                </div></div>
                <div class="form-row"><div class="form-col">
                    <label class="form-label">Password Admin</label>
                    <input type="password" name="password" class="form-control" required>
                </div></div>
                <button type="submit" class="btn btn-purple" style="width: 100%;">Entra nel Gestionale</button>
            </form>
        </div>
    </main>
</div>
</body>
</html>