<?php
require_once 'auth_agriculteur.php';
require_once '../config.php';
$pageTitle  = "Mon profil";
$pageScript = "profil.js";
require_once '../header.php';

$pdo = getDB();
$id = $_SESSION['user'];

$msg = ''; $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom     = trim($_POST['nom'] ?? '');
    $prenom  = trim($_POST['prenom'] ?? '');
    $email   = trim($_POST['email'] ?? '');
    $adresse = trim($_POST['adresse'] ?? '');
    $pwd     = trim($_POST['password'] ?? '');

    if ($pwd) {
        $hash = password_hash($pwd, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE agriculteur SET nom=?,prenom=?,email=?,adresse=?,password=? WHERE id_agriculteur=?");
        $stmt->execute([$nom, $prenom, $email, $adresse, $hash, $id]);
    } else {
        $stmt = $pdo->prepare("UPDATE agriculteur SET nom=?,prenom=?,email=?,adresse=? WHERE id_agriculteur=?");
        $stmt->execute([$nom, $prenom, $email, $adresse, $id]);
    }
    $_SESSION['nom'] = $nom;
    $_SESSION['prenom'] = $prenom;
    $msg = "Profil mis à jour avec succès.";
}

$stmt = $pdo->prepare("SELECT * FROM agriculteur WHERE id_agriculteur=?");
$stmt->execute([$id]);
$agri = $stmt->fetch();
?>

<div class="container" style="max-width:700px;">
  <div class="profile-header">
    <div class="profile-avatar">
      <?= strtoupper(substr($agri['prenom'],0,1) . substr($agri['nom'],0,1)) ?>
    </div>
    <div>
      <div class="profile-name"><?= $agri['prenom'] . ' ' . $agri['nom'] ?></div>
      <div class="profile-sub"> Agriculteur · @<?= $agri['pseudo'] ?></div>
      <div class="profile-sub">📧 <?= $agri['email'] ?></div>
    </div>
  </div>

  <?php if ($msg): ?><div class="alert alert-success"> <?= $msg ?></div><?php endif; ?>
  <?php if ($error): ?><div class="alert alert-error"> <?= $error ?></div><?php endif; ?>

  <div class="card">
    <h3 style="font-family:'Playfair Display',serif;color:var(--olive-dark);margin-bottom:1.5rem;">Modifier mon profil</h3>
    <form method="POST" data-validate>
      <div class="form-row">
        <div class="form-group">
          <label>Nom</label>
          <input type="text" name="nom" value="<?= $agri['nom'] ?>" required>
          <span class="error-msg"></span>
        </div>
        <div class="form-group">
          <label>Prénom</label>
          <input type="text" name="prenom" value="<?= $agri['prenom'] ?>" required>
          <span class="error-msg"></span>
        </div>
      </div>
      <div class="form-group">
        <label>Email</label>
        <input type="email" name="email" value="<?= $agri['email'] ?>" required>
        <span class="error-msg"></span>
      </div>
      <div class="form-group">
        <label>Adresse</label>
        <input type="text" name="adresse" value="<?= $agri['adresse'] ?? '' ?>">
        <span class="error-msg"></span>
      </div>
      <div class="form-group">
        <label>Nouveau mot de passe <span style="color:var(--text-light);font-weight:400;">(laisser vide pour ne pas changer)</span></label>
        <input type="password" name="password" placeholder="Nouveau mot de passe (finit par $ ou #)">
        <span class="error-msg"></span>
      </div>
      <button type="submit" class="btn btn-primary">Enregistrer les modifications</button>
    </form>
  </div>

  <div class="card" style="margin-top:1.5rem;background:var(--cream);">
    <h4 style="color:var(--text-light);font-size:0.85rem;text-transform:uppercase;letter-spacing:0.05em;margin-bottom:0.8rem;">Informations non modifiables</h4>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
      <div><span style="color:var(--text-light);font-size:0.82rem;">CIN</span><br><strong><?= $agri['CIN'] ?></strong></div>
      <div><span style="color:var(--text-light);font-size:0.82rem;">Pseudo</span><br><strong>@<?= $agri['pseudo'] ?></strong></div>
    </div>
  </div>
</div>

<?php require_once '../footer.php'; ?>