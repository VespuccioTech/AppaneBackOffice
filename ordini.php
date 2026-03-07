<?php
require_once("config.php");

$messaggio = '';

// Gestione aggiornamento stato
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['aggiorna_stato'])) {
    $id_ordine = $_POST['id_ordine'];
    $nuovo_stato = $_POST['stato_ordine'];
    
    try {
        $stmt_update = $pdo->prepare("UPDATE tordine SET stato = ? WHERE id_ordine = ?");
        $stmt_update->execute([$nuovo_stato, $id_ordine]);
        $messaggio = "Stato dell'ordine #$id_ordine aggiornato con successo!";
    } catch (\PDOException $e) {
        $messaggio = "Errore durante l'aggiornamento: " . $e->getMessage();
    }
}

// Lettura variabili dinamiche giorni
$giorni_settimana = ['Lunedì', 'Martedì', 'Mercoledì', 'Giovedì', 'Venerdì', 'Sabato', 'Domenica'];
$giorno_ripub_attuale = 'Mercoledì'; $giorno_fine_attuale = 'Venerdì';

try {
    $menu_attuale = $pdo->query("SELECT giorno_ripubblicazione, giorno_fine_ordinazioni FROM tmenu_settimanale ORDER BY id_menu DESC LIMIT 1")->fetch();
    if ($menu_attuale) {
        if(!empty($menu_attuale['giorno_ripubblicazione'])) $giorno_ripub_attuale = $menu_attuale['giorno_ripubblicazione'];
        if(!empty($menu_attuale['giorno_fine_ordinazioni'])) $giorno_fine_attuale = $menu_attuale['giorno_fine_ordinazioni'];
    }
} catch (\PDOException $e) {}

// Query aggiornata: richiede 'stato' al posto di 'consegna_effettuata'
$ordini = $pdo->query("SELECT o.id_ordine, o.data, o.stato, i.citta, i.via, i.n_civico, c.nome, c.cognome, c.n_telefono FROM tordine o JOIN tindirizzo_di_consegna i ON o.id_indirizzo = i.id_indirizzo JOIN tregistrazione r ON o.username_account = r.username_account JOIN tcliente c ON r.email_cliente = c.email ORDER BY o.data DESC")->fetchAll();

$tutti_prodotti = $pdo->query("SELECT id_ordine, nome_prodotto, quantita FROM tselezione")->fetchAll();
$prodotti_per_ordine = []; 
foreach ($tutti_prodotti as $p) { 
    $prodotti_per_ordine[$p['id_ordine']][] = $p;
}

// Array con i possibili stati dell'ordine
$stati_disponibili = ['In attesa', 'In preparazione', 'In fase di consegna', 'Consegnato', 'Annullato'];
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
        <?php if($messaggio): ?>
            <div class='alert alert-success'><?php echo htmlspecialchars($messaggio); ?></div>
        <?php endif; ?>

        <?php if (empty($ordini)): ?>
            <div style="text-align:center; padding: 50px; color: #8B4513; border: 2px dashed #D4A373;">Nessun ordine presente nel database.</div>
        <?php else: ?>
            <?php foreach ($ordini as $ordine): 
                $nome = $ordine['nome'] ?? '-'; 
                $cognome = $ordine['cognome'] ?? '-';
                $stato_corrente = $ordine['stato'] ?? 'In attesa';
                
                // Colori dinamici per lo stato
                $colore_stato = '#4A3320'; // Default
                if($stato_corrente == 'Consegnato') $colore_stato = '#79B473'; // Verde
                if($stato_corrente == 'In attesa') $colore_stato = '#D6604D'; // Rosso/Arancio
                if($stato_corrente == 'In preparazione') $colore_stato = '#F4A261'; // Arancione
                if($stato_corrente == 'In fase di consegna') $colore_stato = '#2A9D8F'; // Ottanio
                if($stato_corrente == 'Annullato') $colore_stato = '#888888'; // Grigio
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
                    <div class="split-row">
                        <div class="box"><span class="label">Telefono</span><span class="value"><?php echo htmlspecialchars($ordine['n_telefono']??'-'); ?></span></div>
                        <div class="box"><span class="label">Data Ordine</span><span class="value"><?php echo date('d/m/y H:i', strtotime($ordine['data'])); ?></span></div>
                    </div>
                    
                    <div class="box" style="background:#FFFAF4; flex-direction: row; gap: 15px;">
                        <span class="label" style="margin-bottom: 0; line-height: 35px;">Stato:</span>
                        <form method="POST" style="display: flex; gap: 10px; margin: 0;">
                            <input type="hidden" name="id_ordine" value="<?php echo $ordine['id_ordine']; ?>">
                            <select name="stato_ordine" class="form-control" style="margin-bottom: 0; padding: 5px; font-weight: bold; color: <?php echo $colore_stato; ?>;">
                                <?php foreach($stati_disponibili as $s): ?>
                                    <option value="<?php echo $s; ?>" <?php if($s == $stato_corrente) echo 'selected'; ?>><?php echo $s; ?></option>
                                <?php endforeach; ?>
                            </select>
                            <button type="submit" name="aggiorna_stato" class="btn btn-purple" style="padding: 5px 15px;">Aggiorna</button>
                        </form>
                    </div>
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
