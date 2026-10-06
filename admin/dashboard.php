<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

exigerAdmin();
$pageTitle = 'Tableau de bord — Admin VoisinPartage';
$pdo = getDB();

// Stats globales
$stats = [
    'total_users'       => $pdo->query("SELECT COUNT(*) FROM utilisateurs")->fetchColumn(),
    'users_actifs'      => $pdo->query("SELECT COUNT(*) FROM utilisateurs WHERE actif = 1")->fetchColumn(),
    'total_annonces'    => $pdo->query("SELECT COUNT(*) FROM annonces")->fetchColumn(),
    'annonces_dispo'    => $pdo->query("SELECT COUNT(*) FROM annonces WHERE statut = 'disponible'")->fetchColumn(),
    'total_messages'    => $pdo->query("SELECT COUNT(*) FROM messages")->fetchColumn(),
    'total_demandes'    => $pdo->query("SELECT COUNT(*) FROM demandes_emprunt")->fetchColumn(),
    'echanges_termines' => $pdo->query("SELECT COUNT(*) FROM demandes_emprunt WHERE statut = 'terminee'")->fetchColumn(),
];

// Derniers inscrits
$derniersUsers = $pdo->query("
    SELECT * FROM utilisateurs ORDER BY date_inscription DESC LIMIT 5
")->fetchAll();

// Dernières annonces
$dernieresAnnonces = $pdo->query("
    SELECT a.*, u.prenom, u.nom, c.nom AS cat_nom
    FROM annonces a
    JOIN utilisateurs u ON a.id_utilisateur = u.id_utilisateur
    JOIN categories c ON a.id_categorie = c.id_categorie
    ORDER BY a.date_publication DESC
    LIMIT 5
")->fetchAll();

require_once '../includes/header.php';
?>

<div class="admin-header">
    <h1><i class="fa-solid fa-shield-halved"></i> Tableau de bord</h1>
    <p>Bienvenue, <?= e($_SESSION['prenom']) ?> ! Vue d'ensemble de la plateforme.</p>
</div>

<!-- STATS -->
<div class="admin-stats-grid">
    <div class="admin-stat-card stat-blue">
        <div class="stat-icon"><i class="fa-solid fa-users"></i></div>
        <div class="stat-info">
            <div class="stat-number"><?= $stats['total_users'] ?></div>
            <div class="stat-label">Utilisateurs total</div>
            <div class="stat-sub"><?= $stats['users_actifs'] ?> actifs</div>
        </div>
    </div>
    <div class="admin-stat-card stat-green">
        <div class="stat-icon"><i class="fa-solid fa-tag"></i></div>
        <div class="stat-info">
            <div class="stat-number"><?= $stats['total_annonces'] ?></div>
            <div class="stat-label">Annonces total</div>
            <div class="stat-sub"><?= $stats['annonces_dispo'] ?> disponibles</div>
        </div>
    </div>
    <div class="admin-stat-card stat-purple">
        <div class="stat-icon"><i class="fa-solid fa-envelope"></i></div>
        <div class="stat-info">
            <div class="stat-number"><?= $stats['total_messages'] ?></div>
            <div class="stat-label">Messages échangés</div>
        </div>
    </div>
    <div class="admin-stat-card stat-orange">
        <div class="stat-icon"><i class="fa-solid fa-handshake"></i></div>
        <div class="stat-info">
            <div class="stat-number"><?= $stats['echanges_termines'] ?></div>
            <div class="stat-label">Échanges terminés</div>
            <div class="stat-sub"><?= $stats['total_demandes'] ?> demandes total</div>
        </div>
    </div>
</div>

<!-- NAVIGATION ADMIN -->
<div class="admin-nav-cards">
    <a href="utilisateurs.php" class="admin-nav-card">
        <i class="fa-solid fa-users-gear"></i>
        <span>Gérer les utilisateurs</span>
    </a>
    <a href="annonces.php" class="admin-nav-card">
        <i class="fa-solid fa-list-check"></i>
        <span>Modérer les annonces</span>
    </a>
    <a href="../annonces/liste.php" class="admin-nav-card">
        <i class="fa-solid fa-eye"></i>
        <span>Voir le site</span>
    </a>
</div>

<div class="admin-two-col">
    <!-- Derniers inscrits -->
    <div class="admin-card">
        <div class="admin-card-header">
            <h3><i class="fa-solid fa-user-plus"></i> Derniers inscrits</h3>
            <a href="utilisateurs.php" class="btn btn-sm btn-outline">Voir tous</a>
        </div>
        <table class="table">
            <thead>
                <tr><th>Utilisateur</th><th>Rôle</th><th>Statut</th><th>Date</th></tr>
            </thead>
            <tbody>
                <?php foreach ($derniersUsers as $u): ?>
                    <tr>
                        <td>
                            <div class="table-user">
                                <img src="<?= imgProfil($u['photo_profil']) ?>"
                                     alt="" class="table-avatar"
                                     onerror="this.src='/voisinpartage/assets/images/default.png'">
                                <div>
                                    <strong><?= e($u['prenom']) ?> <?= e($u['nom']) ?></strong>
                                    <small><?= e($u['email']) ?></small>
                                </div>
                            </div>
                        </td>
                        <td>
                            <span class="badge <?= $u['role'] === 'administrateur' ? 'badge-admin' : 'badge-user' ?>">
                                <?= $u['role'] ?>
                            </span>
                        </td>
                        <td>
                            <span class="badge <?= $u['actif'] ? 'badge-success' : 'badge-danger' ?>">
                                <?= $u['actif'] ? 'Actif' : 'Inactif' ?>
                            </span>
                        </td>
                        <td><?= formaterDate($u['date_inscription']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Dernières annonces -->
    <div class="admin-card">
        <div class="admin-card-header">
            <h3><i class="fa-solid fa-clock-rotate-left"></i> Dernières annonces</h3>
            <a href="annonces.php" class="btn btn-sm btn-outline">Voir toutes</a>
        </div>
        <table class="table">
            <thead>
                <tr><th>Annonce</th><th>Type</th><th>Statut</th><th>Date</th></tr>
            </thead>
            <tbody>
                <?php foreach ($dernieresAnnonces as $a): ?>
                    <tr>
                        <td>
                            <a href="../annonces/detail.php?id=<?= $a['id_annonce'] ?>">
                                <?= e(mb_substr($a['titre'], 0, 35)) ?>...
                            </a>
                            <small><?= e($a['prenom']) ?> <?= e($a['nom']) ?> — <?= e($a['cat_nom']) ?></small>
                        </td>
                        <td>
                            <span class="badge badge-type-<?= $a['type'] ?>">
                                <?= $a['type'] === 'pret' ? 'Prêt' : 'Don' ?>
                            </span>
                        </td>
                        <td>
                            <span class="badge badge-statut-<?= $a['statut'] ?>">
                                <?= $a['statut'] ?>
                            </span>
                        </td>
                        <td><?= formaterDate($a['date_publication']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>