<?php
require_once 'auth_ouvrier.php';
require_once '../config.php';
$pageTitle = "Notifications";
$basePath = "../";
require_once '../header.php';

$pdo = getDB();
$mon_id = (int)$_SESSION['user'];

if (isset($_GET['mark_all'])) {
    $pdo->prepare("UPDATE notification SET lu=1 WHERE destinataire_type='ouvrier' AND destinataire_id=?")->execute([$mon_id]);
    header("Location: notifications.php");
    exit;
}

if (isset($_GET['read'])) {
    $nid = (int)$_GET['read'];
    $pdo->prepare("UPDATE notification SET lu=1 WHERE id_notification=? AND destinataire_type='ouvrier' AND destinataire_id=?")->execute([$nid, $mon_id]);
    $n = $pdo->prepare("SELECT lien FROM notification WHERE id_notification=?");
    $n->execute([$nid]);
    $row = $n->fetch();
    if ($row && $row['lien']) {
        header("Location: " . $row['lien']);
        exit;
    }
    header("Location: notifications.php");
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM notification WHERE destinataire_type='ouvrier' AND destinataire_id=? ORDER BY date_creation DESC LIMIT 50");
$stmt->execute([$mon_id]);
$notifs = $stmt->fetchAll();

$non_lus = array_filter($notifs, fn($n) => !$n['lu']);

$type_icons = [
    'candidature_acceptee' => '✅',
    'candidature_refusee'  => '❌',
    'nouvelle_candidature' => '📋',
    'message'              => '💬',
];
?>

<div class="container">
  <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:1rem;margin-bottom:2rem;">
    <div>
      <h1 class="page-title">🔔 Notifications</h1>
      <p class="page-subtitle"><?= count($non_lus) ?> non lue(s)</p>
    </div>
    <?php if ($non_lus): ?>
      <a href="notifications.php?mark_all=1" class="btn btn-secondary btn-sm">Tout marquer comme lu</a>
    <?php endif; ?>
  </div>

  <?php if (empty($notifs)): ?>
    <div class="card" style="text-align:center;padding:3rem;">
      <div style="font-size:3rem;margin-bottom:1rem;">🔔</div>
      <p style="color:var(--text-light);">Aucune notification pour l'instant.</p>
    </div>
  <?php else: ?>
    <div style="display:flex;flex-direction:column;gap:0.5rem;">
      <?php foreach ($notifs as $n):
        $icon = $type_icons[$n['type']] ?? '📌';
      ?>
      <a href="notifications.php?read=<?= $n['id_notification'] ?>" class="notif-item <?= $n['lu'] ? '' : 'notif-unread' ?>" style="text-decoration:none;">
        <span class="notif-icon"><?= $icon ?></span>
        <div class="notif-body">
          <div class="notif-msg"><?= htmlspecialchars($n['message']) ?></div>
          <div class="notif-time"><?= date('d/m/Y à H:i', strtotime($n['date_creation'])) ?></div>
        </div>
        <?php if (!$n['lu']): ?>
          <span class="notif-dot"></span>
        <?php endif; ?>
      </a>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>

<style>
.notif-item { display:flex; align-items:center; gap:1rem; padding:1rem 1.25rem; border-radius:var(--radius); border:1.5px solid var(--border); color:var(--text-dark); background:#fff; transition:background 0.15s; }
.notif-item:hover { background:var(--cream); }
.notif-unread { border-left:4px solid var(--olive); background:var(--warm-white); }
.notif-icon { font-size:1.5rem; flex-shrink:0; }
.notif-body { flex:1; }
.notif-msg { font-size:0.92rem; font-weight:500; }
.notif-time { font-size:0.78rem; color:var(--text-light); margin-top:2px; }
.notif-dot { width:10px; height:10px; border-radius:50%; background:var(--olive); flex-shrink:0; }
</style>

<?php require_once '../footer.php'; ?>
