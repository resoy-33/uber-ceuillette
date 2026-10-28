<?php
require_once 'auth_ouvrier.php';
require_once '../config.php';
$pageTitle = "Offres de récolte";
require_once '../header.php';

$pdo = getDB();
$id_ouvrier = (int)$_SESSION['user'];

$fruits       = $pdo->query("SELECT * FROM type_fruit ORDER BY libelle")->fetchAll();
$gouvernorats = $pdo->query("SELECT * FROM gouvernorat ORDER BY libelle")->fetchAll();



if (isset($_GET['postuler'])) {
    $id_offre = (int)$_GET['postuler'];

    $stmt = $pdo->prepare("SELECT id_candidature FROM candidature WHERE id_offre=? AND id_ouvrier=?");
    $stmt->execute([$id_offre, $id_ouvrier]);

    if ($stmt->fetch()) {
        $err = "Vous avez déjà postulé à cette offre.";
    } else {

        $stmt = $pdo->prepare("SELECT * FROM offre WHERE id_offre=? AND date_limite >= CURDATE()");
        $stmt->execute([$id_offre]);
        $offreCheck = $stmt->fetch();

        if (!$offreCheck) {
            $err = "Cette offre n'est plus disponible.";
        } else {

            $stmt = $pdo->prepare("SELECT COUNT(*) FROM candidature WHERE id_offre=? AND decision='accepte'");
            $stmt->execute([$id_offre]);

            if ($stmt->fetchColumn() >= $offreCheck['nombre_ouvriers']) {
                $err = "Cette offre est déjà complète.";
            } else {

                $stmt = $pdo->prepare("INSERT INTO candidature (id_offre, id_ouvrier, decision) VALUES (?, ?, 'encours')");
                $stmt->execute([$id_offre, $id_ouvrier]);

                $msg = "Candidature envoyée avec succès !";
            }
        }
    }
}



$f_fruit  = (int)($_GET['fruit'] ?? 0);
$f_gouv   = (int)($_GET['gouv'] ?? 0);
$f_sort   = $_GET['sort'] ?? '';
$f_search = trim($_GET['search'] ?? '');

$where = "WHERE o.date_limite >= CURDATE()
AND (SELECT COUNT(*) 
     FROM candidature 
     WHERE id_offre=o.id_offre AND decision='accepte') < o.nombre_ouvriers";

if ($f_fruit) {
    $where .= " AND o.id_type_fruit=" . $f_fruit;
}

if ($f_gouv) {
    $where .= " AND o.id_gouvernorat=" . $f_gouv;
}

if ($f_search !== '') {
    $escaped = $pdo->quote('%' . $f_search . '%');
    $where .= " AND (tf.libelle LIKE $escaped OR g.libelle LIKE $escaped OR o.adresse LIKE $escaped)";
}



$order = "ORDER BY o.id_offre DESC";

if ($f_sort === 'prix_asc')  $order = "ORDER BY o.prix_journee ASC";
if ($f_sort === 'prix_desc') $order = "ORDER BY o.prix_journee DESC";
if ($f_sort === 'date')      $order = "ORDER BY o.date_debut ASC";



$sql = "
SELECT o.*, tf.libelle AS fruit, g.libelle AS gouvernorat,
  (SELECT COUNT(*) FROM candidature WHERE id_offre=o.id_offre AND decision='accepte') AS nb_acceptes,
  (SELECT id_candidature FROM candidature WHERE id_offre=o.id_offre AND id_ouvrier=$id_ouvrier) AS deja_postule
FROM offre o
JOIN type_fruit tf ON o.id_type_fruit=tf.id_type_fruit
JOIN gouvernorat g ON o.id_gouvernorat=g.id_gouvernorat
$where
$order
";

$stmt = $pdo->query($sql);
$offres = $stmt->fetchAll();
?>

