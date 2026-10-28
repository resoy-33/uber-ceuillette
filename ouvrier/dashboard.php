<?php
require_once 'auth_ouvrier.php';
require_once '../config.php';
$pageTitle = "Tableau de bord";
require_once '../header.php';

$pdo = getDB();
$id = $_SESSION['user'];


$stmt = $pdo->prepare("SELECT COUNT(*) FROM candidature WHERE id_ouvrier=?");
$stmt->execute([$id]); $total = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM candidature WHERE id_ouvrier=? AND decision='accepte'");
$stmt->execute([$id]); $acceptees = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COALESCE(SUM(remuneration),0) FROM candidature WHERE id_ouvrier=? AND decision='accepte'");
$stmt->execute([$id]); $gains = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT ROUND(AVG(note),1) FROM candidature WHERE id_ouvrier=? AND note IS NOT NULL");
$stmt->execute([$id]); $moyenne = $stmt->fetchColumn();


$stmt = $pdo->query("
  SELECT o.*, tf.libelle AS fruit, g.libelle AS gouvernorat
  FROM offre o
  JOIN type_fruit tf ON o.id_type_fruit=tf.id_type_fruit
  JOIN gouvernorat g ON o.id_gouvernorat=g.id_gouvernorat
  WHERE o.date_limite >= CURDATE()
    AND (SELECT COUNT(*) FROM candidature WHERE id_offre=o.id_offre AND decision='accepte') < o.nombre_ouvriers
  ORDER BY o.prix_journee DESC
  LIMIT 3
");
$offres = $stmt->fetchAll();
?>

<div class="container">
  <h1 class="page-title">Bonjour, <?= $_SESSION['prenom'] ?> ! </h1>
  <p class="page-subtitle">Voici un résumé de votre activité</p>

  <!-- Stats -->
  <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:1rem;margin-bottom:2rem;">
    <div class="card" style="text-align:center;border-top:4px solid var(--olive);">
      <div style="font-size:2rem;font-weight:700;color:var(--olive);"><?= $total ?></div>
      <div style="color:var(--text-light);font-size:0.88rem;">Candidatures</div>
    </div>
    <div class="card" style="text-align:center;border-top:4px solid #28a745;">
      <div style="font-size:2rem;font-weight:700;color:#28a745;"><?= $acceptees ?></div>
      <div style="color:var(--text-light);font-size:0.88rem;">Acceptées</div>
    </div>
    <div class="card" style="text-align:center;border-top:4px solid var(--earth);">
      <div style="font-size:2rem;font-weight:700;color:var(--earth);"><?= number_format($gains, 1) ?> DT</div>
      <div style="color:var(--text-light);font-size:0.88rem;">Gains totaux</div>
    </div>
    <div class="card" style="text-align:center;border-top:4px solid #f0ad4e;">
      <div style="font-size:2rem;font-weight:700;color:#f0ad4e;"><?= $moyenne ?: '—' ?></div>
      <div style="color:var(--text-light);font-size:0.88rem;">Moyenne /10</div>
    </div>
  </div>

  <div style="display:flex;gap:1rem;flex-wrap:wrap;margin-bottom:2rem;">
    <a href="offres.php" class="btn btn-primary"> Voir les offres</a>
    <a href="mes_candidatures.php" class="btn btn-secondary"> Mes candidatures</a>
    <a href="chantiers.php" class="btn btn-secondary"> Mes chantiers</a>
  </div>

  <?php if ($offres): ?>
  <h2 style="font-family:'Playfair Display',serif;color:var(--olive-dark);margin-bottom:1rem;">Offres à ne pas manquer </h2>
  <div class="card-grid">
    <?php foreach ($offres as $o): ?>
    <div class="offer-card">
      <div class="offer-card-top">
        <div class="offer-card-fruit">🌿 <?= $o['fruit'] ?></div>
        <div class="offer-card-loc">📍 <?= $o['gouvernorat'] ?></div>
      </div>
      <div class="offer-card-body">
        <div class="offer-meta">
          <div class="offer-meta-item">
            <span class="offer-meta-label">Période</span>
            <span class="offer-meta-value"><?= date('d/m', strtotime($o['date_debut'])) ?> → <?= date('d/m/Y', strtotime($o['date_fin'])) ?></span>
          </div>
          <div class="offer-meta-item">
            <span class="offer-meta-label">Prix/jour</span>
            <span class="offer-meta-value" style="color:var(--olive-dark);font-weight:700;"><?= $o['prix_journee'] ?> DT</span>
          </div>
        </div>
      </div>
      <div class="offer-card-actions">
        <a href="offres.php" class="btn btn-primary btn-sm">Voir les offres</a>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</div>

<?php require_once '../footer.php'; ?>
