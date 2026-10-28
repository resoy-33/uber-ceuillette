<?php
require_once 'auth_ouvrier.php';
require_once '../config.php';
$pageTitle = "Mes candidatures";
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
  WHERE c.id_ouvrier=?
  ORDER BY c.date_candidature DESC
");
$stmt->execute([$id]);
$candidatures = $stmt->fetchAll();
?>

<div class="container">
  <h1 class="page-title">Mes candidatures</h1>
  <p class="page-subtitle"><?= count($candidatures) ?> candidature(s) au total</p>

  <?php if (empty($candidatures)): ?>
    <div class="card" style="text-align:center;padding:3rem;">
      <div style="font-size:3rem;margin-bottom:1rem;"></div>
      <p style="color:var(--text-light);">Vous n'avez pas encore postulé à des offres.</p>
      <a href="offres.php" class="btn btn-primary" style="margin-top:1rem;">Voir les offres disponibles</a>
    </div>
  <?php else: ?>
    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th>Offre</th>
            <th>Lieu</th>
            <th>Période</th>
            <th>Prix/jour</th>
            <th>Agriculteur</th>
            <th>Date de candidature</th>
            <th>Décision</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($candidatures as $c): ?>
          <tr>
            <td><strong style="color:var(--olive-dark);"> <?= $c['fruit'] ?></strong></td>
            <td><?= $c['gouvernorat'] ?><br><small style="color:var(--text-light);"><?= $c['adresse_site'] ?></small></td>
            <td style="font-size:0.85rem;"><?= date('d/m/Y', strtotime($c['date_debut'])) ?><br>→ <?= date('d/m/Y', strtotime($c['date_fin'])) ?></td>
            <td style="font-weight:700;color:var(--olive-dark);"><?= $c['prix_journee'] ?> DT</td>
            <td><?= $c['agri_prenom'] . ' ' . $c['agri_nom'] ?></td>
            <td style="font-size:0.82rem;"><?= date('d/m/Y', strtotime($c['date_candidature'])) ?></td>
            <td>
              <?php if ($c['decision'] === 'accepte'): ?>
                <span class="badge badge-green"> Accepté</span>
              <?php elseif ($c['decision'] === 'refuse'): ?>
                <span class="badge badge-red"> Refusé</span>
              <?php else: ?>
                <span class="badge badge-blue"> En cours</span>
              <?php endif; ?>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>

<?php require_once '../footer.php'; ?>
