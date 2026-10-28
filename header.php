<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$role = $_SESSION['role'] ?? null;
$user = $_SESSION['user'] ?? null;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= isset($pageTitle) ? htmlspecialchars($pageTitle) . ' - Uber-Cueillette' : 'Uber-Cueillette' ?></title>
  <link rel="stylesheet" href="<?= $basePath ?? '' ?>style.css">
</head>
<body>

<nav>
  <a href="<?= $basePath ?? '' ?>index.php" class="nav-brand">🌿 Uber-<span>Cueillette</span></a>
  <ul>
    <?php if (!isset($user)): ?>
        <li><a href="<?= $basePath ?? '' ?>login.php">Se connecter</a></li>
        <li><a href="<?= $basePath ?? '' ?>index.php">Accueil</a></li>
        
    <?php elseif ($user && $role === 'agriculteur'): ?>
      <?php
     
        try {
            require_once __DIR__ . '/config.php';
            $pdo_h = getDB();
            $nb_notif = $pdo_h->prepare("SELECT COUNT(*) FROM notification WHERE destinataire_type='agriculteur' AND destinataire_id=? AND lu=0");
            $nb_notif->execute([$user]); $cnt_notif_agri = $nb_notif->fetchColumn();
            $nb_msg_h = $pdo_h->prepare("SELECT COUNT(*) FROM message WHERE destinataire_type='agriculteur' AND destinataire_id=? AND lu=0");
            $nb_msg_h->execute([$user]); $cnt_msg_agri = $nb_msg_h->fetchColumn();
        } catch(Exception $e) { $cnt_notif_agri = 0; $cnt_msg_agri = 0; }
      ?>
      <li><a href="<?= $basePath ?? '' ?>agriculteur/dashboard.php">Tableau de bord</a></li>
      <li><a href="<?= $basePath ?? '' ?>agriculteur/mes_offres.php">Mes offres</a></li>
      <li><a href="<?= $basePath ?? '' ?>agriculteur/statistiques.php">📊 Stats</a></li>
      <li>
        <a href="<?= $basePath ?? '' ?>agriculteur/messages.php" style="position:relative;">
          💬 Messages<?php if ($cnt_msg_agri > 0): ?> <span class="nav-badge"><?= $cnt_msg_agri ?></span><?php endif; ?>
        </a>
      </li>
      <li>
        <a href="<?= $basePath ?? '' ?>agriculteur/notifications.php" style="position:relative;">
          🔔<?php if ($cnt_notif_agri > 0): ?> <span class="nav-badge"><?= $cnt_notif_agri ?></span><?php endif; ?>
        </a>
      </li>
      <li><a href="<?= $basePath ?? '' ?>agriculteur/profil.php">Mon profil</a></li>
      <li><a href="<?= $basePath ?? '' ?>logout.php" class="btn-nav-logout">Déconnexion</a></li>

    <?php elseif ($user && $role === 'ouvrier'): ?>
      <?php
        try {
            require_once __DIR__ . '/config.php';
            $pdo_h = getDB();
            $nb_notif = $pdo_h->prepare("SELECT COUNT(*) FROM notification WHERE destinataire_type='ouvrier' AND destinataire_id=? AND lu=0");
            $nb_notif->execute([$user]); $cnt_notif_ouv = $nb_notif->fetchColumn();
            $nb_msg_h = $pdo_h->prepare("SELECT COUNT(*) FROM message WHERE destinataire_type='ouvrier' AND destinataire_id=? AND lu=0");
            $nb_msg_h->execute([$user]); $cnt_msg_ouv = $nb_msg_h->fetchColumn();
        } catch(Exception $e) { $cnt_notif_ouv = 0; $cnt_msg_ouv = 0; }
      ?>
      <li><a href="<?= $basePath ?? '' ?>ouvrier/dashboard.php">Tableau de bord</a></li>
      <li><a href="<?= $basePath ?? '' ?>ouvrier/offres.php">Offres</a></li>
      <li><a href="<?= $basePath ?? '' ?>ouvrier/statistiques.php">📊 Stats</a></li>
      <li>
        <a href="<?= $basePath ?? '' ?>ouvrier/messages.php">
          💬 Messages<?php if ($cnt_msg_ouv > 0): ?> <span class="nav-badge"><?= $cnt_msg_ouv ?></span><?php endif; ?>
        </a>
      </li>
      <li>
        <a href="<?= $basePath ?? '' ?>ouvrier/notifications.php">
          🔔<?php if ($cnt_notif_ouv > 0): ?> <span class="nav-badge"><?= $cnt_notif_ouv ?></span><?php endif; ?>
        </a>
      </li>
      <li><a href="<?= $basePath ?? '' ?>ouvrier/profil.php">Mon profil</a></li>
      <li><a href="<?= $basePath ?? '' ?>logout.php" class="btn-nav-logout">Déconnexion</a></li>

    <?php endif; ?>

  </ul>
</nav>