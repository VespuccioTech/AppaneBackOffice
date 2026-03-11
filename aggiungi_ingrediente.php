<?php
require_once("config.php");

$messaggio = $errore = '';
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nome = trim($_POST['nome_ingrediente'] ?? '');
    $tipo = trim($_POST['tipo_ingrediente'] ?? '');
    $descrizione = trim($_POST['descrizione'] ?? '');
    
    if (empty($nome)) { $errore = "Il nome dell'ingrediente è obbligatorio."; } 
    else {
        try {
            $pdo->beginTransaction();
            $stmt = $pdo->prepare("INSERT INTO tingrediente (nome, tipo, descrizione) VALUES (?, ?, ?)");
            $stmt->execute([$nome, $tipo, $descrizione]);

            // Salvataggio Immagini
            if (!empty($_FILES['immagini_ingrediente']['name'][0])) {
                $upload_dir = 'uploads/ingredienti/';
                if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
                
                $stmt_img = $pdo->prepare("INSERT INTO timmagine_ingrediente (nome_ingrediente, percorso_file) VALUES (?, ?)");
                for($i = 0; $i < count($_FILES['immagini_ingrediente']['name']); $i++){
                    $nome_file = uniqid() . "_" . basename($_FILES['immagini_ingrediente']['name'][$i]);
                    $destinazione = $upload_dir . $nome_file;
                    if(move_uploaded_file($_FILES['immagini_ingrediente']['tmp_name'][$i], $destinazione)){
                        $stmt_img->execute([$nome, $destinazione]);
                    }
                }
            }
            $pdo->commit();
            $messaggio = "Ingrediente '$nome' aggiunto con successo!";
        } catch (\PDOException $e) { 
            $pdo->rollBack();
            $errore = ($e->getCode() == 23000) ? "L'ingrediente esiste già." : "Errore: " . $e->getMessage(); 
        }
    }
}

$giorni_settimana = ['Lunedì', 'Martedì', 'Mercoledì', 'Giovedì', 'Venerdì', 'Sabato', 'Domenica'];
$giorno_ripub_attuale = 'Giovedì'; $giorno_fine_attuale = 'Venerdì';
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
<head><meta charset="UTF-8"><title>Aggiungi Ingrediente</title><link rel="stylesheet" href="style.css"></head>
<body>
<div class="dashboard-wrapper">
    <header class="main-header">
        <a href="index.php" class="logo-link"><img src="appane logo.jpg" alt="Logo Appane" style="height: 70px;"></a>
        <div class="nav-title">AGGIUNGI INGREDIENTE</div>
        <div class="header-nav-group">
            <a href="ordini.php" style="color: white; font-weight: bold;">VISUALIZZAZIONE ORDINI</a>
            <a href="ingredienti.php" style="color: #E9D5FF; font-weight: bold; font-size: 0.85rem;">LISTA INGREDIENTI</a>
            <a href="clienti.php" style="color: #E9D5FF; font-weight: bold; font-size: 0.85rem;">LISTA CLIENTI</a>
            <a href="riepiloghi.php" style="color: #FFD700; font-weight: bold; font-size: 0.85rem;">RIEPILOGO INCASSI</a>
        </div>
    </header>

    <nav class="sub-nav">
        <div><a href="ingredienti.php" style="color: #5E3A8C; font-weight: bold; text-decoration: none;">← Torna alla Lista</a></div>
        <div><label>Ripubblicazione: </label><select disabled><?php foreach($giorni_settimana as $g) echo "<option" . ($g == $giorno_ripub_attuale ? " selected" : "") . ">$g</option>"; ?></select></div>
        <div><label>Fine ordinazioni: </label><select disabled><?php foreach($giorni_settimana as $g) echo "<option" . ($g == $giorno_fine_attuale ? " selected" : "") . ">$g</option>"; ?></select></div>
    </nav>

    <main class="content-area">
        <div class="form-container">
            <?php if($messaggio) echo "<div class='alert alert-success'>$messaggio</div>"; ?>
            <?php if($errore) echo "<div class='alert alert-error'>$errore</div>"; ?>
            <form action="" method="POST" enctype="multipart/form-data">
                <div style="background: #FFF8E7; border-left: 4px solid #F4A261; padding: 15px; margin-bottom: 20px; border-radius: 4px; font-size: 0.9rem; color: #4A3320;">
                    <strong>💡 Come inserire le varianti (es. Farine):</strong> Il nome dell'ingrediente deve essere unico. Se aggiungi più tipi di uno stesso prodotto, specificalo direttamente nel nome. <br>
                    <em>✅ Corretto: "Farina Tipo 0", "Farina Integrale".<br>
                    ❌ Sbagliato: Chiamarle tutte "Farina".</em>
                </div>

                <div class="form-row">
                    <div class="form-col" style="align-items: center;">
                        <label class="form-label">Nome Ingrediente (Univoco)</label>
                        <input type="text" name="nome_ingrediente" class="form-control" style="width: 60%; text-align: center;" placeholder="Es. Farina Tipo 0, Farina integrale, Sale, Lievito..." required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-col">
                        <label class="form-label">Categoria / Tipo (Opzionale)</label>
                        <input type="text" name="tipo_ingrediente" class="form-control" placeholder="Es. Tipo 0, Integrale...">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-col">
                        <label class="form-label">Descrizione</label>
                        <textarea name="descrizione" class="form-control" rows="4"></textarea>
                    </div>
                </div>
                
                <div style="display: flex; justify-content: flex-end; margin-top: 20px;">
                    <button type="submit" class="btn btn-purple">Salva Ingrediente</button>
                </div>
            </form>
        </div>
    </main>
</div>
</body>
</html>