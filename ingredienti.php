<?php
require_once("config.php");

$giorni_settimana = ['Lunedì', 'Martedì', 'Mercoledì', 'Giovedì', 'Venerdì', 'Sabato', 'Domenica'];
$giorno_ripub_attuale = 'Mercoledì'; $giorno_fine_attuale = 'Venerdì';
try {
    $menu_attuale = $pdo->query("SELECT giorno_ripubblicazione, giorno_fine_ordinazioni FROM tmenu_settimanale ORDER BY id_menu DESC LIMIT 1")->fetch();
    if ($menu_attuale) {
        if(!empty($menu_attuale['giorno_ripubblicazione'])) $giorno_ripub_attuale = $menu_attuale['giorno_ripubblicazione'];
        if(!empty($menu_attuale['giorno_fine_ordinazioni'])) $giorno_fine_attuale = $menu_attuale['giorno_fine_ordinazioni'];
    }
} catch (\PDOException $e) {}

$ingredienti = $pdo->query("SELECT * FROM tingrediente ORDER BY tipo, nome")->fetchAll();
?>
<!DOCTYPE html>
<html lang="it">
<head><meta charset="UTF-8"><title>Lista Ingredienti - Appane</title><link rel="stylesheet" href="style.css"></head>
<body>
<div class="dashboard-wrapper">
    <header class="main-header">
        <a href="index.php" class="logo-link"><img src="appane logo.jpg" alt="Logo Appane" style="height: 70px; width: auto;"></a>
        <div class="nav-title">ARCHIVIO INGREDIENTI</div>
        <div class="header-nav-group">
            <a href="ordini.php" style="color: white; font-weight: bold;">VISUALIZZAZIONE ORDINI</a>
            <a href="ingredienti.php" style="color: #E9D5FF; font-weight: bold; font-size: 0.85rem;">LISTA INGREDIENTI</a>
            <a href="clienti.php" style="color: #E9D5FF; font-weight: bold; font-size: 0.85rem;">LISTA CLIENTI</a>
        </div>
    </header>

    <nav class="sub-nav">
        <div><a href="index.php" style="color: #5E3A8C; font-weight: bold; text-decoration: none;">← Torna alla Dashboard</a></div>
        <div><label>Ripubblicazione: </label><select disabled><?php foreach($giorni_settimana as $g) echo "<option" . ($g == $giorno_ripub_attuale ? " selected" : "") . ">$g</option>"; ?></select></div>
        <div><label>Fine ordinazioni: </label><select disabled><?php foreach($giorni_settimana as $g) echo "<option" . ($g == $giorno_fine_attuale ? " selected" : "") . ">$g</option>"; ?></select></div>
    </nav>

    <main class="content-area">
        <?php if (empty($ingredienti)): ?>
            <div style="text-align:center; padding: 50px; color: #8B4513; border: 2px dashed #D4A373;">Nessun ingrediente presente nel database.</div>
        <?php else: ?>
            <div class="grid-layout">
                <?php foreach ($ingredienti as $ing): ?>
                    <div class="card" style="border-left: 5px solid #5E3A8C;">
                        <div class="card-header" style="font-size: 0.85rem; text-transform: uppercase;">
                            <?php echo htmlspecialchars($ing['tipo']??'Altro'); ?>
                        </div>
                        <div style="padding: 20px;">
                            <h3 style="color:#8B4513; margin-bottom:10px;"><?php echo htmlspecialchars($ing['nome']); ?></h3>
                            <p style="font-size: 0.9rem;"><?php echo htmlspecialchars($ing['descrizione']??''); ?></p>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </main>

    <div class="action-bar"><a href="aggiungi_ingrediente.php" class="btn btn-bread">+ Nuovo Ingrediente</a></div>
</div>
</body>
</html>