<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

exigerAdmin();
$pageTitle = 'Modération annonces — Admin';
$pdo = getDB();

// Actions POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifierTokenCSRF($_POST['csrf_token'] ?? '')) {
        redirigerAvecMessage('annonces.php', 'Token invalide.', 'danger');
    }

    $action = $_POST['action'] ?? '';
    $id     = (int)($_POST['id_annonce'] ?? 0);

    if ($action === 'changer_statut' && $id > 0) {
        $statut = $_POST['statut'] ?? '';
        $valid  = ['disponible', 'reserve', 'indisponible'];
        if (in_array($statut, $valid, true)) {
            $pdo->prepare('UPDATE annonces SET statut = ? WHERE id_annonce = ?')->execute([$statut, $id]);
            redirigerAvecMessage('annonces.php', 'Statut modifié.', 'success');
        }
    }

    if ($action === 'supprimer' && $id > 0) {
        $stmt = $pdo->prepare('SELECT photo FROM annonces WHERE id_annonce = ?');
        $stmt->execute([$id]);
        $a = $stmt->fetch();
        if ($a) supprimerImage($a['photo'], 'annonces');
        $pdo->prepare('DELETE FROM annonces WHERE id_annonce = ?')->execute([$id]);
        redirigerAvecMessage('annonces.php', 'Annonce supprimée.', 'success');
    }
}

// Filtres
$recherche  = trim($_GET['q']      ?? '');
$typeF      = $_GET['type']        ?? '';
$statutF    = $_GET['statut']      ?? '';
$categorieF = (int)($_GET['cat']   ?? 0);

$where  = [];
$params = [];

if ($recherche !== '') {
    $where[]  = '(a.titre LIKE ? OR a.description LIKE ?)';
    $params[] = "%{$recherche}%";
    $params[] = "%{$recherche}%";
}
if (in_array($typeF, ['pret', 'don'], true)) {
    $where[]  = 'a.type = ?';
    $params[] = $typeF;
}
if (in_array($statutF, ['disponible', 'reserve', 'indisponible'], true)) {
    $where[]  = 'a.statut = ?';
    $params[] = $statutF;
}
if ($categorieF > 0) {
    $where[]  = 'a.id_categorie = ?';
    $params[] = $categorieF;
}

$whereSQL = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$stmtA = $pdo->prepare("
    SELECT a.*, u.prenom, u.nom, u.email, c.nom AS cat_nom
    FROM annonces a
    JOIN utilisateurs u ON a.id_utilisateur = u.id_utilisateur
    JOIN categories c ON a.id_categorie = c.id_categorie
    {$whereSQL}
    ORDER BY a.date_publication DESC
");
$stmtA->execute($params);
$annonces = $stmtA->fetchAll();

$categories = $pdo->query('SELECT * FROM categories ORDER BY nom')->fetchAll();

require_once '../includes/header.php';
?>

<div class="admin-header">
    <h1><i class="fa-solid fa-list-check"></i> Modération des annonces</h1>
    <p><?= count($annonces) ?> annonce(s)</p>
</div>

<!-- Filtres -->
<div class="admin-toolbar filters-form">
    <form method="GET" action="annonces.php" class="filters-form">
        <input type="text" name="q" value="<?= e($recherche) ?>"
               placeholder="Rechercher..." class="form-control">
        <select name="type" class="form-control">
            <option value="">Tous types</option>
            <option value="pret" <?= $typeF === 'pret' ? 'selected' : '' ?>>Prêt</option>
            <option value="don"  <?= $typeF === 'don'  ? 'selected' : '' ?>>Don</option>
        </select>
        <select name="statut" class="form-control">
            <option value="">Tous statuts</option>
            <option value="disponible"   <?= $statutF === 'disponible'   ? 'selected' : '' ?>>Disponible</option>
            <option value="reserve"      <?= $statutF === 'reserve'      ? 'selected' : '' ?>>Réservé</option>
            <option value="indisponible" <?= $statutF === 'indisponible' ? 'selected' : '' ?>>Indisponible</option>
        </select>
        <select name="cat" class="form-control">
            <option value="0">Toutes catégories</option>
            <?php foreach ($categories as $cat): ?>
                <option value="<?= $cat['id_categorie'] ?>"
                    <?= $categorieF === (int)$cat['id_categorie'] ? 'selected' : '' ?>>
                    <?= e($cat['nom']) ?>
                </option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="btn btn-primary">
            <i class="fa-solid fa-filter"></i> Filtrer
        </button>
        <a href="annonces.php" class="btn btn-outline">
            <i class="fa-solid fa-xmark"></i>
        </a>
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
                    <th>Annonce</th>
                    <th>Auteur</th>
                    <th>Type</th>
                    <th>Catégorie</th>
                    <th>Statut</th>
                    <th>Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($annonces)): ?>
                    <tr><td colspan="7" class="text-center">Aucune annonce trouvée.</td></tr>
                <?php endif; ?>
                <?php foreach ($annonces as $a): ?>
                    <tr>
                        <td>
                            <div class="table-annonce">
                                <img src="<?= imgAnnonce($a['photo']) ?>" alt="" class="table-thumb">
                                <a href="../annonces/detail.php?id=<?= $a['id_annonce'] ?>">
                                    <?= e(mb_substr($a['titre'], 0, 40)) ?>
                                </a>
                            </div>
                        </td>
                        <td>
                            <?= e($a['prenom']) ?> <?= e($a['nom']) ?>
                            <small><?= e($a['email']) ?></small>
                        </td>
                        <td>
                            <span class="badge badge-type-<?= $a['type'] ?>">
                                <?= $a['type'] === 'pret' ? 'Prêt' : 'Don' ?>
                            </span>
                        </td>
                        <td><?= e($a['cat_nom']) ?></td>
                        <td>
                            <!-- Formulaire changement statut inline -->
                            <form method="POST" action="annonces.php">
                                <?= champCSRF() ?>
                                <input type="hidden" name="action" value="changer_statut">
                                <input type="hidden" name="id_annonce" value="<?= $a['id_annonce'] ?>">
                                <select name="statut" class="form-control form-control-sm"
                                        onchange="this.form.submit()">
                                    <option value="disponible"   <?= $a['statut'] === 'disponible'   ? 'selected' : '' ?>>✅ Disponible</option>
                                    <option value="reserve"      <?= $a['statut'] === 'reserve'      ? 'selected' : '' ?>>⏳ Réservé</option>
                                    <option value="indisponible" <?= $a['statut'] === 'indisponible' ? 'selected' : '' ?>>❌ Indisponible</option>
                                </select>
                            </form>
                        </td>
                        <td><?= formaterDate($a['date_publication']) ?></td>
                        <td class="table-actions">
                            <a href="../annonces/detail.php?id=<?= $a['id_annonce'] ?>"
                               class="btn btn-sm btn-outline" title="Voir">
                                <i class="fa-solid fa-eye"></i>
                            </a>
                            <form method="POST" action="annonces.php" style="display:inline">
                                <?= champCSRF() ?>
                                <input type="hidden" name="action" value="supprimer">
                                <input type="hidden" name="id_annonce" value="<?= $a['id_annonce'] ?>">
                                <button type="submit" class="btn btn-sm btn-danger" title="Supprimer"
                                        onclick="return confirm('Supprimer cette annonce ?')">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>