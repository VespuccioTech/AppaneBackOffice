<?php
require_once("config.php");

$messaggio = $errore = '';
$giorni_settimana = ['Lunedì', 'Martedì', 'Mercoledì', 'Giovedì', 'Venerdì', 'Sabato', 'Domenica'];

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['aggiorna_menu'])) {
    $ripub = $_POST['giorno_ripubblicazione'] ?? 'Giovedì';
    $fine = $_POST['giorno_fine_ordinazioni'] ?? 'Venerdì';
    $prodotti_menu = $_POST['prodotti_menu'] ?? [];

    try {
        $pdo->beginTransaction();
        $stmt = $pdo->prepare("INSERT INTO tmenu_settimanale (giorno_ripubblicazione, giorno_fine_ordinazioni, data) VALUES (?, ?, CURDATE())");
        $stmt->execute([$ripub, $fine]);
        $id_menu = $pdo->lastInsertId();

        if (!empty($prodotti_menu)) {
            $stmt_prod = $pdo->prepare("INSERT INTO tproduzione (nome_prodotto, id_menu) VALUES (?, ?)");
            foreach ($prodotti_menu as $p_nome) { $stmt_prod->execute([$p_nome, $id_menu]); }
        }
        $pdo->commit();
        $messaggio = "Menù e orari aggiornati con successo!";
    } catch (\PDOException $e) {
        $pdo->rollBack();
        $errore = "Errore durante l'aggiornamento: " . $e->getMessage();
    }
}

$giorno_ripub_attuale = 'Giovedì';
$giorno_fine_attuale = 'Venerdì';
$prodotti_attivi = []; // Nuovo array per memorizzare i prodotti già nel menù

try {
    // Aggiunto 'id_menu' alla SELECT
    $menu_attuale = $pdo->query("SELECT id_menu, giorno_ripubblicazione, giorno_fine_ordinazioni FROM tmenu_settimanale ORDER BY id_menu DESC LIMIT 1")->fetch();
    if ($menu_attuale) {
        if(!empty($menu_attuale['giorno_ripubblicazione'])) $giorno_ripub_attuale = $menu_attuale['giorno_ripubblicazione'];
        if(!empty($menu_attuale['giorno_fine_ordinazioni'])) $giorno_fine_attuale = $menu_attuale['giorno_fine_ordinazioni'];
        
        // Recupero i prodotti attualmente associati all'ultimo menù
        $stmt_prod_attivi = $pdo->prepare("SELECT nome_prodotto FROM tproduzione WHERE id_menu = ?");
        $stmt_prod_attivi->execute([$menu_attuale['id_menu']]);
        $prodotti_attivi = $stmt_prod_attivi->fetchAll(PDO::FETCH_COLUMN); // Restituisce un array semplice di nomi
    }
} catch (\PDOException $e) {}

// Query aggiornata per estrarre anche l'immagine
$prodotti_per_tipo = [];
$sql = "SELECT p.*, 
               GROUP_CONCAT(c.nome_ingrediente SEPARATOR ', ') as ingredienti,
               (SELECT percorso_file FROM timmagine_prodotto ip WHERE ip.nome_prodotto = p.nome LIMIT 1) as immagine
        FROM tprodotto p 
        LEFT JOIN tcomposizione c ON p.nome = c.nome_prodotto 
        GROUP BY p.nome";
