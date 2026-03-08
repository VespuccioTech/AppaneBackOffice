<?php
require_once("config.php");

$messaggio = $errore = '';
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nome = trim($_POST['nome_prodotto'] ?? '');
    $tipo = trim($_POST['tipo'] ?? 'Pane'); 
    $prezzo = (float)($_POST['prezzo'] ?? 0);
    $descrizione = trim($_POST['descrizione'] ?? '');
    $ingredienti_selezionati = $_POST['ingredienti'] ?? [];

    if (empty($nome) || $prezzo <= 0) {
        $errore = "Nome e prezzo validi sono obbligatori.";
    } else {
        try {
            $pdo->beginTransaction();
            $stmt = $pdo->prepare("INSERT INTO tprodotto (nome, tipo, prezzo, descrizione) VALUES (?, ?, ?, ?)");
            $stmt->execute([$nome, $tipo, $prezzo, $descrizione]);
            
            if (!empty($ingredienti_selezionati)) {
                $stmt_comp = $pdo->prepare("INSERT INTO tcomposizione (nome_prodotto, nome_ingrediente) VALUES (?, ?)");
                foreach ($ingredienti_selezionati as $ing) { $stmt_comp->execute([$nome, $ing]); }
            }

            // Salvataggio Immagini Multiple
            if (!empty($_FILES['immagini_prodotto']['name'][0])) {
                $upload_dir = 'uploads/prodotti/';
                if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
                
                $stmt_img = $pdo->prepare("INSERT INTO timmagine_prodotto (nome_prodotto, percorso_file) VALUES (?, ?)");
                for($i = 0; $i < count($_FILES['immagini_prodotto']['name']); $i++){
                    $nome_file = uniqid() . "_" . basename($_FILES['immagini_prodotto']['name'][$i]);
                    $destinazione = $upload_dir . $nome_file;
                    if(move_uploaded_file($_FILES['immagini_prodotto']['tmp_name'][$i], $destinazione)){
                        $stmt_img->execute([$nome, $destinazione]);
                    }
                }
            }

            $pdo->commit();
            $messaggio = "Prodotto '$nome' inserito con successo!";
        } catch (\PDOException $e) {
            $pdo->rollBack();
            $errore = ($e->getCode() == 23000) ? "Il prodotto esiste già." : "Errore: " . $e->getMessage();
        }
    }
}
$lista_ingredienti = $pdo->query("SELECT nome FROM tingrediente ORDER BY nome")->fetchAll();
$giorni_settimana = ['Lunedì', 'Martedì', 'Mercoledì', 'Giovedì', 'Venerdì', 'Sabato', 'Domenica'];
$giorno_ripub_attuale = 'Mercoledì'; $giorno_fine_attuale = 'Venerdì';
try {
    $menu_attuale = $pdo->query("SELECT giorno_ripubblicazione, giorno_fine_ordinazioni FROM tmenu_settimanale ORDER BY id_menu DESC LIMIT 1")->fetch();
    if ($menu_attuale) {
        if(!empty($menu_attuale['giorno_ripubblicazione'])) $giorno_ripub_attuale = $menu_attuale['giorno_ripubblicazione'];
        if(!empty($menu_attuale['giorno_fine_ordinazioni'])) $giorno_fine_attuale = $menu_attuale['giorno_fine_ordinazioni'];
    }
} catch (\PDOException $e) {}
?>
<!DOCTYPE html>
<html lang="it">
<head><meta charset="UTF-8"><title>Aggiungi Prodotto</title><link rel="stylesheet" href="style.css"></head>
<body>
<div class="dashboard-wrapper">
    <header class="main-header">
        <a href="index.php" class="logo-link"><img src="appane logo.jpg" alt="Logo Appane" style="height: 70px;"></a>
        <div class="nav-title">AGGIUNGI NUOVO PRODOTTO</div>
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
        <div class="form-container">
            <?php if($messaggio) echo "<div class='alert alert-success'>$messaggio</div>"; ?>
            <?php if($errore) echo "<div class='alert alert-error'>$errore</div>"; ?>
            
            <form action="" method="POST" enctype="multipart/form-data">
                <div class="form-row"><div class="form-col" style="align-items: center;"><label class="form-label">Nome Prodotto</label><input type="text" name="nome_prodotto" class="form-control" style="width: 60%; text-align: center;" required></div></div>
                <div class="form-row">
                    <div class="form-col">
                        <label class="form-label">Inserimento Immagini</label>
                        <input type="file" name="immagini_prodotto[]" multiple class="form-control" style="padding: 9px; margin-bottom: 15px;" accept="image/*">
                        <label class="form-label">Prezzo (€)</label>
                        <input type="number" step="0.01" name="prezzo" class="form-control" placeholder="es. 3.50" required>
                    </div>
                    <div class="form-col">
                        <label class="form-label">Seleziona Ingredienti (Composizione)</label>
                        <div class="checkbox-list">
                            <?php if(empty($lista_ingredienti)): ?><p style="color:#aaa;">Nessun ingrediente in database.</p>
                            <?php else: foreach($lista_ingredienti as $ing): ?>
                                <label><input type="checkbox" name="ingredienti[]" value="<?php echo htmlspecialchars($ing['nome']); ?>"> <?php echo htmlspecialchars($ing['nome']); ?></label>
                            <?php endforeach; endif; ?>
                        </div>
                    </div>
                </div>
                <div class="form-row"><div class="form-col"><label class="form-label">Descrizione</label><textarea name="descrizione" class="form-control" rows="4"></textarea></div></div>
                <div style="display: flex; justify-content: flex-end; margin-top: 20px;"><button type="submit" class="btn btn-purple">Salva Prodotto</button></div>
            </form>
        </div>
    </main>
</div>
</body>
</html>