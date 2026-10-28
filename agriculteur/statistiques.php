<?php
require_once 'auth_agriculteur.php';
require_once '../config.php';
$pageTitle = "Statistiques";
$basePath = "../";
require_once '../header.php';

$pdo = getDB();
$id = (int)$_SESSION['user'];

// Global stats
$s = $pdo->prepare("
    SELECT
        COUNT(DISTINCT o.id_offre) AS total_offres,
        COUNT(DISTINCT CASE WHEN o.date_limite >= CURDATE() AND nb_acc.n < o.nombre_ouvriers THEN o.id_offre END) AS offres_actives,
        COUNT(c.id_candidature) AS total_candidatures,
        SUM(CASE WHEN c.decision='accepte' THEN 1 ELSE 0 END) AS total_acceptes,
        SUM(CASE WHEN c.decision='refuse' THEN 1 ELSE 0 END) AS total_refuses,
        SUM(CASE WHEN c.decision='encours' THEN 1 ELSE 0 END) AS total_en_attente,
        ROUND(AVG(CASE WHEN c.note IS NOT NULL THEN c.note END), 1) AS note_moyenne
    FROM offre o
    LEFT JOIN candidature c ON c.id_offre=o.id_offre
    LEFT JOIN (SELECT id_offre, COUNT(*) AS n FROM candidature WHERE decision='accepte' GROUP BY id_offre) nb_acc ON nb_acc.id_offre=o.id_offre
    WHERE o.id_agriculteur=?
");
$s->execute([$id]);
$stats = $s->fetch();

$fruit_stmt = $pdo->prepare("
    SELECT tf.libelle AS fruit,
        COUNT(DISTINCT o.id_offre) AS nb_offres,
        COUNT(c.id_candidature) AS nb_candidatures,
        SUM(CASE WHEN c.decision='accepte' THEN 1 ELSE 0 END) AS nb_acceptes,
        ROUND(AVG(CASE WHEN c.note IS NOT NULL THEN c.note END),1) AS note_moy
    FROM offre o
    JOIN type_fruit tf ON o.id_type_fruit=tf.id_type_fruit
    LEFT JOIN candidature c ON c.id_offre=o.id_offre
    WHERE o.id_agriculteur=?
    GROUP BY tf.id_type_fruit
    ORDER BY nb_offres DESC
");
$fruit_stmt->execute([$id]);
$par_fruit = $fruit_stmt->fetchAll();


$offre_stmt = $pdo->prepare("
    SELECT o.id_offre, tf.libelle AS fruit, g.libelle AS gouvernorat,
        o.date_debut, o.date_fin, o.prix_journee, o.nombre_ouvriers,
        COUNT(c.id_candidature) AS total_candidats,
        SUM(CASE WHEN c.decision='accepte' THEN 1 ELSE 0 END) AS acceptes,
        SUM(CASE WHEN c.decision='refuse'  THEN 1 ELSE 0 END) AS refuses,
        SUM(CASE WHEN c.decision='encours' THEN 1 ELSE 0 END) AS en_attente,
        ROUND(AVG(CASE WHEN c.note IS NOT NULL THEN c.note END),1) AS note_moy
    FROM offre o
    JOIN type_fruit tf ON o.id_type_fruit=tf.id_type_fruit
    JOIN gouvernorat g ON o.id_gouvernorat=g.id_gouvernorat
    LEFT JOIN candidature c ON c.id_offre=o.id_offre
    WHERE o.id_agriculteur=?
    GROUP BY o.id_offre
    ORDER BY o.id_offre DESC
");
$offre_stmt->execute([$id]);
$par_offre = $offre_stmt->fetchAll();

$taux_acceptation = $stats['total_candidatures'] > 0
    ? round($stats['total_acceptes'] / $stats['total_candidatures'] * 100, 1)
    : 0;
?>

<div class="container">
  <h1 class="page-title">📊 Statistiques de vos offres</h1>
  <p class="page-subtitle">Vue d'ensemble de votre activité</p>

  
  <div class="stats-grid" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:1rem;margin-bottom:2rem;">
    <div class="card stat-card" style="border-top:4px solid var(--olive);">
      <div class="stat-val"><?= $stats['total_offres'] ?></div>
      <div class="stat-lbl">Offres publiées</div>
    </div>
    <div class="card stat-card" style="border-top:4px solid var(--olive-light);">
      <div class="stat-val"><?= $stats['offres_actives'] ?></div>
      <div class="stat-lbl">Offres actives</div>
    </div>
    <div class="card stat-card" style="border-top:4px solid var(--earth);">
      <div class="stat-val"><?= $stats['total_candidatures'] ?></div>
      <div class="stat-lbl">Candidatures reçues</div>
    </div>
    <div class="card stat-card" style="border-top:4px solid #28a745;">
      <div class="stat-val"><?= $stats['total_acceptes'] ?></div>
      <div class="stat-lbl">Acceptées</div>
    </div>
    <div class="card stat-card" style="border-top:4px solid #dc3545;">
      <div class="stat-val"><?= $stats['total_refuses'] ?></div>
      <div class="stat-lbl">Refusées</div>
    </div>
    <div class="card stat-card" style="border-top:4px solid #f0ad4e;">
      <div class="stat-val"><?= $stats['total_en_attente'] ?></div>
      <div class="stat-lbl">En attente</div>
    </div>
    <div class="card stat-card" style="border-top:4px solid #6c757d;">
      <div class="stat-val"><?= $taux_acceptation ?>%</div>
      <div class="stat-lbl">Taux d'acceptation</div>
    </div>
    <?php if ($stats['note_moyenne']): ?>
    <div class="card stat-card" style="border-top:4px solid #f0ad4e;">
      <div class="stat-val"><?= $stats['note_moyenne'] ?>/10</div>
      <div class="stat-lbl">Note moyenne ouvriers</div>
    </div>
    <?php endif; ?>
  </div>

  
  <?php if ($stats['total_candidatures'] > 0): ?>
  <div class="card" style="margin-bottom:2rem;">
    <h3 style="font-family:'Playfair Display',serif;margin-bottom:1rem;">Répartition des candidatures</h3>
    <div style="display:flex;gap:0.5rem;margin-bottom:0.5rem;">
      <?php
        $total = $stats['total_candidatures'];
        $pct_acc = $total ? round($stats['total_acceptes']/$total*100) : 0;
        $pct_ref = $total ? round($stats['total_refuses']/$total*100) : 0;
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
      <span><span style="color:#28a745;">●</span> Acceptées (<?= $stats['total_acceptes'] ?>)</span>
      <span><span style="color:#dc3545;">●</span> Refusées (<?= $stats['total_refuses'] ?>)</span>
      <span><span style="color:#f0ad4e;">●</span> En attente (<?= $stats['total_en_attente'] ?>)</span>
    </div>
  </div>
  <?php endif; ?>

  
  <?php if ($par_fruit): ?>
  <div class="card" style="margin-bottom:2rem;">
    <h3 style="font-family:'Playfair Display',serif;margin-bottom:1rem;">Par type de fruit</h3>
    <div class="table-wrap">
      <table>
        <thead><tr><th>Fruit</th><th>Offres</th><th>Candidatures</th><th>Acceptés</th><th>Note moy.</th></tr></thead>
        <tbody>
          <?php foreach ($par_fruit as $f): ?>
          <tr>
            <td>🌿 <?= htmlspecialchars($f['fruit']) ?></td>
            <td><?= $f['nb_offres'] ?></td>
            <td><?= $f['nb_candidatures'] ?></td>
            <td><span class="badge badge-green"><?= $f['nb_acceptes'] ?></span></td>
            <td><?= $f['note_moy'] ? $f['note_moy'].'/10' : '—' ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
  <?php endif; ?>

  
  <?php if ($par_offre): ?>
  <div class="card">
    <h3 style="font-family:'Playfair Display',serif;margin-bottom:1rem;">Détail par offre</h3>
    <div class="table-wrap">
      <table>
        <thead>
          <tr><th>Offre</th><th>Lieu</th><th>Période</th><th>Prix/j</th><th>Candidats</th><th>Acceptés</th><th>Refusés</th><th>En att.</th><th>Note</th></tr>
        </thead>
        <tbody>
          <?php foreach ($par_offre as $o): ?>
          <tr>
            <td>🌿 <?= htmlspecialchars($o['fruit']) ?></td>
            <td style="font-size:0.82rem;">📍 <?= htmlspecialchars($o['gouvernorat']) ?></td>
            <td style="font-size:0.8rem;"><?= date('d/m', strtotime($o['date_debut'])) ?> → <?= date('d/m/Y', strtotime($o['date_fin'])) ?></td>
            <td style="color:var(--olive-dark);font-weight:700;"><?= $o['prix_journee'] ?> DT</td>
            <td><?= $o['total_candidats'] ?></td>
            <td><span class="badge badge-green"><?= $o['acceptes'] ?>/<?= $o['nombre_ouvriers'] ?></span></td>
            <td><span style="color:#dc3545;"><?= $o['refuses'] ?></span></td>
            <td><?= $o['en_attente'] ?></td>
            <td><?= $o['note_moy'] ? $o['note_moy'].'/10' : '—' ?></td>
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
.stat-val { font-size:2rem; font-weight:700; color:var(--olive-dark); }
.stat-lbl { font-size:0.82rem; color:var(--text-light); margin-top:0.25rem; }
</style>

<?php require_once '../footer.php'; ?>
