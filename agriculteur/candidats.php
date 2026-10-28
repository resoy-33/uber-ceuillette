<?php
require_once 'auth_agriculteur.php';
require_once '../config.php';
$pageTitle = "Liste des candidats";
require_once '../header.php';

$pdo = getDB();
$id_agri = $_SESSION['user'];
$id_offre = intval($_GET['id'] ?? 0);

// Verify offer belongs to this agriculteur
$stmt = $pdo->prepare("SELECT o.*, tf.libelle AS fruit, g.libelle AS gouvernorat FROM offre o JOIN type_fruit tf ON o.id_type_fruit=tf.id_type_fruit JOIN gouvernorat g ON o.id_gouvernorat=g.id_gouvernorat WHERE o.id_offre=? AND o.id_agriculteur=?");
$stmt->execute([$id_offre, $id_agri]);
$offre = $stmt->fetch();

if (!$offre) { echo "<div class='container'><div class='alert alert-error'>Offre introuvable.</div></div>";
   require_once '../footer.php'; 
   exit; }


$stmt = $pdo->prepare("SELECT COUNT(*) FROM candidature WHERE id_offre=? AND decision='accepte'");
$stmt->execute([$id_offre]);
$nb_acceptes = $stmt->fetchColumn();
$is_full = $nb_acceptes >= $offre['nombre_ouvriers'];


if (isset($_GET['action']) && isset($_GET['cid'])) {
    $cid = intval($_GET['cid']);
    $action = $_GET['action'];
    // Get ouvrier id for notification
    $cstmt = $pdo->prepare("SELECT id_ouvrier FROM candidature WHERE id_candidature=?");
    $cstmt->execute([$cid]);
    $cand_row = $cstmt->fetch();
    $ouvrier_notif_id = $cand_row ? $cand_row['id_ouvrier'] : null;

    if ($action === 'accepte' && !$is_full) {
        $stmt = $pdo->prepare("UPDATE candidature SET decision='accepte' WHERE id_candidature=? AND id_offre=?");
        $stmt->execute([$cid, $id_offre]);
        // Notification to ouvrier
        if ($ouvrier_notif_id) {
            $notifMsg = " Votre candidature pour l'offre de {$offre['fruit']} ({$offre['gouvernorat']}) a été acceptée !";
            $pdo->prepare("INSERT INTO notification (destinataire_type, destinataire_id, type, message, lien) VALUES ('ouvrier', ?, 'candidature_acceptee', ?, 'mes_candidatures.php')")->execute([$ouvrier_notif_id, $notifMsg]);
        }
      
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM candidature WHERE id_offre=? AND decision='accepte'");
        $stmt->execute([$id_offre]); 
        $nb_acceptes = $stmt->fetchColumn();
        $is_full = $nb_acceptes >= $offre['nombre_ouvriers'];
        $msg = "Candidat accepté.";
    } elseif ($action === 'refuse') {
        $stmt = $pdo->prepare("UPDATE candidature SET decision='refuse' WHERE id_candidature=? AND id_offre=?");
        $stmt->execute([$cid, $id_offre]);
       
        if ($ouvrier_notif_id) {
            $notifMsg = " Votre candidature pour l'offre de {$offre['fruit']} ({$offre['gouvernorat']}) a été refusée.";
            $pdo->prepare("INSERT INTO notification (destinataire_type, destinataire_id, type, message, lien) VALUES ('ouvrier', ?, 'candidature_refusee', ?, 'mes_candidatures.php')")->execute([$ouvrier_notif_id, $notifMsg]);
        }
        $msg = "Candidat refusé.";
    } elseif ($action === 'accepte' && $is_full) {
        $err = "Le nombre maximum d'ouvriers a été atteint.";
    }
}

