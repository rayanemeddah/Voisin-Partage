<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

exigerConnexion();
$pageTitle = 'Messagerie — VoisinPartage';
$pdo    = getDB();
$userId = (int)$_SESSION['user_id'];

// Interlocuteur sélectionné
$interlocuteurId = (int)($_GET['interlocuteur'] ?? 0);
$annonceRef      = (int)($_GET['annonce']       ?? 0);

// Envoi d'un message
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifierTokenCSRF($_POST['csrf_token'] ?? '')) {
        redirigerAvecMessage('messagerie.php', 'Token invalide.', 'danger');
    }
    $dest    = (int)($_POST['id_destinataire'] ?? 0);
    $contenu = trim($_POST['contenu'] ?? '');

    if ($dest > 0 && $dest !== $userId && !empty($contenu)) {
        // Vérifier que le destinataire existe et est actif
        $check = $pdo->prepare('SELECT id_utilisateur FROM utilisateurs WHERE id_utilisateur = ? AND actif = 1');
        $check->execute([$dest]);
        if ($check->fetch()) {
            $ins = $pdo->prepare('INSERT INTO messages (id_expediteur, id_destinataire, contenu) VALUES (?, ?, ?)');
            $ins->execute([$userId, $dest, $contenu]);
        }
    }
    header("Location: messagerie.php?interlocuteur={$dest}");
    exit;
}

