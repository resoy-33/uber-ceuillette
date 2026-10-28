<?php
require_once 'auth_agriculteur.php';
require_once '../config.php';
$pageTitle = "Évaluer les ouvriers";
require_once '../header.php';

$pdo = getDB();
$id_agri = $_SESSION['user'];
$id_offre = intval($_GET['id'] ?? 0);


$stmt = $pdo->prepare("SELECT o.*, tf.libelle AS fruit, g.libelle AS gouvernorat 
                       FROM offre o 
                       JOIN type_fruit tf ON o.id_type_fruit=tf.id_type_fruit 
                       JOIN gouvernorat g ON o.id_gouvernorat=g.id_gouvernorat 
                       WHERE o.id_offre=? AND o.id_agriculteur=?");
$stmt->execute([$id_offre, $id_agri]);
$offre = $stmt->fetch();

if (!$offre) {
    echo "<div class='container'><div class='alert alert-error'>Offre introuvable.</div></div>";
    require_once '../footer.php'; exit;
}

$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cid   = intval($_POST['id_candidature'] ?? 0);
    $note  = intval($_POST['note'] ?? 0);
    $comm  = trim($_POST['commentaire'] ?? '');
    $renum = floatval($_POST['remuneration'] ?? 0);

    if ($note < 0 || $note > 10) { $err = "La note doit être entre 0 et 10."; }
    else {
        $stmt = $pdo->prepare("UPDATE candidature SET note=?, commentaire=?, remuneration=? WHERE id_candidature=? AND id_offre=?");
        $stmt->execute([$note, $comm, $renum, $cid, $id_offre]);
        $msg = "Évaluation enregistrée.";
    }
}


$stmt = $pdo->prepare("
  SELECT c.*, ou.nom, ou.prenom, ou.photo
  FROM candidature c
  JOIN ouvrier ou ON c.id_ouvrier=ou.id_ouvrier
  WHERE c.id_offre=? AND c.decision='accepte'
  ORDER BY ou.nom
");
$stmt->execute([$id_offre]);
$ouvriers = $stmt->fetchAll();
?>

<div class="container">
  <div style="margin-bottom:1.5rem;">
    <a href="mes_offres.php" style="color:var(--olive);text-decoration:none;">← Retour aux offres</a>
  </div>

  <h1 class="page-title">Évaluer les ouvriers</h1>
  <p class="page-subtitle">🌿 <?= $offre['fruit'] ?> — <?= $offre['gouvernorat'] ?> | <?= date('d/m/Y', strtotime($offre['date_debut'])) ?> → <?= date('d/m/Y', strtotime($offre['date_fin'])) ?></p>

  <?php if ($msg): ?><div class="alert alert-success"> <?= $msg ?></div><?php endif; ?>
  <?php if (isset($err)): ?><div class="alert alert-error"> <?= $err ?></div><?php endif; ?>

  <?php if (empty($ouvriers)): ?>
    <div class="card" style="text-align:center;padding:2rem;">
      <p style="color:var(--text-light);">Aucun ouvrier accepté pour cette offre.</p>
    </div>
  <?php else: ?>
    <div class="card-grid">
      <?php foreach ($ouvriers as $ov): ?>
      <div class="card">
        <div style="display:flex;align-items:center;gap:1rem;margin-bottom:1.2rem;">
          <?php if ($ov['photo']): ?>
            <img src="data:image/jpeg;base64,<?= base64_encode($ov['photo']) ?>" style="width:55px;height:55px;border-radius:50%;object-fit:cover;border:2px solid var(--border);">
          <?php else: ?>
            <div style="width:55px;height:55px;border-radius:50%;background:var(--olive);color:#fff;display:flex;align-items:center;justify-content:center;font-size:1.4rem;font-family:'Playfair Display',serif;">
              <?= strtoupper(substr($ov['prenom'],0,1)) ?>
            </div>
          <?php endif; ?>
          <div>
            <strong style="font-size:1rem;"><?= $ov['prenom'] . ' ' . $ov['nom'] ?></strong>
            <?php if ($ov['note'] !== null): ?>
              <br><span class="badge badge-olive">Note : <?= $ov['note'] ?>/10</span>
            <?php endif; ?>
          </div>
        </div>

        <form method="POST" data-validate>
          <input type="hidden" name="id_candidature" value="<?= $ov['id_candidature'] ?>">

          <div class="form-group">
            <label>Note (0-10)</label>
            <div class="star-rating" style="display:flex;gap:4px;margin-bottom:0.5rem;">
              <?php for ($i=1; $i<=10; $i++): ?>
                <span class="star <?= ($ov['note'] !== null && $i <= $ov['note']) ? 'active' : '' ?>" style="font-size:1.3rem;cursor:pointer;color:<?= ($ov['note'] !== null && $i <= $ov['note']) ? '#f0ad4e' : '#ddd' ?>;">★</span>
              <?php endfor; ?>
              <input type="hidden" name="note" value="<?= $ov['note'] ?? 0 ?>">
            </div>
            <input type="number" name="note" min="0" max="10" value="<?= $ov['note'] ?? '' ?>" placeholder="Note de 0 à 10" style="width:100%;">
            <span class="error-msg"></span>
          </div>

          <div class="form-group">
            <label>Commentaire</label>
            <textarea name="commentaire" rows="2" placeholder="Qualité du travail, ponctualité..."><?= htmlspecialchars($ov['commentaire'] ?? '') ?></textarea>
            <span class="error-msg"></span>
          </div>

          <div class="form-group">
            <label>Rémunération accordée (DT)</label>
            <input type="number" name="remuneration" step="0.5" min="0" value="<?= $ov['remuneration'] ?? '' ?>" placeholder="Montant total payé">
            <span class="error-msg"></span>
          </div>

          <button type="submit" class="btn btn-primary btn-full btn-sm">
            <?= $ov['note'] !== null ? ' Modifier' : ' Enregistrer' ?>
          </button>
        </form>
      </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>

<?php require_once '../footer.php'; ?>
