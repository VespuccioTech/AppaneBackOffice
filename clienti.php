<?php
require_once("config.php");

// 1. Recupero tutti gli utenti registrati unendo i dati di tregistrazione e tcliente
$sql_utenti = "
    SELECT c.nome, c.cognome, c.email, c.n_telefono, r.username_account AS username, r.data 
    FROM tregistrazione r
    JOIN tcliente c ON r.email_cliente = c.email
    ORDER BY r.data DESC
";
$utenti = $pdo->query($sql_utenti)->fetchAll();

// 2. Recupero tutti gli indirizzi di consegna
// Includiamo anche la colonna 'attivo' creata nel passaggio precedente
$sql_indirizzi = "SELECT username_account, via, n_civico, cap, citta, attivo FROM tindirizzo_di_consegna";
$tutti_indirizzi = $pdo->query($sql_indirizzi)->fetchAll();

// 3. Raggruppo gli indirizzi per username per stamparli facilmente nelle card
$indirizzi_per_utente = [];
foreach ($tutti_indirizzi as $ind) {
    $indirizzi_per_utente[$ind['username_account']][] = $ind;
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Clienti - Appane</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="dashboard-wrapper">
    <header class="main-header">
        <a href="index.php" class="logo-link"><img src="appane logo.jpg" alt="Logo Appane" style="height: 70px;"></a>
        <div class="nav-title">GESTIONE CLIENTI</div>
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
        <?php if (empty($utenti)): ?>
            <div style="text-align:center; padding: 50px; color: #8B4513; border: 2px dashed #D4A373;">Nessun cliente registrato al momento.</div>
        <?php else: ?>
            <div class="grid-layout">
                <?php foreach ($utenti as $u): ?>
                    <div class="card" style="border-top: 5px solid #5E3A8C;">
                        
                        <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
                            <span><?php echo htmlspecialchars($u['nome'] . ' ' . $u['cognome']); ?></span>
                            <span style="font-size: 0.8rem; background: #5E3A8C; color: white; padding: 3px 8px; border-radius: 12px;">@<?php echo htmlspecialchars($u['username']); ?></span>
                        </div>
                        
                        <div style="padding: 15px; font-size: 0.95rem; line-height: 1.8;">
                            <div><strong>Email:</strong> <a href="mailto:<?php echo htmlspecialchars($u['email']); ?>" style="color: #8B4513; text-decoration: none;"><?php echo htmlspecialchars($u['email']); ?></a></div>
                            <div><strong>Telefono:</strong> <?php echo htmlspecialchars($u['n_telefono'] ?: 'Non inserito'); ?></div>
                            <div><strong style="color: #666; font-size: 0.85rem;">Registrato il: <?php echo date('d/m/Y', strtotime($u['data'])); ?></strong></div>
                            
                            <hr style="border: 0; border-top: 1px dashed #D4A373; margin: 15px 0;">
                            
                            <strong style="color: #5E3A8C; font-size: 0.9rem; text-transform: uppercase;">Rubrica Indirizzi:</strong>
                            <div style="margin-top: 10px; max-height: 150px; overflow-y: auto; padding-right: 5px;">
                                <?php if (empty($indirizzi_per_utente[$u['username']])): ?>
                                    <p style="color: #888; font-size: 0.85rem; font-style: italic;">Nessun indirizzo in rubrica.</p>
                                <?php else: ?>
                                    <ul style="list-style-type: none; padding: 0; font-size: 0.85rem;">
                                        <?php foreach ($indirizzi_per_utente[$u['username']] as $ind): 
                                            // Se l'indirizzo è stato "eliminato" dall'utente, lo mostriamo sbarrato e con un badge
                                            $is_active = isset($ind['attivo']) ? $ind['attivo'] : 1; 
                                            $style = $is_active ? 'color: #4A3320;' : 'color: #aaa; text-decoration: line-through;';
                                            $badge = $is_active ? '' : '<span style="color: #D6604D; font-size: 0.7rem; margin-left: 5px; font-weight: bold;">(Rimosso dall\'utente)</span>';
                                        ?>
                                            <li style="background: #FFFAF4; padding: 8px; border: 1px solid #EED5B7; border-radius: 5px; margin-bottom: 5px; line-height: 1.4; <?php echo $style; ?>">
                                                <strong><?php echo htmlspecialchars($ind['via'] . ', ' . $ind['n_civico']); ?></strong><br>
                                                <?php echo htmlspecialchars($ind['cap'] . ' - ' . $ind['citta']); ?>
                                                <?php echo $badge; ?>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php endif; ?>
                            </div>

                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </main>
</div>
</body>
</html>