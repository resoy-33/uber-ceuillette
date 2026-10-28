<?php
require_once 'auth_ouvrier.php';
require_once '../config.php';
$pageTitle = "Messages";
$basePath = "../";
require_once '../header.php';

$pdo = getDB();
$mon_id   = (int)$_SESSION['user'];
$mon_type = 'ouvrier';


if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['contenu']) && isset($_POST['dest_id'])) {
    $dest_id   = (int)$_POST['dest_id'];
    $dest_type = 'agriculteur';
    $contenu   = trim($_POST['contenu']);
    if ($contenu !== '') {
        $stmt = $pdo->prepare("INSERT INTO message (expediteur_type, expediteur_id, destinataire_type, destinataire_id, contenu) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$mon_type, $mon_id, $dest_type, $dest_id, $contenu]);
        // Notify agriculteur
        $notifMsg = "Nouveau message de " . $_SESSION['prenom'] . " " . $_SESSION['nom'];
        $lien = "messages.php?with=" . $mon_id;
        $stmt2 = $pdo->prepare("INSERT INTO notification (destinataire_type, destinataire_id, type, message, lien) VALUES ('agriculteur', ?, 'message', ?, ?)");
        $stmt2->execute([$dest_id, $notifMsg, $lien]);
        header("Location: messages.php?with=" . $dest_id);
        exit;
    }
}


$contacts_stmt = $pdo->prepare("
    SELECT DISTINCT 
        CASE WHEN m.expediteur_type='agriculteur' THEN m.expediteur_id ELSE m.destinataire_id END AS agri_id,
        (SELECT CONCAT(nom,' ',prenom) FROM agriculteur WHERE id_agriculteur = CASE WHEN m.expediteur_type='agriculteur' THEN m.expediteur_id ELSE m.destinataire_id END) AS nom_agri,
        (SELECT COUNT(*) FROM message WHERE lu=0 AND expediteur_type='agriculteur' AND expediteur_id=(CASE WHEN m.expediteur_type='agriculteur' THEN m.expediteur_id ELSE m.destinataire_id END) AND destinataire_type='ouvrier' AND destinataire_id=?) AS non_lus,
        (SELECT date_envoi FROM message WHERE ((expediteur_type='ouvrier' AND expediteur_id=? AND destinataire_type='agriculteur' AND destinataire_id=CASE WHEN m.expediteur_type='agriculteur' THEN m.expediteur_id ELSE m.destinataire_id END) OR (expediteur_type='agriculteur' AND expediteur_id=CASE WHEN m.expediteur_type='agriculteur' THEN m.expediteur_id ELSE m.destinataire_id END AND destinataire_type='ouvrier' AND destinataire_id=?)) ORDER BY date_envoi DESC LIMIT 1) AS dernier_msg
    FROM message m
    WHERE (m.expediteur_type='ouvrier' AND m.expediteur_id=? AND m.destinataire_type='agriculteur')
       OR (m.destinataire_type='ouvrier' AND m.destinataire_id=? AND m.expediteur_type='agriculteur')
    ORDER BY dernier_msg DESC
");
$contacts_stmt->execute([$mon_id, $mon_id, $mon_id, $mon_id, $mon_id]);
$contacts = $contacts_stmt->fetchAll();


$agri_stmt = $pdo->prepare("
    SELECT DISTINCT a.id_agriculteur, CONCAT(a.nom,' ',a.prenom) AS nom_agri
    FROM candidature c
    JOIN offre o ON c.id_offre=o.id_offre
    JOIN agriculteur a ON o.id_agriculteur=a.id_agriculteur
    WHERE c.id_ouvrier=?
");
$agri_stmt->execute([$mon_id]);
$agriculteurs = $agri_stmt->fetchAll(PDO::FETCH_KEY_PAIR);

$contact_ids = array_column($contacts, 'agri_id');
foreach ($agriculteurs as $aid => $nom) {
    if (!in_array($aid, $contact_ids)) {
        $contacts[] = ['agri_id' => $aid, 'nom_agri' => $nom, 'non_lus' => 0, 'dernier_msg' => null];
    }
}

$with_id = isset($_GET['with']) ? (int)$_GET['with'] : ($contacts[0]['agri_id'] ?? null);

$messages = [];
$with_nom  = '';
if ($with_id) {
    $pdo->prepare("UPDATE message SET lu=1 WHERE expediteur_type='agriculteur' AND expediteur_id=? AND destinataire_type='ouvrier' AND destinataire_id=? AND lu=0")->execute([$with_id, $mon_id]);

    $with_nom = $agriculteurs[$with_id] ?? '';
    foreach ($contacts as $c) {
        if ($c['agri_id'] == $with_id) { $with_nom = $c['nom_agri']; break; }
    }

    $stmt = $pdo->prepare("
        SELECT * FROM message
        WHERE ((expediteur_type='ouvrier' AND expediteur_id=? AND destinataire_type='agriculteur' AND destinataire_id=?)
            OR (expediteur_type='agriculteur' AND expediteur_id=? AND destinataire_type='ouvrier' AND destinataire_id=?))
        ORDER BY date_envoi ASC
    ");
    $stmt->execute([$mon_id, $with_id, $with_id, $mon_id]);
    $messages = $stmt->fetchAll();
}
?>

<div class="container">
  <h1 class="page-title">💬 Messages</h1>
  <p class="page-subtitle">Échangez avec les agriculteurs</p>

  <div class="messages-layout">
    <div class="messages-sidebar">
      <?php if (empty($contacts)): ?>
        <p style="color:var(--text-light);padding:1rem;font-size:0.9rem;">Aucune conversation pour l'instant.</p>
      <?php else: ?>
        <?php foreach ($contacts as $c): ?>
          <a href="messages.php?with=<?= $c['agri_id'] ?>"
             class="contact-item <?= $with_id == $c['agri_id'] ? 'active' : '' ?>">
            <div class="contact-avatar"><?= strtoupper(substr($c['nom_agri'], 0, 1)) ?></div>
            <div class="contact-info">
              <div class="contact-name"><?= htmlspecialchars($c['nom_agri']) ?></div>
              <?php if ($c['dernier_msg']): ?>
                <div class="contact-time"><?= date('d/m H:i', strtotime($c['dernier_msg'])) ?></div>
              <?php endif; ?>
            </div>
            <?php if ($c['non_lus'] > 0): ?>
              <span class="unread-badge"><?= $c['non_lus'] ?></span>
            <?php endif; ?>
          </a>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>

    <div class="messages-chat">
      <?php if ($with_id && $with_nom): ?>
        <div class="chat-header">
          <div class="contact-avatar" style="width:36px;height:36px;font-size:0.9rem;"><?= strtoupper(substr($with_nom, 0, 1)) ?></div>
          <strong><?= htmlspecialchars($with_nom) ?></strong>
        </div>
        <div class="chat-messages" id="chat-messages">
          <?php if (empty($messages)): ?>
            <p style="text-align:center;color:var(--text-light);margin-top:2rem;">Commencez la conversation !</p>
          <?php else: ?>
            <?php foreach ($messages as $m): ?>
              <div class="msg-bubble <?= ($m['expediteur_type'] === 'ouvrier') ? 'msg-me' : 'msg-other' ?>">
                <div class="msg-text"><?= nl2br(htmlspecialchars($m['contenu'])) ?></div>
                <div class="msg-time"><?= date('d/m H:i', strtotime($m['date_envoi'])) ?></div>
              </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
        <form method="POST" class="chat-form">
          <input type="hidden" name="dest_id" value="<?= $with_id ?>">
          <textarea name="contenu" class="chat-input" placeholder="Écrire un message..." rows="2" required></textarea>
          <button type="submit" class="btn btn-primary chat-send">Envoyer</button>
        </form>
      <?php else: ?>
        <div style="display:flex;align-items:center;justify-content:center;height:100%;color:var(--text-light);">
          Sélectionnez une conversation
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<style>
.messages-layout { display:grid; grid-template-columns:280px 1fr; gap:0; height:520px; border:1.5px solid var(--border); border-radius:var(--radius); overflow:hidden; background:#fff; }
.messages-sidebar { border-right:1.5px solid var(--border); overflow-y:auto; }
.contact-item { display:flex; align-items:center; gap:0.75rem; padding:1rem; text-decoration:none; color:var(--text-dark); border-bottom:1px solid var(--border); transition:background 0.15s; position:relative; }
.contact-item:hover, .contact-item.active { background:var(--cream); }
.contact-avatar { width:42px; height:42px; border-radius:50%; background:var(--olive); color:#fff; display:flex; align-items:center; justify-content:center; font-weight:700; font-size:1.1rem; flex-shrink:0; }
.contact-name { font-weight:600; font-size:0.9rem; }
.contact-time { font-size:0.75rem; color:var(--text-light); }
.unread-badge { background:var(--olive); color:#fff; border-radius:50%; width:20px; height:20px; display:flex; align-items:center; justify-content:center; font-size:0.72rem; font-weight:700; margin-left:auto; }
.messages-chat { display:flex; flex-direction:column; }
.chat-header { display:flex; align-items:center; gap:0.75rem; padding:0.75rem 1rem; border-bottom:1.5px solid var(--border); background:var(--cream); font-size:0.95rem; }
.chat-messages { flex:1; overflow-y:auto; padding:1rem; display:flex; flex-direction:column; gap:0.6rem; }
.msg-bubble { max-width:72%; display:flex; flex-direction:column; }
.msg-me { align-self:flex-end; align-items:flex-end; }
.msg-other { align-self:flex-start; align-items:flex-start; }
.msg-text { padding:0.6rem 0.9rem; border-radius:16px; font-size:0.9rem; line-height:1.45; }
.msg-me .msg-text { background:var(--olive); color:#fff; border-bottom-right-radius:4px; }
.msg-other .msg-text { background:var(--cream); color:var(--text-dark); border-bottom-left-radius:4px; }
.msg-time { font-size:0.7rem; color:var(--text-light); margin-top:2px; }
.chat-form { display:flex; gap:0.5rem; padding:0.75rem; border-top:1.5px solid var(--border); }
.chat-input { flex:1; resize:none; padding:0.55rem 0.75rem; border:1.5px solid var(--border); border-radius:10px; font-family:inherit; font-size:0.9rem; }
.chat-send { flex-shrink:0; align-self:flex-end; }
@media(max-width:640px){ .messages-layout{grid-template-columns:1fr;height:auto;} .messages-sidebar{height:150px;} }
</style>

<script>
const chatBox = document.getElementById('chat-messages');
if (chatBox) chatBox.scrollTop = chatBox.scrollHeight;
</script>

<?php require_once '../footer.php'; ?>