$stmt = $pdo->prepare("
  SELECT c.*, ou.nom, ou.prenom, ou.photo, ou.description,
    ROUND(AVG(c2.note), 1) AS moyenne_notes,
    GROUP_CONCAT(c2.commentaire SEPARATOR ' | ') AS anciens_commentaires
  FROM candidature c
  JOIN ouvrier ou ON c.id_ouvrier=ou.id_ouvrier
  LEFT JOIN candidature c2 ON c2.id_ouvrier=ou.id_ouvrier AND c2.note IS NOT NULL AND c2.id_offre!=?
  WHERE c.id_offre=?
  GROUP BY c.id_candidature
  ORDER BY c.date_candidature ASC
");
$stmt->execute([$id_offre, $id_offre]);
$candidats = $stmt->fetchAll();
?>

<div class="container">
  <div style="margin-bottom:1.5rem;">
    <a href="mes_offres.php" style="color:var(--olive);text-decoration:none;">← Retour aux offres</a>
  </div>

  <h1 class="page-title">Candidats pour : <?= $offre['fruit'] ?></h1>
  <p class="page-subtitle">📍 <?= $offre['gouvernorat'] ?> · <?= date('d/m/Y', strtotime($offre['date_debut'])) ?> → <?= date('d/m/Y', strtotime($offre['date_fin'])) ?></p>

  <?php if (isset($msg)): ?><div class="alert alert-success"> <?= $msg ?></div><?php endif; ?>
  <?php if (isset($err)): ?><div class="alert alert-error"><?= $err ?></div><?php endif; ?>

  <div class="card" style="margin-bottom:1.5rem;">
    <div style="display:flex;gap:2rem;flex-wrap:wrap;">
      <div><strong style="color:var(--olive-dark);"><?= $nb_acceptes ?> / <?= $offre['nombre_ouvriers'] ?></strong> <span style="color:var(--text-light);">ouvriers acceptés</span></div>
      <div><?php if ($is_full): ?><span class="badge badge-red">Offre clôturée — quota atteint</span><?php else: ?><span class="badge badge-green">Encore <?= $offre['nombre_ouvriers'] - $nb_acceptes ?> place(s) disponible(s)</span><?php endif; ?></div>
    </div>
  </div>

  <?php if (empty($candidats)): ?>
    <div class="card" style="text-align:center;padding:2rem;">
      <p style="color:var(--text-light);">Aucun candidat pour cette offre.</p>
    </div>
  <?php else: ?>
    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th>Candidat</th>
            <th>Description</th>
            <th>Moyenne</th>
            <th>Date candidature</th>
            <th>Statut</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($candidats as $c): ?>
          <tr>
            <td>
              <div style="display:flex;align-items:center;gap:0.7rem;">
                <?php if ($c['photo']): ?>
                  <img src="data:image/jpeg;base64,<?= base64_encode($c['photo']) ?>" style="width:40px;height:40px;border-radius:50%;object-fit:cover;">
                <?php else: ?>
                  <div style="width:40px;height:40px;border-radius:50%;background:var(--olive);color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;">
                    <?= strtoupper(substr($c['prenom'],0,1)) ?>
                  </div>
                <?php endif; ?>
                <div>
                  <strong><?= $c['prenom'] . ' ' . $c['nom'] ?></strong>
                </div>
              </div>
            </td>
            <td style="max-width:180px;font-size:0.82rem;color:var(--text-light);">
              <?= substr($c['description'] ?? '', 0, 80) ?>
              <?php if ($c['anciens_commentaires']): ?>
                <br><em style="color:var(--olive);">"<?= substr($c['anciens_commentaires'], 0, 60) ?>..."</em>
              <?php endif; ?>
            </td>
            <td>
              <?php if ($c['moyenne_notes']): ?>
                <strong style="color:var(--olive-dark);"><?= $c['moyenne_notes'] ?>/10</strong>
              <?php else: ?>
                <span style="color:var(--text-light);">—</span>
              <?php endif; ?>
            </td>
            <td style="font-size:0.85rem;"><?= date('d/m/Y H:i', strtotime($c['date_candidature'])) ?></td>
            <td>
              <?php if ($c['decision'] === 'accepte'): ?>
                <span class="badge badge-green"> Accepté</span>
              <?php elseif ($c['decision'] === 'refuse'): ?>
                <span class="badge badge-red">Refusé</span>
              <?php else: ?>
                <span class="badge badge-blue"> En cours</span>
              <?php endif; ?>
            </td>
            <td>
              <?php if ($c['decision'] === 'encours'): ?>
                <?php if (!$is_full): ?>
                  <a href="?id=<?= $id_offre ?>&action=accepte&cid=<?= $c['id_candidature'] ?>" class="btn btn-primary btn-sm">Accepter</a>
                <?php endif; ?>
                <a href="?id=<?= $id_offre ?>&action=refuse&cid=<?= $c['id_candidature'] ?>" class="btn btn-danger btn-sm">Refuser</a>
              <?php endif; ?>
              <a href="messages.php?with=<?= $c['id_ouvrier'] ?>" class="btn btn-secondary btn-sm" title="Envoyer un message">💬</a>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>

<?php require_once '../footer.php'; ?>
