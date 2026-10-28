<?php
require_once 'auth_ouvrier.php';
require_once '../config.php';
$pageTitle  = "Mon profil";
$pageScript = "profil.js";
require_once '../header.php';

$pdo = getDB();
$id = $_SESSION['user'];

$msg = ''; $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom        = trim($_POST['nom'] ?? '');
    $prenom     = trim($_POST['prenom'] ?? '');
    $email      = trim($_POST['email'] ?? '');
    $description= trim($_POST['description'] ?? '');
    $pwd        = trim($_POST['password'] ?? '');

    
    $photoData = null;
    $updatePhoto = false;
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        $photoData = file_get_contents($_FILES['photo']['tmp_name']);
        $updatePhoto = true;
    }

    if ($pwd && $updatePhoto) {
        $hash = password_hash($pwd, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE ouvrier SET nom=?,prenom=?,email=?,description=?,photo=?,password=? WHERE id_ouvrier=?");
        $stmt->execute([$nom, $prenom, $email, $description, $photoData, $hash, $id]);
    } elseif ($pwd) {
        $hash = password_hash($pwd, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE ouvrier SET nom=?,prenom=?,email=?,description=?,password=? WHERE id_ouvrier=?");
        $stmt->execute([$nom, $prenom, $email, $description, $hash, $id]);
    } elseif ($updatePhoto) {
        $stmt = $pdo->prepare("UPDATE ouvrier SET nom=?,prenom=?,email=?,description=?,photo=? WHERE id_ouvrier=?");
        $stmt->execute([$nom, $prenom, $email, $description, $photoData, $id]);
    } else {
        $stmt = $pdo->prepare("UPDATE ouvrier SET nom=?,prenom=?,email=?,description=? WHERE id_ouvrier=?");
        $stmt->execute([$nom, $prenom, $email, $description, $id]);
    }
    $_SESSION['nom'] = $nom;
    $_SESSION['prenom'] = $prenom;
    $msg = "Profil mis à jour.";
}

$stmt = $pdo->prepare("SELECT * FROM ouvrier WHERE id_ouvrier=?");
$stmt->execute([$id]);
$ouv = $stmt->fetch();

$stmt = $pdo->prepare("SELECT ROUND(AVG(note),1) FROM candidature WHERE id_ouvrier=? AND note IS NOT NULL");
$stmt->execute([$id]);
$moyenne = $stmt->fetchColumn();
?>

<div class="container" style="max-width:700px;">
  <div class="profile-header">
    <div class="profile-avatar">
      <?php if ($ouv['photo']): ?>
        <img src="data:image/jpeg;base64,<?= base64_encode($ouv['photo']) ?>">
      <?php else: ?>
        <?= strtoupper(substr($ouv['prenom'],0,1) . substr($ouv['nom'],0,1)) ?>
      <?php endif; ?>
    </div>
    <div>
      <div class="profile-name"><?= $ouv['prenom'] . ' ' . $ouv['nom'] ?></div>
      <div class="profile-sub"> Ouvrier de récolte · @<?= $ouv['pseudo'] ?></div>
      <div class="profile-sub">📧 <?= $ouv['email'] ?></div>
      <?php if ($moyenne): ?>
        <div style="margin-top:0.4rem;">
          <?php for ($i=1; $i<=10; $i++): ?>
            <span style="color:<?= $i <= $moyenne ? '#f0ad4e' : 'rgba(255,255,255,0.3)' ?>;font-size:0.9rem;">★</span>
          <?php endfor; ?>
          <span style="opacity:0.8;font-size:0.85rem;margin-left:0.3rem;"><?= $moyenne ?>/10</span>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <?php if ($msg): ?><div class="alert alert-success"> <?= $msg ?></div><?php endif; ?>
  <?php if ($error): ?><div class="alert alert-error"> <?= $error ?></div><?php endif; ?>

  <div class="card">
    <h3 style="font-family:'Playfair Display',serif;color:var(--olive-dark);margin-bottom:1.5rem;">Modifier mon profil</h3>
    <form method="POST" enctype="multipart/form-data" data-validate>
      <div class="form-row">
        <div class="form-group">
          <label>Nom</label>
          <input type="text" name="nom" value="<?= $ouv['nom'] ?>" required>
          <span class="error-msg"></span>
        </div>
        <div class="form-group">
          <label>Prénom</label>
          <input type="text" name="prenom" value="<?= $ouv['prenom'] ?>" required>
          <span class="error-msg"></span>
        </div>
      </div>
      <div class="form-group">
        <label>Email</label>
        <input type="email" name="email" value="<?= $ouv['email'] ?>" required>
        <span class="error-msg"></span>
      </div>
      <div class="form-group">
        <label>Description (expérience, niveau éducatif…)</label>
        <textarea name="description" rows="3"><?= $ouv['description'] ?? '' ?></textarea>
        <span class="error-msg"></span>
      </div>
      <div class="form-group">
        <label>Photo d'identité <span style="color:var(--text-light);font-weight:400;">(laisser vide pour garder l'actuelle)</span></label>
        <input type="file" name="photo" accept="image/*">
        <span class="error-msg"></span>
      </div>
      <div class="form-group">
        <label>Nouveau mot de passe <span style="color:var(--text-light);font-weight:400;">(optionnel)</span></label>
        <input type="password" name="password" placeholder="Min 8 chars, finit par $ ou #">
        <span class="error-msg"></span>
      </div>
      <button type="submit" class="btn btn-earth">Enregistrer les modifications</button>
    </form>
  </div>

  <div class="card" style="margin-top:1.5rem;background:var(--cream);">
    <h4 style="color:var(--text-light);font-size:0.85rem;text-transform:uppercase;letter-spacing:0.05em;margin-bottom:0.8rem;">Informations non modifiables</h4>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
      <div><span style="color:var(--text-light);font-size:0.82rem;">CIN</span><br><strong><?= $ouv['CIN'] ?></strong></div>
      <div><span style="color:var(--text-light);font-size:0.82rem;">Pseudo</span><br><strong>@<?= $ouv['pseudo'] ?></strong></div>
    </div>
  </div>
</div>

<?php require_once '../footer.php'; ?>