<div class="container">
  <h1 class="page-title">Offres de récolte disponibles</h1>
  <p class="page-subtitle"><?= count($offres) ?> offre(s) trouvée(s)</p>

  <?php if (isset($msg)): ?><div class="alert alert-success"> <?= $msg ?></div><?php endif; ?>
  <?php if (isset($err)): ?><div class="alert alert-error"> <?= $err ?></div><?php endif; ?>


  <form method="GET" class="filter-bar">
    <div class="form-group" style="flex:1 1 200px;">
      <label>🔍 Recherche</label>
      <input type="text" name="search" value="<?= htmlspecialchars($f_search) ?>" placeholder="Fruit, gouvernorat, adresse..." style="width:100%;padding:0.55rem 0.75rem;border:1.5px solid var(--border);border-radius:10px;font-family:inherit;font-size:0.9rem;">
    </div>
    <div class="form-group">
      <label>Fruit</label>
      <select name="fruit">
        <option value="">Tous les fruits</option>
        <?php foreach ($fruits as $f): ?>
          <option value="<?= $f['id_type_fruit'] ?>" <?= $f_fruit == $f['id_type_fruit'] ? 'selected' : '' ?>>
            <?= $f['libelle'] ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="form-group">
      <label>Gouvernorat</label>
      <select name="gouv">
        <option value="">Tous les gouvernorats</option>
        <?php foreach ($gouvernorats as $g): ?>
          <option value="<?= $g['id_gouvernorat'] ?>" <?= $f_gouv == $g['id_gouvernorat'] ? 'selected' : '' ?>>
            <?= $g['libelle'] ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="form-group">
      <label>Trier par</label>
      <select name="sort">
        <option value="">Plus récentes</option>
        <option value="prix_desc" <?= $f_sort === 'prix_desc' ? 'selected' : '' ?>>Prix ↓ (plus élevé)</option>
        <option value="prix_asc"  <?= $f_sort === 'prix_asc'  ? 'selected' : '' ?>>Prix ↑ (moins élevé)</option>
        <option value="date"      <?= $f_sort === 'date'      ? 'selected' : '' ?>>Date de début</option>
      </select>
    </div>
    <div class="form-group" style="display:flex;align-items:flex-end;">
      <button type="submit" class="btn btn-primary">Filtrer</button>
      &nbsp;
      <a href="offres.php" class="btn btn-secondary">✕ Reset</a>
    </div>
  </form>

  <?php if (empty($offres)): ?>
    <div class="card" style="text-align:center;padding:3rem;">
      <div style="font-size:3rem;margin-bottom:1rem;"></div>
      <p style="color:var(--text-light);">Aucune offre disponible avec ces critères.</p>
    </div>
  <?php else: ?>
    <div class="card-grid">
      <?php foreach ($offres as $o):
        $jours = (strtotime($o['date_fin']) - strtotime($o['date_debut'])) / 86400 + 1;
        $total_gain = $jours * $o['prix_journee'];
      ?>
      <div class="offer-card">
        <div class="offer-card-top">
          <div style="display:flex;justify-content:space-between;align-items:start;">
            <div class="offer-card-fruit">🌿 <?= $o['fruit'] ?></div>
            <span class="badge" style="background:rgba(255,255,255,0.25);color:#fff;font-size:0.75rem;">
              <?= $o['nombre_ouvriers'] - $o['nb_acceptes'] ?> place(s)
            </span>
          </div>
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
              <span class="offer-meta-label">Gain total estimé</span>
              <span class="offer-meta-value" style="color:var(--earth);font-weight:700;"><?= number_format($total_gain, 1) ?> DT</span>
            </div>
          </div>
          <div style="font-size:0.8rem;color:var(--text-light);margin-top:0.5rem;">
             Postuler avant le <?= date('d/m/Y', strtotime($o['date_limite'])) ?>
          </div>
        </div>
        <div class="offer-card-actions">
          <?php if ($o['deja_postule']): ?>
            <span class="badge badge-blue"> Déjà postulé</span>
          <?php else: ?>
            <a href="offres.php?postuler=<?= $o['id_offre'] ?><?= $f_fruit ? '&fruit='.$f_fruit : '' ?><?= $f_gouv ? '&gouv='.$f_gouv : '' ?><?= $f_sort ? '&sort='.$f_sort : '' ?>"
               class="btn btn-primary btn-sm"
               onclick="return confirm('Postuler à cette offre ?')">
               Postuler
            </a>
          <?php endif; ?>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>

<?php require_once '../footer.php'; ?>
