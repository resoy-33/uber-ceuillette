<?php
require_once 'auth_ouvrier.php';
require_once '../config.php';
$pageTitle = "Mes statistiques";
$basePath = "../";
require_once '../header.php';

$pdo = getDB();
$id = (int)$_SESSION['user'];


$s = $pdo->prepare("
    SELECT
        COUNT(*) AS total_candidatures,
        SUM(CASE WHEN decision='accepte' THEN 1 ELSE 0 END) AS acceptees,
        SUM(CASE WHEN decision='refuse'  THEN 1 ELSE 0 END) AS refusees,
        SUM(CASE WHEN decision='encours' THEN 1 ELSE 0 END) AS en_attente,
        COALESCE(SUM(remuneration),0) AS gains_totaux,
        ROUND(AVG(CASE WHEN note IS NOT NULL THEN note END),1) AS note_moyenne
    FROM candidature WHERE id_ouvrier=?
");
$s->execute([$id]);
$stats = $s->fetch();


$fruit_stmt = $pdo->prepare("
    SELECT tf.libelle AS fruit, COUNT(*) AS nb,
        SUM(CASE WHEN c.decision='accepte' THEN 1 ELSE 0 END) AS acceptees,
        COALESCE(SUM(c.remuneration),0) AS gains,
        ROUND(AVG(CASE WHEN c.note IS NOT NULL THEN c.note END),1) AS note_moy
    FROM candidature c
    JOIN offre o ON c.id_offre=o.id_offre
    JOIN type_fruit tf ON o.id_type_fruit=tf.id_type_fruit
    WHERE c.id_ouvrier=?
    GROUP BY tf.id_type_fruit ORDER BY nb DESC
");
$fruit_stmt->execute([$id]);
$par_fruit = $fruit_stmt->fetchAll();


$detail_stmt = $pdo->prepare("
    SELECT c.*, tf.libelle AS fruit, g.libelle AS gouvernorat,
        o.date_debut, o.date_fin, o.prix_journee,
        CONCAT(a.nom,' ',a.prenom) AS agriculteur
    FROM candidature c
    JOIN offre o ON c.id_offre=o.id_offre
    JOIN type_fruit tf ON o.id_type_fruit=tf.id_type_fruit
    JOIN gouvernorat g ON o.id_gouvernorat=g.id_gouvernorat
    JOIN agriculteur a ON o.id_agriculteur=a.id_agriculteur
    WHERE c.id_ouvrier=?
    ORDER BY c.date_candidature DESC
");
$detail_stmt->execute([$id]);
$details = $detail_stmt->fetchAll();

$taux = $stats['total_candidatures'] > 0
    ? round($stats['acceptees'] / $stats['total_candidatures'] * 100, 1)
    : 0;
?>

<div class="container">
  <h1 class="page-title">📊 Mes statistiques</h1>
  <p class="page-subtitle">Votre historique et performance</p>

  <!-- Global stats -->
  <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:1rem;margin-bottom:2rem;">
    <div class="card stat-card" style="border-top:4px solid var(--olive);">
      <div class="stat-val"><?= $stats['total_candidatures'] ?></div>
      <div class="stat-lbl">Candidatures</div>
    </div>
    <div class="card stat-card" style="border-top:4px solid #28a745;">
      <div class="stat-val"><?= $stats['acceptees'] ?></div>
      <div class="stat-lbl">Acceptées</div>
    </div>
    <div class="card stat-card" style="border-top:4px solid #dc3545;">
      <div class="stat-val"><?= $stats['refusees'] ?></div>
      <div class="stat-lbl">Refusées</div>
    </div>
    <div class="card stat-card" style="border-top:4px solid #f0ad4e;">
      <div class="stat-val"><?= $stats['en_attente'] ?></div>
      <div class="stat-lbl">En attente</div>
    </div>
    <div class="card stat-card" style="border-top:4px solid var(--earth);">
      <div class="stat-val"><?= number_format($stats['gains_totaux'],1) ?> DT</div>
      <div class="stat-lbl">Gains totaux</div>
    </div>
    <div class="card stat-card" style="border-top:4px solid #6c757d;">
      <div class="stat-val"><?= $taux ?>%</div>
      <div class="stat-lbl">Taux d'acceptation</div>
    </div>
    <?php if ($stats['note_moyenne']): ?>
    <div class="card stat-card" style="border-top:4px solid #f0ad4e;">
      <div class="stat-val"><?= $stats['note_moyenne'] ?>/10</div>
      <div class="stat-lbl">Votre note moyenne</div>
    </div>
    <?php endif; ?>
  </div>

  <?php if ($stats['total_candidatures'] > 0): ?>
  <div class="card" style="margin-bottom:2rem;">
    <h3 style="font-family:'Playfair Display',serif;margin-bottom:1rem;">Répartition de vos candidatures</h3>
    <div style="display:flex;gap:0.5rem;margin-bottom:0.5rem;">
      <?php
        $total = $stats['total_candidatures'];
        $pct_acc = $total ? round($stats['acceptees']/$total*100) : 0;
        $pct_ref = $total ? round($stats['refusees']/$total*100) : 0;
        $pct_enc = 100 - $pct_acc - $pct_ref;
      ?>
      <?php if ($pct_acc > 0): ?>
        <div style="height:28px;background:#28a745;border-radius:6px 0 0 6px;width:<?= $pct_acc ?>%;display:flex;align-items:center;justify-content:center;color:#fff;font-size:0.78rem;font-weight:600;"><?= $pct_acc ?>%</div>
      <?php endif; ?>
      <?php if ($pct_ref > 0): ?>
        <div style="height:28px;background:#dc3545;width:<?= $pct_ref ?>%;display:flex;align-items:center;justify-content:center;color:#fff;font-size:0.78rem;font-weight:600;"><?= $pct_ref ?>%</div>
      <?php endif; ?>
      <?php if ($pct_enc > 0): ?>
        <div style="height:28px;background:#f0ad4e;border-radius:0 6px 6px 0;width:<?= $pct_enc ?>%;display:flex;align-items:center;justify-content:center;color:#fff;font-size:0.78rem;font-weight:600;"><?= $pct_enc ?>%</div>
      <?php endif; ?>
    </div>
    <div style="display:flex;gap:1.5rem;font-size:0.82rem;color:var(--text-light);">
      <span><span style="color:#28a745;">●</span> Acceptées (<?= $stats['acceptees'] ?>)</span>
      <span><span style="color:#dc3545;">●</span> Refusées (<?= $stats['refusees'] ?>)</span>
      <span><span style="color:#f0ad4e;">●</span> En attente (<?= $stats['en_attente'] ?>)</span>
    </div>
  </div>
  <?php endif; ?>

  <?php if ($par_fruit): ?>
  <div class="card" style="margin-bottom:2rem;">
    <h3 style="font-family:'Playfair Display',serif;margin-bottom:1rem;">Par type de récolte</h3>
    <div class="table-wrap">
      <table>
        <thead><tr><th>Fruit</th><th>Candidatures</th><th>Acceptées</th><th>Gains</th><th>Note moy.</th></tr></thead>
        <tbody>
          <?php foreach ($par_fruit as $f): ?>
          <tr>
            <td>🌿 <?= htmlspecialchars($f['fruit']) ?></td>
            <td><?= $f['nb'] ?></td>
            <td><span class="badge badge-green"><?= $f['acceptees'] ?></span></td>
            <td style="color:var(--earth);font-weight:600;"><?= number_format($f['gains'],1) ?> DT</td>
            <td><?= $f['note_moy'] ? $f['note_moy'].'/10' : '—' ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
  <?php endif; ?>


  <?php if ($details): ?>
  <div class="card">
    <h3 style="font-family:'Playfair Display',serif;margin-bottom:1rem;">Historique complet</h3>
    <div class="table-wrap">
      <table>
        <thead>
          <tr><th>Offre</th><th>Agriculteur</th><th>Lieu</th><th>Période</th><th>Prix/j</th><th>Statut</th><th>Note</th></tr>
        </thead>
        <tbody>
          <?php foreach ($details as $d): ?>
          <tr>
            <td>🌿 <?= htmlspecialchars($d['fruit']) ?></td>
            <td style="font-size:0.85rem;"><?= htmlspecialchars($d['agriculteur']) ?></td>
            <td style="font-size:0.82rem;">📍 <?= htmlspecialchars($d['gouvernorat']) ?></td>
            <td style="font-size:0.8rem;"><?= date('d/m', strtotime($d['date_debut'])) ?> → <?= date('d/m/Y', strtotime($d['date_fin'])) ?></td>
            <td style="color:var(--olive-dark);font-weight:700;"><?= $d['prix_journee'] ?> DT</td>
            <td>
              <?php if ($d['decision'] === 'accepte'): ?>
                <span class="badge badge-green">✅ Accepté</span>
              <?php elseif ($d['decision'] === 'refuse'): ?>
                <span class="badge badge-red">❌ Refusé</span>
              <?php else: ?>
                <span class="badge badge-blue">⏳ En attente</span>
              <?php endif; ?>
            </td>
            <td><?= $d['note'] ? $d['note'].'/10' : '—' ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
  <?php endif; ?>
</div>

<style>
.stat-card { text-align:center; padding:1.25rem 1rem; }
.stat-val { font-size:1.9rem; font-weight:700; color:var(--olive-dark); }
.stat-lbl { font-size:0.82rem; color:var(--text-light); margin-top:0.25rem; }
</style>

<?php require_once '../footer.php'; ?>
