<?php
require_once("config.php");

// 1. Recupero tutti gli ordini con i dati del cliente
$sql = "SELECT o.id_ordine, o.data, o.importo, o.stato, c.nome, c.cognome 
        FROM tordine o 
        JOIN tregistrazione r ON o.username_account = r.username_account 
        JOIN tcliente c ON r.email_cliente = c.email 
        ORDER BY o.data DESC";
$ordini_raw = $pdo->query($sql)->fetchAll();

// 2. Elaborazione dati: Raggruppo per Mese/Anno e calcolo il totale
$riepilogo = [];
$mesi_ita = [
    1 => 'Gennaio', 2 => 'Febbraio', 3 => 'Marzo', 4 => 'Aprile', 
    5 => 'Maggio', 6 => 'Giugno', 7 => 'Luglio', 8 => 'Agosto', 
    9 => 'Settembre', 10 => 'Ottobre', 11 => 'Novembre', 12 => 'Dicembre'
];

foreach ($ordini_raw as $ordine) {
    $timestamp = strtotime($ordine['data']);
    $mese_num = (int)date('n', $timestamp);
    $anno = date('Y', $timestamp);
    $mese_anno = $mesi_ita[$mese_num] . " " . $anno;

    // Se il mese non esiste ancora nell'array, lo inizializzo
    if (!isset($riepilogo[$mese_anno])) {
        $riepilogo[$mese_anno] = [
            'ordini' => [],
            'totale' => 0
        ];
    }

    // Aggiungo l'ordine alla lista del mese
    $riepilogo[$mese_anno]['ordini'][] = $ordine;

    // Sommo l'importo al totale del mese SOLO se l'ordine NON è 'Annullato'
    if ($ordine['stato'] !== 'Annullato') {
        $riepilogo[$mese_anno]['totale'] += $ordine['importo'];
    }
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Riepilogo Incassi - Appane</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="dashboard-wrapper">
    <header class="main-header">
        <a href="index.php" class="logo-link"><img src="appane logo.jpg" alt="Logo Appane" style="height: 70px;"></a>
        <div class="nav-title">RIEPILOGO INCASSI MENSILE</div>
        <div class="header-nav-group">
            <a href="ordini.php" style="color: white; font-weight: bold;">VISUALIZZAZIONE ORDINI</a>
            <a href="ingredienti.php" style="color: #E9D5FF; font-weight: bold; font-size: 0.85rem;">LISTA INGREDIENTI</a>
            <a href="clienti.php" style="color: #E9D5FF; font-weight: bold; font-size: 0.85rem;">LISTA CLIENTI</a>
            <a href="riepiloghi.php" style="color: #FFD700; font-weight: bold; font-size: 0.85rem;">RIEPILOGO INCASSI</a>
        </div>
    </header>

    <nav class="sub-nav">
        <div><a href="index.php" style="color: #5E3A8C; font-weight: bold; text-decoration: none;">← Torna alla Dashboard</a></div>
    </nav>

    <main class="content-area">
        <?php if (empty($riepilogo)): ?>
            <div style="text-align:center; padding: 50px; color: #8B4513; border: 2px dashed #D4A373;">Nessun ordine registrato al momento.</div>
        <?php else: ?>
            <div style="max-width: 1000px; margin: 0 auto;">
                <?php foreach ($riepilogo as $mese => $dati): ?>
                    <div class="card" style="margin-bottom: 40px; border-radius: 10px; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.05);">
                        
                        <div style="background: #8B4513; color: white; padding: 15px 20px; font-size: 1.2rem; font-weight: bold; display: flex; justify-content: space-between; align-items: center;">
                            <span>🗓️ <?php echo $mese; ?></span>
                            <span style="font-size: 0.9rem; background: rgba(255,255,255,0.2); padding: 5px 10px; border-radius: 20px;">
                                <?php echo count($dati['ordini']); ?> ordini
                            </span>
                        </div>

                        <div style="max-height: 400px; overflow-y: auto;">
                            <table class="prod-table" style="width: 100%; border-collapse: collapse;">
                                <thead style="position: sticky; top: 0; background: #5E3A8C; color: white;">
                                    <tr>
                                        <th style="padding: 12px 10px; text-align: center; width: 10%;">ID Ordine</th>
                                        <th style="padding: 12px 10px; text-align: center; width: 25%;">Data</th>
                                        <th style="padding: 12px 10px; text-align: left; width: 30%;">Cliente</th>
                                        <th style="padding: 12px 10px; text-align: center; width: 20%;">Stato</th>
                                        <th style="padding: 12px 10px; text-align: right; width: 15%;">Importo</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($dati['ordini'] as $ord): 
                                        // Stile per gli ordini annullati
                                        $is_annullato = ($ord['stato'] === 'Annullato');
                                        $row_style = $is_annullato ? 'background: #f8f9fa; color: #aaa; text-decoration: line-through;' : '';
                                        $stato_color = $is_annullato ? '#aaa' : '#5E3A8C';
                                    ?>
                                        <tr style="border-bottom: 1px solid #EED5B7; <?php echo $row_style; ?>">
                                            <td style="padding: 12px 10px; text-align: center; font-weight: bold;">#<?php echo $ord['id_ordine']; ?></td>
                                            <td style="padding: 12px 10px; text-align: center; font-size: 0.9rem;"><?php echo date('d/m/Y H:i', strtotime($ord['data'])); ?></td>
                                            <td style="padding: 12px 10px; text-align: left;"><?php echo htmlspecialchars($ord['nome'] . ' ' . $ord['cognome']); ?></td>
                                            <td style="padding: 12px 10px; text-align: center; font-weight: bold; color: <?php echo $stato_color; ?>; font-size: 0.85rem; text-transform: uppercase;">
                                                <?php echo htmlspecialchars($ord['stato']); ?>
                                            </td>
                                            <td style="padding: 12px 10px; text-align: right; font-weight: bold;">
                                                €<?php echo number_format($ord['importo'], 2); ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <div style="background: #FFF8E7; padding: 20px; text-align: right; border-top: 3px solid #D4A373;">
                            <span style="font-size: 1.1rem; color: #8B4513; text-transform: uppercase; margin-right: 15px; font-weight: bold;">Totale Mese (esclusi annullati):</span>
                            <span style="font-size: 1.8rem; font-weight: bold; color: #5E3A8C;">
                                €<?php echo number_format($dati['totale'], 2); ?>
                            </span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </main>
</div>
</body>
</html>