$stmt = $pdo->query($sql);
while ($row = $stmt->fetch()) { 
    $prodotti_per_tipo[$row['tipo']][] = $row;
}
?>
<!DOCTYPE html>
<html lang="it">
<head><meta charset="UTF-8"><title>Gestione Appane - Dashboard</title><link rel="stylesheet" href="style.css"></head>
<body>
<div class="dashboard-wrapper">
    <header class="main-header">
        <a href="index.php" class="logo-link"><img src="appane logo.jpg" alt="Logo Appane" style="height: 70px; width: auto;"></a>
        <div class="nav-title">GESTIONE DI APPANE</div>
        <div class="header-nav-group">
            <a href="ordini.php" style="color: white; font-weight: bold;">VISUALIZZAZIONE ORDINI</a>
            <a href="ingredienti.php" style="color: #E9D5FF; font-weight: bold; font-size: 0.85rem;">LISTA INGREDIENTI</a>
            <a href="clienti.php" style="color: #E9D5FF; font-weight: bold; font-size: 0.85rem;">LISTA CLIENTI</a>
            <a href="riepiloghi.php" style="color: #FFD700; font-weight: bold; font-size: 0.85rem;">RIEPILOGO INCASSI</a>
        </div>
    </header>

    <form action="" method="POST" style="display: flex; flex-direction: column; flex-grow: 1; overflow: hidden;">
        <nav class="sub-nav">
            <div>
                <label>Ripubblicazione menù: </label>
                <select name="giorno_ripubblicazione">
                    <?php foreach($giorni_settimana as $g) echo "<option" . ($g == $giorno_ripub_attuale ? " selected" : "") . ">$g</option>"; ?>
                </select>
            </div>
            <div>
                <label>Fine ordinazioni: </label>
                <select name="giorno_fine_ordinazioni">
                    <?php foreach($giorni_settimana as $g) echo "<option" . ($g == $giorno_fine_attuale ? " selected" : "") . ">$g</option>"; ?>
                </select>
            </div>
        </nav>

        <main class="content-area">
            <?php if($messaggio) echo "<div class='alert alert-success'>$messaggio</div>"; ?>
            <?php if($errore) echo "<div class='alert alert-error'>$errore</div>"; ?>
            
            <?php if (empty($prodotti_per_tipo)): ?>
                <div style="text-align:center; padding: 50px; color: #8B4513; border: 2px dashed #D4A373;">Nessun prodotto trovato.</div>
            <?php else: ?>
                <?php foreach ($prodotti_per_tipo as $tipo => $prodotti): ?>
                    <h2 style="margin: 20px 0 15px 0; border-bottom: 2px solid #D4A373; color: #8B4513;"><?php echo htmlspecialchars($tipo ?? 'Non categorizzato'); ?></h2>
                    <div class="grid-layout">
                        <?php foreach ($prodotti as $p): ?>
                            <div class="card">
                                <?php if(!empty($p['immagine'])): ?>
                                    <img src="<?php echo htmlspecialchars($p['immagine']); ?>" alt="<?php echo htmlspecialchars($p['nome']); ?>" style="width: 100%; height: 150px; object-fit: cover;">
                                <?php endif; ?>

                                <div class="card-header"><?php echo htmlspecialchars($p['nome']); ?> - €<?php echo htmlspecialchars($p['prezzo']); ?></div>
                                <div style="padding: 15px; flex-grow: 1; font-size: 0.9rem;"><?php echo htmlspecialchars($p['descrizione'] ?? ''); ?></div>
                                <div style="padding: 15px; background: #FFFAF4; border-top: 1px solid #D4A373; font-size: 0.8rem;">
                                    <strong>Ing:</strong> <?php echo htmlspecialchars($p['ingredienti'] ?? 'Nessuno'); ?>
                                <div style="margin-top: 10px; display: flex; justify-content: space-between; align-items: center;">
                                    <label><input type="checkbox" name="prodotti_menu[]" value="<?php echo htmlspecialchars($p['nome']); ?>" <?php echo in_array($p['nome'], $prodotti_attivi) ? 'checked' : ''; ?>> Includi nel menù</label>
                                    <a href="modifica_prodotto.php?nome=<?php echo urlencode($p['nome']); ?>" style="background: #5E3A8C; color: white; padding: 5px 10px; border-radius: 5px; text-decoration: none; font-size: 0.8rem; font-weight: bold;">✏️ Modifica</a>
                                </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </main>

        <div class="action-bar">
            <button type="submit" name="aggiorna_menu" class="btn btn-bread">Aggiorna il menù settimanale</button>
            <a href="aggiungi_prodotto.php" class="btn btn-purple">+ Aggiungi prodotto</a>
        </div>
    </form>
</div>
</body>
</html>
