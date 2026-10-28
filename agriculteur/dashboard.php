<?php
require_once 'auth_agriculteur.php';
require_once '../config.php';
$pageTitle = "Tableau de bord";
require_once '../header.php';

$pdo = getDB();
$id = $_SESSION['user'];


$stmt = $pdo->prepare("SELECT COUNT(*) FROM offre WHERE id_agriculteur=?");
$stmt->execute([$id]);
$total_offres = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM candidature c JOIN offre o ON c.id_offre=o.id_offre WHERE o.id_agriculteur=? AND c.decision='encours'");
$stmt->execute([$id]);
$en_attente = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM offre WHERE id_agriculteur=? AND date_limite >= CURDATE() AND (SELECT COUNT(*) FROM candidature WHERE id_offre=offre.id_offre AND decision='accepte') < nombre_ouvriers");
$stmt->execute([$id]); 
$offres_actives = $stmt->fetchColumn();


$stmt = $pdo->prepare("
  SELECT o.*, tf.libelle AS fruit, g.libelle AS gouvernorat,
    (SELECT COUNT(*) FROM candidature WHERE id_offre=o.id_offre AND decision='accepte') AS nb_acceptes
  FROM offre o
  JOIN type_fruit tf ON o.id_type_fruit=tf.id_type_fruit
  JOIN gouvernorat g ON o.id_gouvernorat=g.id_gouvernorat
  WHERE o.id_agriculteur=?
  ORDER BY o.id_offre DESC LIMIT 3
");
$stmt->execute([$id]);
$recent_offres = $stmt->fetchAll();
?>

<div class="container">
  <h1 class="page-title">Bonjour, <?= $_SESSION['prenom'] ?> ! </h1>
  <p class="page-subtitle">Voici un aperçu de votre activité</p>

  <!-- Stats -->
  <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:1rem;margin-bottom:2rem;">
    <div class="card" style="text-align:center;border-top:4px solid var(--olive);">
      <div style="font-size:2.2rem;font-weight:700;color:var(--olive);"><?= $total_offres ?></div>
      <div style="color:var(--text-light);font-size:0.9rem;">Offres publiées</div>
    </div>
    <div class="card" style="text-align:center;border-top:4px solid var(--olive-light);">
      <div style="font-size:2.2rem;font-weight:700;color:var(--olive-light);"><?= $offres_actives ?></div>
      <div style="color:var(--text-light);font-size:0.9rem;">Offres actives</div>
    </div>
    <div class="card" style="text-align:center;border-top:4px solid var(--earth);">
      <div style="font-size:2.2rem;font-weight:700;color:var(--earth);"><?= $en_attente ?></div>
      <div style="color:var(--text-light);font-size:0.9rem;">Candidatures en attente</div>
    </div>
  </div>

  
  <div style="display:flex;gap:1rem;flex-wrap:wrap;margin-bottom:2rem;">
    <a href="ajouter_offre.php" class="btn btn-primary">+ Nouvelle offre</a>
    <a href="mes_offres.php" class="btn btn-secondary">Voir mes offres</a>
  </div>

  
  <?php if ($recent_offres): ?>
  <h2 style="font-family:'Playfair Display',serif;color:var(--olive-dark);margin-bottom:1rem;">Dernières offres</h2>
  <div class="card-grid">
    <?php foreach ($recent_offres as $o): ?>
    <div class="offer-card">
      <div class="offer-card-top">
        <div class="offer-card-fruit">🌿 <?= $o['fruit'] ?></div>
        <div class="offer-card-loc">📍 <?= $o['gouvernorat'] ?> — <?= $o['adresse'] ?></div>
      </div>
      <div class="offer-card-body">
        <div class="offer-meta">
          <div class="offer-meta-item">
            <span class="offer-meta-label">Début</span>
            <span class="offer-meta-value"><?= date('d/m/Y', strtotime($o['date_debut'])) ?></span>
          </div>
          <div class="offer-meta-item">
            <span class="offer-meta-label">Fin</span>
            <span class="offer-meta-value"><?= date('d/m/Y', strtotime($o['date_fin'])) ?></span>
          </div>
          <div class="offer-meta-item">
            <span class="offer-meta-label">Prix/jour</span>
            <span class="offer-meta-value" style="color:var(--olive-dark);font-weight:700;"><?= $o['prix_journee'] ?> DT</span>
          </div>
          <div class="offer-meta-item">
            <span class="offer-meta-label">Ouvriers</span>
            <span class="offer-meta-value"><?= $o['nb_acceptes'] ?> / <?= $o['nombre_ouvriers'] ?></span>
          </div>
        </div>
      </div>
      <div class="offer-card-actions">
        <a href="candidats.php?id=<?= $o['id_offre'] ?>" class="btn btn-primary btn-sm">Candidats</a>
        <a href="evaluer.php?id=<?= $o['id_offre'] ?>" class="btn btn-secondary btn-sm">Évaluer</a>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</div>

<?php require_once '../footer.php'; ?>
