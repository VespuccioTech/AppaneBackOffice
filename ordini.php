<?php
require_once("config.php");
// Lettura variabili dinamiche giorni
$giorni_settimana = ['Lunedì', 'Martedì', 'Mercoledì', 'Giovedì', 'Venerdì', 'Sabato', 'Domenica'];
$giorno_ripub_attuale = 'Mercoledì'; $giorno_fine_attuale = 'Venerdì';

try {
    $menu_attuale = $pdo->query("SELECT giorno_ripubblicazione, giorno_fine_ordinazioni FROM menu_settimanale ORDER BY id_menu DESC LIMIT 1")->fetch();
    if ($menu_attuale) {
        if(!empty($menu_attuale['giorno_ripubblicazione'])) $giorno_ripub_attuale = $menu_attuale['giorno_ripubblicazione'];
        if(!empty($menu_attuale['giorno_fine_ordinazioni'])) $giorno_fine_attuale = $menu_attuale['giorno_fine_ordinazioni'];
    }
} catch (\PDOException $e) {}

// Sostituito c.nome_cognome con c.nome, c.cognome
$ordini = $pdo->query("SELECT o.id_ordine, o.data, o.consegna_effettuata, i.citta, i.via, i.n_civico, c.nome, c.cognome, c.n_telefono FROM ordine o JOIN indirizzo_di_consegna i ON o.id_indirizzo = i.id_indirizzo JOIN registrazione r ON o.username_account = r.username_account JOIN cliente c ON r.email_cliente = c.email ORDER BY o.data DESC")->fetchAll();

$tutti_prodotti = $pdo->query("SELECT id_ordine, nome_prodotto, quantita FROM selezione")->fetchAll();
$prodotti_per_ordine = []; 
foreach ($tutti_prodotti as $p) { 
    $prodotti_per_ordine[$p['id_ordine']][] = $p;
}
?>
<!DOCTYPE html>
<html lang="it">
<head><meta charset="UTF-8"><title>Ordini - Appane</title><link rel="stylesheet" href="style.css"></head>
<body>
<div class="dashboard-wrapper">
    <header class="main-header">
        <a href="index.php" class="logo-link"><img src="appane logo.jpg" alt="Logo Appane" style="height: 70px; width: auto;"></a>
        <div class="nav-title">VISUALIZZAZIONE ORDINI</div>
        <div class="header-nav-group"><a href="ordini.php" style="color: white; font-weight: bold;">VISUALIZZAZIONE ORDINI</a><a href="ingredienti.php" style="color: #E9D5FF; font-weight: bold; font-size: 0.85rem;">LISTA INGREDIENTI</a></div>
    </header>

    <nav class="sub-nav">
        <div><a href="index.php" style="color: #5E3A8C; font-weight: bold; text-decoration: none;">← Torna alla Dashboard</a></div>
        <div><label>Ripubblicazione: </label>
        <select disabled><?php foreach($giorni_settimana as $g) echo "<option" . ($g == $giorno_ripub_attuale ? " selected" : "") . ">$g</option>"; ?></select></div>
        <div><label>Fine ordinazioni: </label>
        <select disabled><?php foreach($giorni_settimana as $g) echo "<option" . ($g == $giorno_fine_attuale ? " selected" : "") . ">$g</option>"; ?></select></div>
    </nav>

    <main class="content-area">
        <?php if (empty($ordini)): ?>
            <div style="text-align:center; padding: 50px; color: #8B4513; border: 2px dashed #D4A373;">Nessun ordine presente nel database.</div>
        <?php else: ?>
            <?php foreach ($ordini as $ordine): 
                // Assegnazione diretta dai nuovi campi del database
                $nome = $ordine['nome'] ?? '-'; 
                $cognome = $ordine['cognome'] ?? '-';
            ?>
            <div class="order-card">
                <div class="col-address">
                    <div class="box"><span class="label">Città</span><span class="value"><?php echo htmlspecialchars($ordine['citta']); ?></span></div>
                    <div class="box"><span class="label">Via</span><span class="value"><?php echo htmlspecialchars($ordine['via']); ?></span></div>
                    <div class="box"><span class="label">N. Civico</span><span class="value"><?php echo htmlspecialchars($ordine['n_civico']); ?></span></div>
                </div>
                <div class="col-info">
                    <div class="split-row">
                        <div class="box"><span class="label">Nome</span><span class="value"><?php echo htmlspecialchars($nome); ?></span></div>
                        <div class="box"><span class="label">Cognome</span><span class="value"><?php echo htmlspecialchars($cognome); ?></span></div>
                    </div>
                    <div class="box"><span class="label">Telefono</span><span class="value"><?php echo htmlspecialchars($ordine['n_telefono']??'-'); ?></span></div>
                    <div class="split-row">
                        <div class="box"><span class="label">Data Ordine</span><span class="value"><?php echo date('d/m/y H:i', strtotime($ordine['data'])); ?></span></div>
                        <div class="box"><span class="label">Accettato</span><span class="status-indicator <?php echo $ordine['consegna_effettuata']?'status-G':'status-R'; ?>"><?php echo $ordine['consegna_effettuata']?'G':'R'; ?></span></div>
                    </div>
                    <div class="box" style="background:#FFFAF4;"><span class="label">Stato</span><span class="value" style="color:<?php echo $ordine['consegna_effettuata']?'#79B473':'#D6604D'; ?>;"><?php echo $ordine['consegna_effettuata']?'Concluso':'In attesa'; ?></span></div>
                </div>
                <div class="col-products">
                    <table class="prod-table">
                        <tr><th>PRODOTTO</th><th style="width: 50px;">Q.</th></tr>
                        <?php foreach ($prodotti_per_ordine[$ordine['id_ordine']] ?? [] as $prod): ?>
                            <tr><td><?php echo htmlspecialchars($prod['nome_prodotto']); ?></td><td><?php echo $prod['quantita']; ?></td></tr>
                        <?php endforeach; ?>
                    </table>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </main>
</div>
</body>
</html>
