<?php
require_once 'auth_agriculteur.php';
require_once '../config.php';
$pageTitle  = "Ajouter une offre";
$pageScript = "offre-form.js";
require_once '../header.php';

$pdo = getDB();
$fruits = $pdo->query("SELECT * FROM type_fruit ORDER BY libelle")->fetchAll();
$gouvernorats = $pdo->query("SELECT * FROM gouvernorat ORDER BY libelle")->fetchAll();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_fruit   = intval($_POST['id_type_fruit'] ?? 0);
    $id_gouv    = intval($_POST['id_gouvernorat'] ?? 0);
    $adresse    = trim($_POST['adresse'] ?? '');
    $date_debut = $_POST['date_debut'] ?? '';
    $date_fin   = $_POST['date_fin'] ?? '';
    $nb_ouv     = intval($_POST['nombre_ouvriers'] ?? 0);
    $prix       = floatval($_POST['prix_journee'] ?? 0);
    $date_lim   = $_POST['date_limite'] ?? '';

    $stmt = $pdo->prepare("INSERT INTO offre (id_type_fruit,id_gouvernorat,adresse,date_debut,date_fin,nombre_ouvriers,prix_journee,date_limite,id_agriculteur) VALUES (?,?,?,?,?,?,?,?,?)");
    $stmt->execute([$id_fruit, $id_gouv, $adresse, $date_debut, $date_fin, $nb_ouv, $prix, $date_lim, $_SESSION['user']]);
    $success = "Offre publiée avec succès !";
}
?>

<div class="container">
  <div class="form-container" style="max-width:700px;">
    <h2 class="form-title">Nouvelle offre de récolte 🌾</h2>
    <p class="form-subtitle">Publiez une offre pour trouver des ouvriers</p>

    <?php if ($error): ?>
      <div class="alert alert-error"> <?= $error ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
      <div class="alert alert-success"> <?= $success ?> <a href="mes_offres.php">Voir mes offres</a></div>
    <?php endif; ?>

    <form method="POST" data-validate>
      <div class="form-row">
        <div class="form-group">
          <label>Type de fruit *</label>
          <select name="id_type_fruit" required>
            <option value="">-- Sélectionner --</option>
            <?php foreach ($fruits as $f): ?>
              <option value="<?= $f['id_type_fruit'] ?>" <?= ($_POST['id_type_fruit'] ?? '') == $f['id_type_fruit'] ? 'selected' : '' ?>>
                <?= $f['libelle'] ?>
              </option>
            <?php endforeach; ?>
          </select>
          <span class="error-msg"></span>
        </div>
        <div class="form-group">
          <label>Gouvernorat *</label>
          <select name="id_gouvernorat" required>
            <option value="">-- Sélectionner --</option>
            <?php foreach ($gouvernorats as $g): ?>
              <option value="<?= $g['id_gouvernorat'] ?>" <?= ($_POST['id_gouvernorat'] ?? '') == $g['id_gouvernorat'] ? 'selected' : '' ?>>
                <?= $g['libelle'] ?>
              </option>
            <?php endforeach; ?>
          </select>
          <span class="error-msg"></span>
        </div>
      </div>

      <div class="form-group">
        <label>Adresse du site de récolte *</label>
        <input type="text" name="adresse" value="<?= $_POST['adresse'] ?? '' ?>" required placeholder="Route de Sfax, Km 12...">
        <span class="error-msg"></span>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label>Date de début *</label>
          <input type="date" name="date_debut" value="<?= $_POST['date_debut'] ?? '' ?>" required>
          <span class="error-msg"></span>
        </div>
        <div class="form-group">
          <label>Date de fin *</label>
          <input type="date" name="date_fin" value="<?= $_POST['date_fin'] ?? '' ?>" required>
          <span class="error-msg"></span>
        </div>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label>Nombre d'ouvriers *</label>
          <input type="number" name="nombre_ouvriers" value="<?= $_POST['nombre_ouvriers'] ?? '' ?>" required min="1" placeholder="10">
          <span class="error-msg"></span>
        </div>
        <div class="form-group">
          <label>Prix à la journée (DT) *</label>
          <input type="number" name="prix_journee" step="0.5" value="<?= $_POST['prix_journee'] ?? '' ?>" required min="0.1" placeholder="25.00">
          <span class="error-msg"></span>
        </div>
      </div>

      <div class="form-group">
        <label>Date limite pour postuler *</label>
        <input type="date" name="date_limite" value="<?= $_POST['date_limite'] ?? ''?>" required>
        <span class="error-msg"></span>
      </div>

      <button type="submit" class="btn btn-primary btn-full">Publier l'offre</button>
    </form>
  </div>
</div>

<?php require_once '../footer.php'; ?>