// Liste des conversations (interlocuteurs distincts)
$stmtConv = $pdo->prepare("
    SELECT
        u.id_utilisateur, u.prenom, u.nom, u.photo_profil, u.quartier,
        (SELECT contenu FROM messages
         WHERE (id_expediteur = u.id_utilisateur AND id_destinataire = :uid1)
            OR (id_expediteur = :uid2 AND id_destinataire = u.id_utilisateur)
         ORDER BY date_envoi DESC LIMIT 1) AS dernier_message,
        (SELECT date_envoi FROM messages
         WHERE (id_expediteur = u.id_utilisateur AND id_destinataire = :uid3)
            OR (id_expediteur = :uid4 AND id_destinataire = u.id_utilisateur)
         ORDER BY date_envoi DESC LIMIT 1) AS date_dernier,
        (SELECT COUNT(*) FROM messages
         WHERE id_expediteur = u.id_utilisateur AND id_destinataire = :uid5 AND lu = 0) AS non_lus
    FROM utilisateurs u
    WHERE u.id_utilisateur IN (
        SELECT DISTINCT IF(id_expediteur = :uid6, id_destinataire, id_expediteur)
        FROM messages
        WHERE id_expediteur = :uid7 OR id_destinataire = :uid8
    )
    ORDER BY date_dernier DESC
");
$stmtConv->execute([
    ':uid1' => $userId, ':uid2' => $userId, ':uid3' => $userId,
    ':uid4' => $userId, ':uid5' => $userId, ':uid6' => $userId,
    ':uid7' => $userId, ':uid8' => $userId,
]);
$conversations = $stmtConv->fetchAll();

// Si on arrive via une annonce et qu'il n'y a pas encore de conversation
if ($annonceRef > 0 && $interlocuteurId > 0) {
    $exists = false;
    foreach ($conversations as $conv) {
        if ((int)$conv['id_utilisateur'] === $interlocuteurId) { $exists = true; break; }
    }
    if (!$exists) {
        $stmtNewUser = $pdo->prepare('SELECT * FROM utilisateurs WHERE id_utilisateur = ? AND actif = 1');
        $stmtNewUser->execute([$interlocuteurId]);
        $newUser = $stmtNewUser->fetch();
        if ($newUser) {
            array_unshift($conversations, array_merge($newUser, [
                'dernier_message' => null,
                'date_dernier'    => null,
                'non_lus'         => 0,
            ]));
        }
    }
}

// Messages de la conversation active
$messages         = [];
$interlocuteur    = null;
$annonceMessage   = null;

if ($interlocuteurId > 0) {
    $stmtInter = $pdo->prepare('SELECT * FROM utilisateurs WHERE id_utilisateur = ? AND actif = 1');
    $stmtInter->execute([$interlocuteurId]);
    $interlocuteur = $stmtInter->fetch();

    if ($interlocuteur) {
        // Marquer comme lus
        $pdo->prepare('UPDATE messages SET lu = 1 WHERE id_expediteur = ? AND id_destinataire = ?')
            ->execute([$interlocuteurId, $userId]);

        // Charger les messages
        $stmtMsg = $pdo->prepare("
            SELECT m.*, u.prenom, u.nom, u.photo_profil
            FROM messages m
            JOIN utilisateurs u ON m.id_expediteur = u.id_utilisateur
            WHERE (m.id_expediteur = :uid1 AND m.id_destinataire = :inter1)
               OR (m.id_expediteur = :inter2 AND m.id_destinataire = :uid2)
            ORDER BY m.date_envoi ASC
        ");
        $stmtMsg->execute([
            ':uid1' => $userId, ':inter1' => $interlocuteurId,
            ':inter2' => $interlocuteurId, ':uid2' => $userId,
        ]);
        $messages = $stmtMsg->fetchAll();

        // Infos annonce de référence
        if ($annonceRef > 0) {
            $stmtA = $pdo->prepare('SELECT id_annonce, titre FROM annonces WHERE id_annonce = ?');
            $stmtA->execute([$annonceRef]);
            $annonceMessage = $stmtA->fetch();
        }
    }
}

require_once '../includes/header.php';
?>

<div class="messagerie-layout">
    <!-- LISTE CONVERSATIONS -->
    <aside class="conversations-sidebar">
        <div class="conv-header">
            <h2><i class="fa-solid fa-envelope"></i> Messagerie</h2>
        </div>

        <?php if (empty($conversations)): ?>
            <div class="conv-empty">
                <i class="fa-solid fa-comment-slash"></i>
                <p>Aucune conversation.<br>Contactez un voisin depuis une annonce !</p>
                <a href="../annonces/liste.php" class="btn btn-sm btn-primary">Voir les annonces</a>
            </div>
        <?php else: ?>
            <ul class="conv-list">
                <?php foreach ($conversations as $conv): ?>
                    <?php
                    $isActive = (int)$conv['id_utilisateur'] === $interlocuteurId;
                    ?>
                    <li class="conv-item <?= $isActive ? 'active' : '' ?>
                                         <?= $conv['non_lus'] > 0 ? 'has-unread' : '' ?>">
                        <a href="messagerie.php?interlocuteur=<?= $conv['id_utilisateur'] ?>">
                            <img src="<?= imgProfil($conv['photo_profil']) ?>"
                                 alt="<?= e($conv['prenom']) ?>"
                                 class="conv-avatar"
                                 onerror="this.src='/voisinpartage/assets/images/default.png'">
                            <div class="conv-info">
                                <div class="conv-name">
                                    <?= e($conv['prenom']) ?> <?= e($conv['nom']) ?>
                                    <?php if ($conv['non_lus'] > 0): ?>
                                        <span class="badge"><?= $conv['non_lus'] ?></span>
                                    <?php endif; ?>
                                </div>
                                <?php if ($conv['dernier_message']): ?>
                                    <div class="conv-preview">
                                        <?= e(mb_substr($conv['dernier_message'], 0, 40)) ?>...
                                    </div>
                                    <div class="conv-date">
                                        <?= formaterDate($conv['date_dernier']) ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </aside>

    <!-- ZONE DE CONVERSATION -->
    <div class="chat-main">
        <?php if ($interlocuteur): ?>
            <!-- Header chat -->
            <div class="chat-header">
                <img src="<?= imgProfil($interlocuteur['photo_profil']) ?>"
                     alt="<?= e($interlocuteur['prenom']) ?>"
                     class="chat-header-avatar"
                     onerror="this.src='/voisinpartage/assets/images/default.png'">
                <div>
                    <strong><?= e($interlocuteur['prenom']) ?> <?= e($interlocuteur['nom']) ?></strong>
                    <?php if ($interlocuteur['quartier']): ?>
                        <span class="chat-location">
                            <i class="fa-solid fa-location-dot"></i> <?= e($interlocuteur['quartier']) ?>
                        </span>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Référence annonce -->
            <?php if ($annonceMessage): ?>
                <div class="chat-annonce-ref">
                    <i class="fa-solid fa-tag"></i>
                    Concernant l'annonce :
                    <a href="../annonces/detail.php?id=<?= $annonceMessage['id_annonce'] ?>">
                        <?= e($annonceMessage['titre']) ?>
                    </a>
                </div>
            <?php endif; ?>

            <!-- Messages -->
            <div class="chat-messages" id="chatMessages">
                <?php if (empty($messages)): ?>
                    <div class="chat-empty">
                        <i class="fa-solid fa-comment"></i>
                        <p>Démarrez la conversation !</p>
                    </div>
                <?php else: ?>
                    <?php
                    $lastDate = '';
                    foreach ($messages as $msg):
                        $dateMsg = (new DateTime($msg['date_envoi']))->format('d/m/Y');
                        if ($dateMsg !== $lastDate):
                            $lastDate = $dateMsg;
                    ?>
                        <div class="chat-date-separator">
                            <span><?= $dateMsg ?></span>
                        </div>
                    <?php endif; ?>

                    <div class="chat-bubble <?= (int)$msg['id_expediteur'] === $userId ? 'sent' : 'received' ?>">
                        <?php if ((int)$msg['id_expediteur'] !== $userId): ?>
                            <img src="<?= imgProfil($msg['photo_profil']) ?>"
                                 alt="<?= e($msg['prenom']) ?>"
                                 class="bubble-avatar"
                                 onerror="this.src='/voisinpartage/assets/images/default.png'">
                        <?php endif; ?>
                        <div class="bubble-content">
                            <p><?= nl2br(e($msg['contenu'])) ?></p>
                            <span class="bubble-time">
                                <?= (new DateTime($msg['date_envoi']))->format('H:i') ?>
                                <?php if ((int)$msg['id_expediteur'] === $userId): ?>
                                    <?= $msg['lu'] ? '<i class="fa-solid fa-check-double" title="Lu"></i>' : '<i class="fa-solid fa-check" title="Envoyé"></i>' ?>
                                <?php endif; ?>
                            </span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Formulaire envoi -->
            <div class="chat-form-wrap">
                <form method="POST" action="messagerie.php?interlocuteur=<?= $interlocuteurId ?>"
                      class="chat-form" id="chatForm">
                    <?= champCSRF() ?>
                    <input type="hidden" name="id_destinataire" value="<?= $interlocuteurId ?>">
                    <textarea name="contenu" class="chat-input" id="chatInput"
                              placeholder="Écrivez votre message..."
                              rows="1" required></textarea>
                    <button type="submit" class="btn btn-primary chat-send">
                        <i class="fa-solid fa-paper-plane"></i>
                    </button>
                </form>
            </div>

        <?php else: ?>
            <div class="chat-placeholder">
                <i class="fa-solid fa-comments fa-3x"></i>
                <h3>Sélectionnez une conversation</h3>
                <p>Ou contactez un voisin depuis une annonce.</p>
                <a href="../annonces/liste.php" class="btn btn-primary">
                    <i class="fa-solid fa-list"></i> Voir les annonces
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
// Scroll vers le bas
const chatMessages = document.getElementById('chatMessages');
if (chatMessages) chatMessages.scrollTop = chatMessages.scrollHeight;

// Auto-resize textarea
const chatInput = document.getElementById('chatInput');
if (chatInput) {
    chatInput.addEventListener('input', function () {
        this.style.height = 'auto';
        this.style.height = Math.min(this.scrollHeight, 120) + 'px';
    });
    chatInput.addEventListener('keydown', function (e) {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            document.getElementById('chatForm').submit();
        }
    });
}
</script>

<?php require_once '../includes/footer.php'; ?>