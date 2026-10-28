<?php
require_once 'auth_agriculteur.php';
require_once '../config.php';
$pageTitle = "Mes offres";
require_once '../header.php';

$pdo = getDB();
$id = $_SESSION['user'];

// Delete offer
if (isset($_GET['delete'])) {
    $oid = intval($_GET['delete']);
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM candidature WHERE id_offre=?");
    $stmt->execute([$oid]);
    if ($stmt->fetchColumn() == 0) {
        $stmt = $pdo->prepare("DELETE FROM offre WHERE id_offre=? AND id_agriculteur=?");
        $stmt->execute([$oid, $id]);
        $msg = "Offre supprimée.";
    } else {
        $err = "Impossible de supprimer : des candidats ont déjà postulé.";
    }
}

$f_search = trim($_GET['search'] ?? '');
$f_fruit  = (int)($_GET['fruit'] ?? 0);
$f_status = $_GET['status'] ?? '';

$where = "WHERE o.id_agriculteur=?";
$params = [$id];

if ($f_search !== '') {
    $where .= " AND (tf.libelle LIKE ? OR g.libelle LIKE ? OR o.adresse LIKE ?)";
    $params[] = '%'.$f_search.'%';
    $params[] = '%'.$f_search.'%';
    $params[] = '%'.$f_search.'%';
}
if ($f_fruit) {
    $where .= " AND o.id_type_fruit=?";
    $params[] = $f_fruit;
}
if ($f_status === 'actif') {
    $where .= " AND o.date_limite >= CURDATE()";
} elseif ($f_status === 'termine') {
    $where .= " AND o.date_fin < CURDATE()";
}

$fruits_list = $pdo->query("SELECT * FROM type_fruit ORDER BY libelle")->fetchAll();

