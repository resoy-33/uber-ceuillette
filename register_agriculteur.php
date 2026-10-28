<?php
$pageTitle  = "Inscription Agriculteur";
$pageScript = "register.js";
require_once 'config.php';
require_once 'header.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom     = trim($_POST['nom'] ?? '');
    $prenom  = trim($_POST['prenom'] ?? '');
    $cin     = trim($_POST['CIN'] ?? '');
    $email   = trim($_POST['email'] ?? '');
    $adresse = trim($_POST['adresse'] ?? '');
    $pseudo  = trim($_POST['pseudo'] ?? '');
    $pwd     = trim($_POST['password'] ?? '');
    $pwd2    = trim($_POST['confirm_password'] ?? '');

  
    $pdo = getDB();
    $stmt = $pdo->prepare("SELECT id_agriculteur FROM agriculteur WHERE pseudo=? OR email=? OR CIN=?");
    $stmt->execute([$pseudo, $email, $cin]);
    if ($stmt->fetch()) {
        $error = "Un compte avec ce pseudo, email ou CIN existe déjà.";
    } else {
        $hash = password_hash($pwd, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO agriculteur (nom,prenom,CIN,email,adresse,pseudo,password) VALUES (?,?,?,?,?,?,?)");
        $stmt->execute([$nom, $prenom, $cin, $email, $adresse, $pseudo, $hash]);
        $success = "Inscription réussie ! <a href='login.php'>Connectez-vous</a>";
    }
}
?>

<div class="container">
  <div class="form-container" style="max-width:620px;">
    <h2 class="form-title">Inscription Agriculteur 🌾</h2>
    <p class="form-subtitle">Créez votre compte pour publier des offres de récolte</p>

    <?php if ($error): ?>
      <div class="alert alert-error"> <?= $error ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
      <div class="alert alert-success"><?= $success ?></div>
    <?php endif; ?>

    <form method="POST" data-validate>
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
        <label>Adresse personnelle</label>
        <input type="text" name="adresse" value="<?= $_POST['adresse'] ?? '' ?>" placeholder="Votre adresse">
        <span class="error-msg"></span>
      </div>

      <div class="form-group">
        <label>Pseudo (lettres uniquement) *</label>
        <input type="text" name="pseudo" value="<?= $_POST['pseudo'] ?? '' ?>" required placeholder="MonPseudo">
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

      <button type="submit" class="btn btn-primary btn-full">Créer mon compte</button>
    </form>

    <div class="form-divider" style="margin-top:1rem;">
      Déjà inscrit ? <a href="login.php">Se connecter</a>
    </div>
  </div>
</div>

<?php require_once 'footer.php'; ?>
