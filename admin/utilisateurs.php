<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

exigerAdmin();
$pageTitle = 'Gestion utilisateurs — Admin';
$pdo    = getDB();
$adminId= (int)$_SESSION['user_id'];

// Actions POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifierTokenCSRF($_POST['csrf_token'] ?? '')) {
        redirigerAvecMessage('utilisateurs.php', 'Token invalide.', 'danger');
    }

    $action   = $_POST['action']   ?? '';
    $targetId = (int)($_POST['user_id'] ?? 0);

    if ($targetId === $adminId) {
        redirigerAvecMessage('utilisateurs.php', 'Vous ne pouvez pas effectuer cette action sur votre propre compte.', 'warning');
    }

    if ($action === 'toggle_actif' && $targetId > 0) {
        $pdo->prepare('UPDATE utilisateurs SET actif = NOT actif WHERE id_utilisateur = ?')->execute([$targetId]);
        redirigerAvecMessage('utilisateurs.php', 'Statut utilisateur modifié.', 'success');
    }

    if ($action === 'supprimer' && $targetId > 0) {
        // Supprimer photo profil
        $u = $pdo->prepare('SELECT photo_profil FROM utilisateurs WHERE id_utilisateur = ?');
        $u->execute([$targetId]);
        $uf = $u->fetch();
        if ($uf) supprimerImage($uf['photo_profil'], 'profils');

        $pdo->prepare('DELETE FROM utilisateurs WHERE id_utilisateur = ?')->execute([$targetId]);
        redirigerAvecMessage('utilisateurs.php', 'Utilisateur supprimé.', 'success');
    }
}

// Recherche
$recherche = trim($_GET['q'] ?? '');
$params    = [];
$where     = '';

if ($recherche !== '') {
    $where    = 'WHERE nom LIKE ? OR prenom LIKE ? OR email LIKE ?';
    $params[] = "%{$recherche}%";
    $params[] = "%{$recherche}%";
    $params[] = "%{$recherche}%";
}

$stmt = $pdo->prepare("
    SELECT u.*,
           (SELECT COUNT(*) FROM annonces WHERE id_utilisateur = u.id_utilisateur) AS nb_annonces
    FROM utilisateurs u
    {$where}
    ORDER BY u.date_inscription DESC
");
$stmt->execute($params);
$utilisateurs = $stmt->fetchAll();

require_once '../includes/header.php';
?>

<div class="admin-header">
    <h1><i class="fa-solid fa-users-gear"></i> Gestion des utilisateurs</h1>
    <p><?= count($utilisateurs) ?> utilisateur(s)</p>
</div>

<!-- Recherche -->
<div class="admin-toolbar">
    <form method="GET" action="utilisateurs.php" class="search-form">
        <input type="text" name="q" value="<?= e($recherche) ?>"
               placeholder="Rechercher par nom, prénom, email..."
               class="form-control">
        <button type="submit" class="btn btn-primary">
            <i class="fa-solid fa-magnifying-glass"></i>
        </button>
        <?php if ($recherche): ?>
            <a href="utilisateurs.php" class="btn btn-outline">
                <i class="fa-solid fa-xmark"></i>
            </a>
        <?php endif; ?>
    </form>
    <a href="dashboard.php" class="btn btn-outline">
        <i class="fa-solid fa-arrow-left"></i> Dashboard
    </a>
</div>

<div class="admin-card">
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Utilisateur</th>
                    <th>Email</th>
                    <th>Quartier</th>
                    <th>Rôle</th>
                    <th>Annonces</th>
                    <th>Inscription</th>
                    <th>Statut</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($utilisateurs as $u): ?>
                    <tr class="<?= !$u['actif'] ? 'row-inactive' : '' ?>">
                        <td>
                            <div class="table-user">
                                <img src="<?= imgProfil($u['photo_profil']) ?>"
                                     alt="" class="table-avatar"
                                     onerror="this.src='/voisinpartage/assets/images/default.png'">
                                <div>
                                    <strong><?= e($u['prenom']) ?> <?= e($u['nom']) ?></strong>
                                    <?php if ((int)$u['id_utilisateur'] === $adminId): ?>
                                        <span class="badge badge-info">Vous</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </td>
                        <td><?= e($u['email']) ?></td>
                        <td><?= e($u['quartier'] ?? '—') ?></td>
                        <td>
                            <span class="badge <?= $u['role'] === 'administrateur' ? 'badge-admin' : 'badge-user' ?>">
                                <?= $u['role'] ?>
                            </span>
                        </td>
                        <td class="text-center"><?= $u['nb_annonces'] ?></td>
                        <td><?= formaterDate($u['date_inscription']) ?></td>
                        <td>
                            <span class="badge <?= $u['actif'] ? 'badge-success' : 'badge-danger' ?>">
                                <?= $u['actif'] ? 'Actif' : 'Désactivé' ?>
                            </span>
                        </td>
                        <td class="table-actions">
                            <?php if ((int)$u['id_utilisateur'] !== $adminId): ?>
                                <!-- Toggle actif -->
                                <form method="POST" action="utilisateurs.php" style="display:inline">
                                    <?= champCSRF() ?>
                                    <input type="hidden" name="action" value="toggle_actif">
                                    <input type="hidden" name="user_id" value="<?= $u['id_utilisateur'] ?>">
                                    <button type="submit"
                                            class="btn btn-sm <?= $u['actif'] ? 'btn-warning' : 'btn-success' ?>"
                                            title="<?= $u['actif'] ? 'Désactiver' : 'Activer' ?>">
                                        <i class="fa-solid <?= $u['actif'] ? 'fa-ban' : 'fa-circle-check' ?>"></i>
                                    </button>
                                </form>
                                <!-- Supprimer -->
                                <form method="POST" action="utilisateurs.php" style="display:inline">
                                    <?= champCSRF() ?>
                                    <input type="hidden" name="action" value="supprimer">
                                    <input type="hidden" name="user_id" value="<?= $u['id_utilisateur'] ?>">
                                    <button type="submit"
                                            class="btn btn-sm btn-danger"
                                            title="Supprimer"
                                            onclick="return confirm('Supprimer l\'utilisateur <?= e($u['prenom']) ?> <?= e($u['nom']) ?> et toutes ses données ?')">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                </form>
                            <?php else: ?>
                                <span class="text-muted">—</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>