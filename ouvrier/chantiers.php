<?php
require_once 'auth_ouvrier.php';
require_once '../config.php';
$pageTitle = "Mes chantiers";
require_once '../header.php';

$pdo = getDB();
$id = $_SESSION['user'];

$stmt = $pdo->prepare("
  SELECT c.*, o.date_debut, o.date_fin, o.prix_journee, o.adresse AS adresse_site,
         tf.libelle AS fruit, g.libelle AS gouvernorat,
         ag.nom AS agri_nom, ag.prenom AS agri_prenom
  FROM candidature c
  JOIN offre o ON c.id_offre=o.id_offre
  JOIN type_fruit tf ON o.id_type_fruit=tf.id_type_fruit
  JOIN gouvernorat g ON o.id_gouvernorat=g.id_gouvernorat
  JOIN agriculteur ag ON o.id_agriculteur=ag.id_agriculteur
  WHERE c.id_ouvrier=? AND c.decision='accepte' AND o.date_fin < CURDATE()
  ORDER BY o.date_fin DESC
");
$stmt->execute([$id]);
$chantiers = $stmt->fetchAll();


$total_gains = array_sum(array_column($chantiers, 'remuneration'));
$notes = array_filter(array_column($chantiers, 'note'), fn($n) => $n !== null);
$moyenne = count($notes) ? round(array_sum($notes) / count($notes), 1) : null;
?>

<div class="container">
  <h1 class="page-title">Mes chantiers clôturés</h1>
  <p class="page-subtitle"><?= count($chantiers) ?> chantier(s) terminé(s)</p>

  <!-- Résumé -->
  <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:1rem;margin-bottom:2rem;">
    <div class="card" style="text-align:center;border-top:4px solid var(--olive);">
      <div style="font-size:2rem;font-weight:700;color:var(--olive);"><?= count($chantiers) ?></div>
      <div style="color:var(--text-light);font-size:0.88rem;">Chantiers terminés</div>
    </div>
    <div class="card" style="text-align:center;border-top:4px solid var(--earth);">
      <div style="font-size:2rem;font-weight:700;color:var(--earth);"><?= number_format($total_gains, 1) ?> DT</div>
      <div style="color:var(--text-light);font-size:0.88rem;">Gains totaux</div>
    </div>
    <div class="card" style="text-align:center;border-top:4px solid #f0ad4e;">
      <div style="font-size:2rem;font-weight:700;color:#f0ad4e;"><?= $moyenne ?: '—' ?></div>
      <div style="color:var(--text-light);font-size:0.88rem;">Moyenne /10</div>
    </div>
  </div>

  <?php if (empty($chantiers)): ?>
    <div class="card" style="text-align:center;padding:3rem;">
      <div style="font-size:3rem;margin-bottom:1rem;"></div>
      <p style="color:var(--text-light);">Aucun chantier clôturé pour l'instant.</p>
      <a href="offres.php" class="btn btn-primary" style="margin-top:1rem;">Trouver des offres</a>
    </div>
  <?php else: ?>
    <div class="card-grid">
      <?php foreach ($chantiers as $ch): ?>
      <div class="card">
        <div class="card-header">
          <span class="card-title">🌿 <?= $ch['fruit'] ?></span>
          <?php if ($ch['note'] !== null): ?>
            <span class="badge badge-olive"> <?= $ch['note'] ?>/10</span>
          <?php else: ?>
            <span class="badge" style="background:var(--cream);color:var(--text-light);">Non noté</span>
          <?php endif; ?>
        </div>

        <div style="margin-bottom:1rem;">
          <div style="color:var(--text-light);font-size:0.82rem;margin-bottom:0.3rem;">📍 Lieu</div>
          <strong><?= $ch['gouvernorat'] ?></strong> — <?= $ch['adresse_site'] ?>
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:0.7rem;margin-bottom:1rem;">
          <div>
            <div style="color:var(--text-light);font-size:0.78rem;text-transform:uppercase;letter-spacing:0.05em;">Période</div>
            <div style="font-size:0.88rem;"><?= date('d/m/Y', strtotime($ch['date_debut'])) ?><br>→ <?= date('d/m/Y', strtotime($ch['date_fin'])) ?></div>
          </div>
          <div>
            <div style="color:var(--text-light);font-size:0.78rem;text-transform:uppercase;letter-spacing:0.05em;">Rémunération</div>
            <div style="font-size:1.1rem;font-weight:700;color:var(--earth);">
              <?= $ch['remuneration'] !== null ? number_format($ch['remuneration'], 1) . ' DT' : '—' ?>
            </div>
          </div>
        </div>

        <div style="margin-bottom:0.8rem;">
          <div style="color:var(--text-light);font-size:0.82rem;margin-bottom:0.3rem;"> Agriculteur</div>
          <?= $ch['agri_prenom'] . ' ' . $ch['agri_nom'] ?>
        </div>

        <?php if ($ch['commentaire']): ?>
        <div style="background:var(--cream);border-radius:8px;padding:0.8rem;border-left:3px solid var(--olive-light);">
          <div style="color:var(--text-light);font-size:0.78rem;text-transform:uppercase;letter-spacing:0.05em;margin-bottom:0.3rem;">Commentaire</div>
          <em style="color:var(--text-mid);font-size:0.9rem;">"<?= $ch['commentaire'] ?>"</em>
        </div>
        <?php endif; ?>

        <?php if ($ch['note'] !== null): ?>
        <div style="margin-top:0.8rem;display:flex;gap:2px;">
          <?php for ($i=1; $i<=10; $i++): ?>
            <span style="color:<?= $i <= $ch['note'] ? '#f0ad4e' : '#ddd' ?>;font-size:1rem;"></span>
          <?php endfor; ?>
        </div>
        <?php endif; ?>
      </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>

<?php require_once '../footer.php'; ?>
