<?php
require_once("config.php");

$messaggio = '';
$errore = '';

// 1. Controllo se è stato passato il nome del prodotto
if (!isset($_GET['nome']) || empty($_GET['nome'])) {
    die("<div style='text-align:center; margin-top:50px;'>Errore: Nessun prodotto selezionato. <a href='index.php'>Torna alla dashboard</a></div>");
}

$nome_prodotto = $_GET['nome'];

// 2. Gestione del salvataggio delle modifiche (Metodo POST)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['salva_modifiche'])) {
    $nuovo_prezzo = str_replace(',', '.', $_POST['prezzo']); // Accetta sia virgola che punto
    $nuova_descrizione = trim($_POST['descrizione']);
    $nuovi_ingredienti = $_POST['ingredienti'] ?? []; // Array degli ingredienti selezionati

    try {
        $pdo->beginTransaction();

        // Aggiorniamo i dati base del prodotto
        $stmt_update = $pdo->prepare("UPDATE tprodotto SET prezzo = ?, descrizione = ? WHERE nome = ?");
        $stmt_update->execute([$nuovo_prezzo, $nuova_descrizione, $nome_prodotto]);

        // Aggiorniamo la composizione (cancelliamo i vecchi e inseriamo i nuovi)
        $stmt_delete_comp = $pdo->prepare("DELETE FROM tcomposizione WHERE nome_prodotto = ?");
        $stmt_delete_comp->execute([$nome_prodotto]);

        if (!empty($nuovi_ingredienti)) {
            $stmt_insert_comp = $pdo->prepare("INSERT INTO tcomposizione (nome_prodotto, nome_ingrediente) VALUES (?, ?)");
            foreach ($nuovi_ingredienti as $ingrediente) {
                $stmt_insert_comp->execute([$nome_prodotto, $ingrediente]);
            }
        }

        // --- INIZIO GESTIONE IMMAGINE ---
        if (isset($_FILES['nuova_immagine']) && $_FILES['nuova_immagine']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = 'uploads/prodotti/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);

            $nome_file = uniqid() . "_" . basename($_FILES['nuova_immagine']['name']);
            $destinazione = $upload_dir . $nome_file;

            if (move_uploaded_file($_FILES['nuova_immagine']['tmp_name'], $destinazione)) {
                // Recuperiamo la vecchia immagine per eliminarla fisicamente dal server
                $stmt_old_img = $pdo->prepare("SELECT percorso_file FROM timmagine_prodotto WHERE nome_prodotto = ?");
                $stmt_old_img->execute([$nome_prodotto]);
                $old_images = $stmt_old_img->fetchAll(PDO::FETCH_COLUMN);
                
                foreach ($old_images as $old_img) {
                    if (file_exists($old_img)) {
                        unlink($old_img); // Elimina il file fisico
                    }
                }

                // Eliminiamo i vecchi record dal database
                $stmt_del_img = $pdo->prepare("DELETE FROM timmagine_prodotto WHERE nome_prodotto = ?");
                $stmt_del_img->execute([$nome_prodotto]);

                // Inseriamo la nuova immagine
                $stmt_ins_img = $pdo->prepare("INSERT INTO timmagine_prodotto (nome_prodotto, percorso_file) VALUES (?, ?)");
                $stmt_ins_img->execute([$nome_prodotto, $destinazione]);
            }
        }
        // --- FINE GESTIONE IMMAGINE ---

        $pdo->commit();
        $messaggio = "Prodotto aggiornato con successo!";
    } catch (\PDOException $e) {
        $pdo->rollBack();
        $errore = "Errore durante l'aggiornamento: " . $e->getMessage();
    }
}

// 3. Recupero i dati attuali del prodotto per precompilare il form
$stmt_prod = $pdo->prepare("SELECT * FROM tprodotto WHERE nome = ?");
$stmt_prod->execute([$nome_prodotto]);
$prodotto_attuale = $stmt_prod->fetch();

if (!$prodotto_attuale) {
    die("<div style='text-align:center; margin-top:50px;'>Errore: Prodotto non trovato. <a href='index.php'>Torna alla dashboard</a></div>");
}

// Recupero gli ingredienti attuali (per spuntare le checkbox)
$stmt_comp = $pdo->prepare("SELECT nome_ingrediente FROM tcomposizione WHERE nome_prodotto = ?");
$stmt_comp->execute([$nome_prodotto]);
$ingredienti_attuali = $stmt_comp->fetchAll(PDO::FETCH_COLUMN);

// Recupero l'immagine attuale del prodotto
$stmt_img = $pdo->prepare("SELECT percorso_file FROM timmagine_prodotto WHERE nome_prodotto = ? LIMIT 1");
$stmt_img->execute([$nome_prodotto]);
$immagine_attuale = $stmt_img->fetchColumn();

