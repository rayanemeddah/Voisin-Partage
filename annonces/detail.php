<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

$pageTitle = 'Détail annonce — VoisinPartage';
$pdo = getDB();
$id  = (int)($_GET['id'] ?? 0);

if (!$id) {
    redirigerAvecMessage('liste.php', 'Annonce introuvable.', 'danger');
}

$stmt = $pdo->prepare("
    SELECT a.*, c.nom AS categorie_nom, c.icone AS categorie_icone,
           u.id_utilisateur AS proprio_id, u.nom AS proprio_nom, u.prenom AS proprio_prenom,
           u.quartier, u.telephone, u.photo_profil, u.date_inscription
    FROM annonces a
    JOIN categories c ON a.id_categorie = c.id_categorie
    JOIN utilisateurs u ON a.id_utilisateur = u.id_utilisateur
    WHERE a.id_annonce = ?
");
$stmt->execute([$id]);
$annonce = $stmt->fetch();

if (!$annonce) {
    redirigerAvecMessage('liste.php', 'Annonce introuvable.', 'danger');
}

$pageTitle    = e($annonce['titre']) . ' — VoisinPartage';
$estProprietaire = estConnecte() && (int)$_SESSION['user_id'] === (int)$annonce['proprio_id'];

// Autres annonces du propriétaire
$stmtAutres = $pdo->prepare("
    SELECT id_annonce, titre, photo, type, statut
    FROM annonces
    WHERE id_utilisateur = ? AND id_annonce != ? AND statut = 'disponible'
    ORDER BY date_publication DESC
    LIMIT 3
");
$stmtAutres->execute([$annonce['proprio_id'], $id]);
$autresAnnonces = $stmtAutres->fetchAll();

require_once '../includes/header.php';
?>

<div class="breadcrumb">
    <a href="../index.php">Accueil</a>
    <i class="fa-solid fa-chevron-right"></i>
    <a href="liste.php">Annonces</a>
    <i class="fa-solid fa-chevron-right"></i>
    <span><?= e($annonce['titre']) ?></span>
</div>

<div class="detail-layout">
    <!-- COLONNE PRINCIPALE -->
    <div class="detail-main">
        <div class="detail-card">
            <!-- Image -->
            <div class="detail-image-wrap">
                <img src="<?= imgAnnonce($annonce['photo']) ?>"
                     alt="<?= e($annonce['titre']) ?>"
                     class="detail-image"
                     onerror="this.src='/voisinpartage/assets/images/no-image.png'">
                <div class="detail-badges">
                    <span class="badge badge-type-<?= $annonce['type'] ?>">
                        <?php if ($annonce['type'] === 'pret'): ?>
                            <i class="fa-solid fa-rotate"></i> Prêt
                        <?php else: ?>
                            <i class="fa-solid fa-gift"></i> Don
                        <?php endif; ?>
                    </span>
                    <span class="badge badge-statut-<?= $annonce['statut'] ?>">
                        <?= match($annonce['statut']) {
                            'disponible'   => '<i class="fa-solid fa-circle-check"></i> Disponible',
                            'reserve'      => '<i class="fa-solid fa-clock"></i> Réservé',
                            'indisponible' => '<i class="fa-solid fa-circle-xmark"></i> Indisponible',
                        } ?>
                    </span>
                </div>
            </div>

            <!-- Contenu -->
            <div class="detail-body">
                <div class="detail-meta">
                    <span class="cat-badge">
                        <i class="<?= e($annonce['categorie_icone']) ?>"></i>
                        <?= e($annonce['categorie_nom']) ?>
                    </span>
                    <span class="detail-date">
                        <i class="fa-solid fa-calendar"></i>
                        Publié <?= formaterDate($annonce['date_publication']) ?>
                    </span>
                </div>

                <h1 class="detail-title"><?= e($annonce['titre']) ?></h1>

                <div class="detail-description">
                    <h3><i class="fa-solid fa-align-left"></i> Description</h3>
                    <p><?= nl2br(e($annonce['description'])) ?></p>
                </div>

                <!-- ACTIONS SELON CONTEXTE -->
                <div class="detail-actions">
                    <?php if (!estConnecte()): ?>
                        <div class="action-box action-login">
                            <i class="fa-solid fa-lock fa-2x"></i>
                            <p>Connectez-vous pour contacter le propriétaire</p>
                            <a href="../connexion.php" class="btn btn-primary">
                                <i class="fa-solid fa-right-to-bracket"></i> Se connecter
                            </a>
                            <a href="../inscription.php" class="btn btn-outline">S'inscrire</a>
                        </div>

                    <?php elseif ($estProprietaire): ?>
                        <div class="action-box action-owner">
                            <h4><i class="fa-solid fa-gear"></i> Gérer mon annonce</h4>
                            <div class="owner-actions">
                                <a href="modifier.php?id=<?= $id ?>" class="btn btn-warning">
                                    <i class="fa-solid fa-pen"></i> Modifier
                                </a>
                                <a href="supprimer.php?id=<?= $id ?>" class="btn btn-danger"
                                   onclick="return confirm('Supprimer cette annonce ?')">
                                    <i class="fa-solid fa-trash"></i> Supprimer
                                </a>
                            </div>
                            <div class="statut-form">
                                <label><i class="fa-solid fa-toggle-on"></i> Changer le statut :</label>
                                <form method="POST" action="changer_statut.php">
                                    <?= champCSRF() ?>
                                    <input type="hidden" name="id_annonce" value="<?= $id ?>">
                                    <input type="hidden" name="redirect" value="detail.php?id=<?= $id ?>">
                                    <select name="statut" class="form-control" onchange="this.form.submit()">
                                        <option value="disponible"   <?= $annonce['statut'] === 'disponible'   ? 'selected' : '' ?>>Disponible</option>
                                        <option value="reserve"      <?= $annonce['statut'] === 'reserve'      ? 'selected' : '' ?>>Réservé</option>
                                        <option value="indisponible" <?= $annonce['statut'] === 'indisponible' ? 'selected' : '' ?>>Indisponible</option>
                                    </select>
                                </form>
                            </div>
                        </div>

                    <?php else: ?>
                        <?php if ($annonce['statut'] === 'disponible'): ?>
                            <div class="action-box action-contact">
                                <h4><i class="fa-solid fa-envelope"></i> Intéressé(e) ?</h4>
                                <p>Contactez <?= e($annonce['proprio_prenom']) ?> via la messagerie privée.</p>
                                <a href="../messages/messagerie.php?interlocuteur=<?= $annonce['proprio_id'] ?>&annonce=<?= $id ?>"
                                   class="btn btn-primary btn-lg">
                                    <i class="fa-solid fa-paper-plane"></i> Contacter le propriétaire
                                </a>
                            </div>
                        <?php else: ?>
                            <div class="action-box action-unavailable">
                                <i class="fa-solid fa-circle-xmark fa-2x"></i>
                                <p>Cette annonce est actuellement
                                    <strong><?= $annonce['statut'] === 'reserve' ? 'réservée' : 'indisponible' ?></strong>.
                                </p>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>

                <!-- Admin : actions supplémentaires -->
                <?php if (estAdmin() && !$estProprietaire): ?>
                    <div class="admin-actions-bar">
                        <span class="badge badge-admin"><i class="fa-solid fa-shield-halved"></i> Admin</span>
                        <a href="supprimer.php?id=<?= $id ?>" class="btn btn-sm btn-danger"
                           onclick="return confirm('Supprimer cette annonce ?')">
                            <i class="fa-solid fa-trash"></i> Supprimer (modération)
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- COLONNE LATÉRALE -->
    <aside class="detail-sidebar">
        <!-- Propriétaire -->
        <div class="sidebar-card">
            <h3><i class="fa-solid fa-user"></i> Propriétaire</h3>
            <div class="owner-profile">
                <img src="<?= imgProfil($annonce['photo_profil']) ?>"
                     alt="<?= e($annonce['proprio_prenom']) ?>"
                     class="owner-avatar"
                     onerror="this.src='/voisinpartage/assets/images/default.png'">
                <div class="owner-info">
                    <strong><?= e($annonce['proprio_prenom']) ?> <?= e($annonce['proprio_nom']) ?></strong>
                    <?php if ($annonce['quartier']): ?>
                        <span><i class="fa-solid fa-location-dot"></i> <?= e($annonce['quartier']) ?></span>
                    <?php endif; ?>
                    <span class="member-since">
                        <i class="fa-solid fa-calendar-check"></i>
                        Membre depuis <?= formaterDateComplete($annonce['date_inscription']) ?>
                    </span>
                </div>
            </div>
        </div>

        <!-- Autres annonces -->
        <?php if (!empty($autresAnnonces)): ?>
            <div class="sidebar-card">
                <h3><i class="fa-solid fa-boxes-stacked"></i> Autres annonces</h3>
                <div class="other-annonces">
                    <?php foreach ($autresAnnonces as $autre): ?>
                        <a href="detail.php?id=<?= $autre['id_annonce'] ?>" class="other-annonce-item">
                            <img src="<?= imgAnnonce($autre['photo']) ?>"
                                 alt="<?= e($autre['titre']) ?>"
                                 onerror="this.src='/voisinpartage/assets/images/no-image.png'">
                            <div>
                                <span class="other-titre"><?= e($autre['titre']) ?></span>
                                <span class="badge-type-sm badge-type-<?= $autre['type'] ?>">
                                    <?= $autre['type'] === 'pret' ? 'Prêt' : 'Don' ?>
                                </span>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </aside>
</div>

<?php require_once '../includes/footer.php'; ?>