$stmt = $pdo->prepare("
  SELECT o.*, tf.libelle AS fruit, g.libelle AS gouvernorat,
    (SELECT COUNT(*) FROM candidature WHERE id_offre=o.id_offre AND decision='accepte') AS nb_acceptes,
    (SELECT COUNT(*) FROM candidature WHERE id_offre=o.id_offre AND decision='encours') AS nb_encours,
    (SELECT COUNT(*) FROM candidature WHERE id_offre=o.id_offre) AS total_candidats
  FROM offre o
  JOIN type_fruit tf ON o.id_type_fruit=tf.id_type_fruit
  JOIN gouvernorat g ON o.id_gouvernorat=g.id_gouvernorat
  $where
  ORDER BY o.id_offre DESC
");
$stmt->execute($params);
$offres = $stmt->fetchAll();
?>

<div class="container">
  <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:1rem;margin-bottom:2rem;">
    <div>
      <h1 class="page-title">Mes offres de récolte</h1>
      <p class="page-subtitle"><?= count($offres) ?> offre(s) publiée(s)</p>
    </div>
    <a href="ajouter_offre.php" class="btn btn-primary">+ Nouvelle offre</a>
  </div>

  <?php if (isset($msg)): ?><div class="alert alert-success"> <?= $msg ?></div><?php endif; ?>
  <?php if (isset($err)): ?><div class="alert alert-error"> <?= $err ?></div><?php endif; ?>

  <!-- Search and filter bar -->
  <form method="GET" style="display:flex;gap:0.75rem;flex-wrap:wrap;margin-bottom:1.5rem;align-items:flex-end;">
    <div class="form-group" style="flex:1 1 200px;margin:0;">
      <label style="font-size:0.82rem;font-weight:600;color:var(--text-mid);">🔍 Recherche</label>
      <input type="text" name="search" value="<?= htmlspecialchars($f_search) ?>" placeholder="Fruit, gouvernorat, adresse..." style="width:100%;padding:0.5rem 0.75rem;border:1.5px solid var(--border);border-radius:10px;font-family:inherit;font-size:0.9rem;">
    </div>
    <div class="form-group" style="margin:0;">
      <label style="font-size:0.82rem;font-weight:600;color:var(--text-mid);">Fruit</label>
      <select name="fruit" style="padding:0.5rem 0.75rem;border:1.5px solid var(--border);border-radius:10px;font-family:inherit;font-size:0.9rem;">
        <option value="">Tous</option>
        <?php foreach ($fruits_list as $f): ?>
          <option value="<?= $f['id_type_fruit'] ?>" <?= $f_fruit == $f['id_type_fruit'] ? 'selected' : '' ?>><?= $f['libelle'] ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="form-group" style="margin:0;">
      <label style="font-size:0.82rem;font-weight:600;color:var(--text-mid);">Statut</label>
      <select name="status" style="padding:0.5rem 0.75rem;border:1.5px solid var(--border);border-radius:10px;font-family:inherit;font-size:0.9rem;">
        <option value="">Tous</option>
        <option value="actif" <?= $f_status === 'actif' ? 'selected' : '' ?>>Actifs</option>
        <option value="termine" <?= $f_status === 'termine' ? 'selected' : '' ?>>Terminés</option>
      </select>
    </div>
    <button type="submit" class="btn btn-primary btn-sm">Filtrer</button>
    <a href="mes_offres.php" class="btn btn-secondary btn-sm">✕ Reset</a>
  </form>

  <?php if (empty($offres)): ?>
    <div class="card" style="text-align:center;padding:3rem;">
      <div style="font-size:3rem;margin-bottom:1rem;"></div>
      <p style="color:var(--text-light);">Aucune offre publiée pour l'instant.</p>
      <a href="ajouter_offre.php" class="btn btn-primary" style="margin-top:1rem;">Créer ma première offre</a>
    </div>
  <?php else: ?>
    <div class="card-grid">
      <?php foreach ($offres as $o):
        $is_closed = $o['date_limite'] < date('Y-m-d') || $o['nb_acceptes'] >= $o['nombre_ouvriers'];
        $is_past   = $o['date_fin'] < date('Y-m-d');
      ?>
      <div class="offer-card">
        <div class="offer-card-top" style="<?= $is_closed ? 'filter:grayscale(40%)' : '' ?>">
          <div style="display:flex;justify-content:space-between;align-items:start;">
            <div class="offer-card-fruit">🌿 <?= $o['fruit'] ?></div>
            <?php if ($is_past): ?>
              <span class="badge badge-red">Terminé</span>
            <?php elseif ($is_closed): ?>
              <span class="badge" style="background:rgba(255,255,255,0.2);color:#fff;">Clôturé</span>
            <?php else: ?>
              <span class="badge badge-green">Actif</span>
            <?php endif; ?>
          </div>
          <div class="offer-card-loc">📍 <?= $o['gouvernorat'] ?> — <?= $o['adresse'] ?></div>
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
            <div class="offer-meta-item">
              <span class="offer-meta-label">Acceptés</span>
              <span class="offer-meta-value"><?= $o['nb_acceptes'] ?> / <?= $o['nombre_ouvriers'] ?></span>
            </div>
            <div class="offer-meta-item">
              <span class="offer-meta-label">En attente</span>
              <span class="offer-meta-value"><?= $o['nb_encours'] ?></span>
            </div>
          </div>
          <div style="font-size:0.8rem;color:var(--text-light);">
             Date limite : <?= date('d/m/Y', strtotime($o['date_limite'])) ?>
          </div>
        </div>
        <div class="offer-card-actions">
          <a href="candidats.php?id=<?= $o['id_offre'] ?>" class="btn btn-primary btn-sm">
             Candidats (<?= $o['total_candidats'] ?>)
          </a>
          <?php if ($is_past): ?>
            <a href="evaluer.php?id=<?= $o['id_offre'] ?>" class="btn btn-secondary btn-sm"> Évaluer</a>
          <?php endif; ?>
          <?php if ($o['total_candidats'] == 0): ?>
            <a href="mes_offres.php?delete=<?= $o['id_offre'] ?>"
               class="btn btn-danger btn-sm"
               onclick="return confirm('Supprimer cette offre ?')">🗑</a>
          <?php endif; ?>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>

<?php require_once '../footer.php'; ?>
