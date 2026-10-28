<?php
$pageTitle  = "Inscription Ouvrier";
$pageScript = "register.js";
require_once 'config.php';
require_once 'header.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom        = trim($_POST['nom'] ?? '');
    $prenom     = trim($_POST['prenom'] ?? '');
    $cin        = trim($_POST['CIN'] ?? '');
    $email      = trim($_POST['email'] ?? '');
    $description= trim($_POST['description'] ?? '');
    $pseudo     = trim($_POST['pseudo'] ?? '');
    $pwd        = trim($_POST['password'] ?? '');
    $pwd2       = trim($_POST['confirm_password'] ?? '');

    
    $photoData = null;
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        $photoData = file_get_contents($_FILES['photo']['tmp_name']);
    }

    $pdo = getDB();
    $stmt = $pdo->prepare("SELECT id_ouvrier FROM ouvrier WHERE pseudo=? OR email=? OR CIN=?");
    $stmt->execute([$pseudo, $email, $cin]);
    if ($stmt->fetch()) {
        $error = "Un compte avec ce pseudo, email ou CIN existe déjà.";
    } else {
        $hash = password_hash($pwd, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO ouvrier (nom,prenom,CIN,email,photo,description,pseudo,password) VALUES (?,?,?,?,?,?,?,?)");
        $stmt->execute([$nom, $prenom, $cin, $email, $photoData, $description, $pseudo, $hash]);
        $success = "Inscription réussie ! <a href='login.php'>Connectez-vous</a>";
    }
}
?>

<div class="container">
  <div class="form-container" style="max-width:620px;">
    <h2 class="form-title">Inscription Ouvrier </h2>
    <p class="form-subtitle">Créez votre profil pour trouver des opportunités de récolte</p>

    <?php if ($error): ?>
      <div class="alert alert-error"> <?= $error ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
      <div class="alert alert-success"> <?= $success ?></div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data" data-validate>
      <div class="form-row">
        <div class="form-group">
          <label>Nom *</label>
          <input type="text" name="nom" value="<?= $_POST['nom'] ?? '' ?>" required placeholder="Votre nom">
          <span class="error-msg"></span>
        </div>
        <div class="form-group">
          <label>Prénom *</label>
          <input type="text" name="prenom" value="<?= $_POST['prenom'] ?? '' ?>" required placeholder="Votre prénom">
          <span class="error-msg"></span>
        </div>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label>CIN (8 chiffres) *</label>
          <input type="text" name="CIN" value="<?= $_POST['CIN'] ?? '' ?>" required maxlength="8" placeholder="12345678">
          <span class="error-msg"></span>
        </div>
        <div class="form-group">
          <label>Email *</label>
          <input type="email" name="email" value="<?= $_POST['email'] ?? '' ?>" required placeholder="votre@email.com">
          <span class="error-msg"></span>
        </div>
      </div>

      <div class="form-group">
        <label>Photo d'identité</label>
        <input type="file" name="photo" accept="image/*">
        <span class="error-msg"></span>
      </div>

      <div class="form-group">
        <label>Description (niveau éducatif, expérience…)</label>
        <textarea name="description" rows="3" placeholder="Décrivez votre expérience et vos compétences"><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
        <span class="error-msg"></span>
      </div>

      <div class="form-group">
        <label>Pseudo (lettres uniquement) *</label>
        <input type="text" name="pseudo" value="<?= $_POST['pseudo'] ?? ''?>" required placeholder="MonPseudo">
        <span class="error-msg"></span>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label>Mot de passe *</label>
          <input type="password" name="password" required placeholder="Min 8 chars, finit par $ ou #">
          <span class="error-msg"></span>
        </div>
        <div class="form-group">
          <label>Confirmer le mot de passe *</label>
          <input type="password" name="confirm_password" required placeholder="Répétez le mot de passe">
          <span class="error-msg"></span>
        </div>
      </div>

      <button type="submit" class="btn btn-earth btn-full">Créer mon compte</button>
    </form>

    <div class="form-divider" style="margin-top:1rem;">
      Déjà inscrit ? <a href="login.php">Se connecter</a>
    </div>
  </div>
</div>

<?php require_once 'footer.php'; ?>
