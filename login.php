<?php
$pageTitle  = "Connexion";
$pageScript = "login.js";
require_once 'config.php';
require_once 'header.php';

$error = '';
$selectedRole = $_POST['role'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pseudo = trim($_POST['pseudo'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $role = $_POST['role'] ?? '';

    if ($pseudo && $password && in_array($role, ['agriculteur', 'ouvrier'])) {
        $pdo = getDB();
        $table = $role;
        $id_col = "id_$role";

        $stmt = $pdo->prepare("SELECT * FROM `$table` WHERE pseudo = ?");
        $stmt->execute([$pseudo]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user'] = $user[$id_col];
            $_SESSION['role'] = $role;
            $_SESSION['nom']  = $user['nom'];
            $_SESSION['prenom'] = $user['prenom'];
            header("Location: $role/dashboard.php");
            exit;
        } else {
            $error = "Pseudo ou mot de passe incorrect.";
        }
    } else {
        $error = "Veuillez remplir tous les champs.";
    }
}
?>

<div class="container">
  <div class="form-container">
    <h2 class="form-title">Connexion</h2>
    <p class="form-subtitle">Bienvenue sur Uber-Cueillette</p>


    <form method="POST" data-validate>
      <div class="form-group">
        <label>Rôle</label>
        <select name="role" required>
           <option value="">-- Choisissez --</option>
           <option value="agriculteur" <?php if ($selectedRole === 'agriculteur') echo 'selected'; ?>> Agriculteur</option>
           <option value="ouvrier" <?php if ($selectedRole === 'ouvrier') echo 'selected'; ?>> Ouvrier de récolte</option>
        </select>
        <span class="error-msg"></span>
      </div>

      <div class="form-group">
        <label>Pseudo</label>
        <input type="text" name="pseudo" value="<?= $_POST['pseudo'] ?? '' ?>" required placeholder="Votre pseudo">
        <span class="error-msg"></span>
      </div>

      <div class="form-group">
        <label>Mot de passe</label>
        <input type="password" name="password" required placeholder="Votre mot de passe">
        <span class="error-msg"></span>
      </div>

      <button type="submit" class="btn btn-primary btn-full">Se connecter</button>
    </form>

    <div class="form-divider" style="margin-top:1rem;">
      Pas de compte ? <a href="register_agriculteur.php">Agriculteur</a> · <a href="register_ouvrier.php">Ouvrier</a>
    </div>
  </div>
</div>

<?php require_once 'footer.php'; ?>
