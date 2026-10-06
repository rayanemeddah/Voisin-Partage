<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

$pageTitle = 'Annonces — VoisinPartage';
$pdo = getDB();

// Filtres
$recherche  = trim($_GET['recherche']  ?? '');
$categorieF = (int)($_GET['categorie'] ?? 0);
$typeF      = $_GET['type']            ?? '';
$page       = max(1, (int)($_GET['page'] ?? 1));
$parPage    = 9;
$offset     = ($page - 1) * $parPage;

// Construction requête
$where  = ["a.statut != 'indisponible'"];
$params = [];

if ($recherche !== '') {
    $where[]  = '(a.titre LIKE ? OR a.description LIKE ?)';
    $params[] = '%' . $recherche . '%';
    $params[] = '%' . $recherche . '%';
}
if ($categorieF > 0) {
    $where[]  = 'a.id_categorie = ?';
    $params[] = $categorieF;
}
if (in_array($typeF, ['pret', 'don'], true)) {
    $where[]  = 'a.type = ?';
    $params[] = $typeF;
}

$whereSQL = implode(' AND ', $where);

// Total pour pagination
$stmtCount = $pdo->prepare("
    SELECT COUNT(*) FROM annonces a WHERE {$whereSQL}
");
$stmtCount->execute($params);
$total      = (int)$stmtCount->fetchColumn();
$totalPages = (int)ceil($total / $parPage);

// Annonces
$stmtA = $pdo->prepare("
    SELECT a.*, c.nom AS categorie_nom, c.icone AS categorie_icone,
           u.prenom, u.nom AS user_nom, u.quartier, u.photo_profil
    FROM annonces a
    JOIN categories c ON a.id_categorie = c.id_categorie
    JOIN utilisateurs u ON a.id_utilisateur = u.id_utilisateur
    WHERE {$whereSQL}
    ORDER BY a.date_publication DESC
    LIMIT ? OFFSET ?
");
$stmtA->execute(array_merge($params, [$parPage, $offset]));
$annonces = $stmtA->fetchAll();

// Catégories pour le filtre
$categories = $pdo->query('SELECT * FROM categories ORDER BY nom')->fetchAll();

require_once '../includes/header.php';
?>

<div class="page-header">
    <h1><i class="fa-solid fa-list"></i> Les annonces</h1>
    <p><?= $total ?> annonce<?= $total > 1 ? 's' : '' ?> trouvée<?= $total > 1 ? 's' : '' ?></p>
</div>

<!-- FILTRES -->
<div class="filters-card">
    <form method="GET" action="liste.php" class="filters-form">
        <div class="filter-group">
            <label for="recherche"><i class="fa-solid fa-magnifying-glass"></i> Rechercher</label>
            <input type="text" id="recherche" name="recherche"
                   value="<?= e($recherche) ?>"
                   placeholder="Titre, description..."
                   class="form-control">
        </div>

        <div class="filter-group">
            <label for="categorie"><i class="fa-solid fa-tag"></i> Catégorie</label>
            <select id="categorie" name="categorie" class="form-control">
                <option value="0">Toutes les catégories</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?= $cat['id_categorie'] ?>"
                        <?= $categorieF === (int)$cat['id_categorie'] ? 'selected' : '' ?>>
                        <?= e($cat['nom']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="filter-group">
            <label for="type"><i class="fa-solid fa-arrow-right-arrow-left"></i> Type</label>
            <select id="type" name="type" class="form-control">
                <option value="">Prêt & Don</option>
                <option value="pret"  <?= $typeF === 'pret' ? 'selected' : '' ?>>Prêt uniquement</option>
                <option value="don"   <?= $typeF === 'don'  ? 'selected' : '' ?>>Don uniquement</option>
            </select>
        </div>

        <div class="filter-actions">
            <button type="submit" class="btn btn-primary">
                <i class="fa-solid fa-filter"></i> Filtrer
            </button>
            <a href="liste.php" class="btn btn-outline">
                <i class="fa-solid fa-xmark"></i> Réinitialiser
            </a>
        </div>
    </form>
</div>

<!-- RÉSULTATS -->
<?php if (empty($annonces)): ?>
    <div class="empty-state">
        <i class="fa-solid fa-box-open"></i>
        <h3>Aucune annonce trouvée</h3>
        <p>Essayez de modifier vos filtres ou publiez la première annonce !</p>
        <?php if (estConnecte()): ?>
            <a href="creer.php" class="btn btn-primary">
                <i class="fa-solid fa-plus"></i> Publier une annonce
            </a>
        <?php else: ?>
            <a href="../inscription.php" class="btn btn-primary">S'inscrire pour publier</a>
        <?php endif; ?>
    </div>
<?php else: ?>
    <div class="annonces-grid">
        <?php foreach ($annonces as $a): ?>
            <article class="annonce-card">
                <a href="detail.php?id=<?= $a['id_annonce'] ?>" class="card-image-link">
                    <img src="<?= imgAnnonce($a['photo']) ?>"
                         alt="<?= e($a['titre']) ?>"
                         class="card-image"
                         onerror="this.src='/voisinpartage/assets/images/no-image.png'">
                    <span class="badge-type badge-type-<?= $a['type'] ?>">
                        <?= $a['type'] === 'pret' ? '<i class="fa-solid fa-rotate"></i> Prêt' : '<i class="fa-solid fa-gift"></i> Don' ?>
                    </span>
                    <?php if ($a['statut'] === 'reserve'): ?>
                        <span class="badge-statut badge-reserve">Réservé</span>
                    <?php endif; ?>
                </a>
                <div class="card-body">
                    <div class="card-meta">
                        <span class="card-category">
                            <i class="<?= e($a['categorie_icone']) ?>"></i>
                            <?= e($a['categorie_nom']) ?>
                        </span>
                        <span class="card-date"><?= formaterDate($a['date_publication']) ?></span>
                    </div>
                    <h3 class="card-title">
                        <a href="detail.php?id=<?= $a['id_annonce'] ?>"><?= e($a['titre']) ?></a>
                    </h3>
                    <p class="card-desc"><?= e(mb_substr($a['description'], 0, 90)) ?>...</p>
                    <div class="card-footer">
                        <span class="card-user">
                            <i class="fa-solid fa-location-dot"></i>
                            <?= e($a['quartier'] ?? 'Non précisé') ?>
                        </span>
                        <a href="detail.php?id=<?= $a['id_annonce'] ?>" class="btn btn-sm btn-primary">
                            Voir
                        </a>
                    </div>
                </div>
            </article>
        <?php endforeach; ?>
    </div>

    <!-- PAGINATION -->
    <?php if ($totalPages > 1): ?>
        <div class="pagination">
            <?php
            $queryBase = http_build_query(array_filter([
                'recherche' => $recherche,
                'categorie' => $categorieF ?: null,
                'type'      => $typeF,
            ]));
            ?>
            <?php if ($page > 1): ?>
                <a href="liste.php?<?= $queryBase ?>&page=<?= $page - 1 ?>" class="page-btn">
                    <i class="fa-solid fa-chevron-left"></i> Précédent
                </a>
            <?php endif; ?>

            <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                <a href="liste.php?<?= $queryBase ?>&page=<?= $i ?>"
                   class="page-btn <?= $i === $page ? 'active' : '' ?>">
                    <?= $i ?>
                </a>
            <?php endfor; ?>

            <?php if ($page < $totalPages): ?>
                <a href="liste.php?<?= $queryBase ?>&page=<?= $page + 1 ?>" class="page-btn">
                    Suivant <i class="fa-solid fa-chevron-right"></i>
                </a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
<?php endif; ?>

<?php require_once '../includes/footer.php'; ?>