// Recupero TUTTI gli ingredienti disponibili per mostrare la lista
$tutti_ingredienti = $pdo->query("SELECT nome FROM tingrediente ORDER BY nome ASC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Modifica Prodotto - Appane</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="dashboard-wrapper">
    <header class="main-header">
    <a href="index.php" class="logo-link"><img src="appane logo.jpg" alt="Logo Appane" style="height: 70px; width: auto;"></a>
    <div class="nav-title">MODIFICA PRODOTTO</div>
    <div class="header-nav-group">
        <a href="ordini.php" style="color: white; font-weight: bold;">VISUALIZZAZIONE ORDINI</a>
        <a href="ingredienti.php" style="color: #E9D5FF; font-weight: bold; font-size: 0.85rem;">LISTA INGREDIENTI</a>
        <a href="clienti.php" style="color: #E9D5FF; font-weight: bold; font-size: 0.85rem;">LISTA CLIENTI</a>
    </div>
</header>

    <nav class="sub-nav">
        <div><a href="index.php" style="color: #5E3A8C; font-weight: bold; text-decoration: none;">← Torna alla Dashboard</a></div>
    </nav>

    <main class="content-area">
        <div class="form-container">
            <h2 style="color: #8B4513; text-align: center; margin-bottom: 20px;">Modifica: <?php echo htmlspecialchars($prodotto_attuale['nome']); ?></h2>
            
            <?php if ($messaggio): ?>
                <div style="background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin-bottom: 20px; text-align: center; border: 1px solid #c3e6cb;">
                    <?php echo $messaggio; ?>
                </div>
            <?php endif; ?>
            
            <?php if ($errore): ?>
                <div style="background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin-bottom: 20px; text-align: center; border: 1px solid #f5c6cb;">
                    <?php echo $errore; ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="" enctype="multipart/form-data">
                <div class="form-row">
                    <div class="form-col" style="text-align: center; background: #FFFAF4; padding: 15px; border-radius: 8px; border: 1px dashed #D4A373;">
                        <label class="form-label">Immagine Attuale</label>
                        <?php if ($immagine_attuale): ?>
                            <img src="<?php echo htmlspecialchars($immagine_attuale); ?>" alt="Immagine prodotto" style="max-width: 200px; height: auto; border-radius: 8px; border: 2px solid #D4A373; margin-bottom: 10px;">
                        <?php else: ?>
                            <p style="color: #888; font-size: 0.9rem; margin-bottom: 10px;">Nessuna immagine presente.</p>
                        <?php endif; ?>
                        
                        <label class="form-label" style="margin-top: 10px;">Sostituisci Immagine (lascia vuoto per non modificare)</label>
                        <input type="file" name="nuova_immagine" accept="image/*" class="form-control" style="padding: 9px; width: 80%; margin: 0 auto;">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-col">
                        <label class="form-label">Nome Prodotto (Non modificabile)</label>
                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($prodotto_attuale['nome']); ?>" readonly style="background-color: #f8f9fa; color: #6c757d; cursor: not-allowed;">
                    </div>
                    <div class="form-col">
                        <label class="form-label">Prezzo (€)</label>
                        <input type="number" step="0.01" name="prezzo" class="form-control" value="<?php echo htmlspecialchars($prodotto_attuale['prezzo']); ?>" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-col">
                        <label class="form-label">Descrizione</label>
                        <textarea name="descrizione" class="form-control" rows="4" required><?php echo htmlspecialchars($prodotto_attuale['descrizione']); ?></textarea>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-col">
                        <label class="form-label">Ingredienti (Seleziona per modificare la composizione)</label>
                        <div style="border: 1px solid #D4A373; padding: 15px; border-radius: 5px; max-height: 200px; overflow-y: auto; background: #FFFAF4;">
                            <?php if (empty($tutti_ingredienti)): ?>
                                <em>Nessun ingrediente nel database. Aggiungili prima dall'apposita sezione.</em>
                            <?php else: ?>
                                <?php foreach ($tutti_ingredienti as $ing): 
                                    $checked = in_array($ing['nome'], $ingredienti_attuali) ? 'checked' : '';
                                ?>
                                    <label style="display: block; margin-bottom: 8px; cursor: pointer;">
                                        <input type="checkbox" name="ingredienti[]" value="<?php echo htmlspecialchars($ing['nome']); ?>" <?php echo $checked; ?>>
                                        <?php echo htmlspecialchars($ing['nome']); ?>
                                    </label>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div style="text-align: center; margin-top: 30px;">
                    <button type="submit" name="salva_modifiche" class="btn btn-purple" style="font-size: 1.1rem; padding: 10px 30px;">💾 Salva Modifiche</button>
                </div>
            </form>
        </div>
    </main>
</div>
</body>
</html>