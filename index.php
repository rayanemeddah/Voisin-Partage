<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

$pageTitle = 'VoisinPartage — Partageons entre voisins';
$pdo = getDB();

// Base URL pour les assets
$base = getBaseUrl();

// Dernières annonces disponibles
$stmtAnnonces = $pdo->query("
    SELECT a.*, c.nom AS categorie_nom, c.icone AS categorie_icone,
           u.nom AS user_nom, u.prenom AS user_prenom, u.quartier
    FROM annonces a
    JOIN categories c ON a.id_categorie = c.id_categorie
    JOIN utilisateurs u ON a.id_utilisateur = u.id_utilisateur
    WHERE a.statut = 'disponible'
    ORDER BY a.date_publication DESC
    LIMIT 6
");
$dernieresAnnonces = $stmtAnnonces->fetchAll();

// Catégories avec comptage
$stmtCats = $pdo->query("
    SELECT c.*, COUNT(a.id_annonce) AS nb_annonces
    FROM categories c
    LEFT JOIN annonces a ON c.id_categorie = a.id_categorie
        AND a.statut = 'disponible'
    GROUP BY c.id_categorie
    ORDER BY nb_annonces DESC
");
$categories = $stmtCats->fetchAll();

// Stats
$stats = [
    'annonces' => $pdo->query("SELECT COUNT(*) FROM annonces WHERE statut = 'disponible'")->fetchColumn(),
    'users'    => $pdo->query("SELECT COUNT(*) FROM utilisateurs WHERE actif = 1")->fetchColumn(),
    'echanges' => $pdo->query("SELECT COUNT(*) FROM demandes_emprunt WHERE statut = 'terminee'")->fetchColumn(),
];

require_once 'includes/header.php';
?>

<!-- HERO -->
<section class="hero">
    <div class="hero-content">
        <div class="hero-badge">
            <i class="fa-solid fa-hands-helping"></i> Économie collaborative
        </div>
        <h1 class="hero-title">
            Partageons entre <span class="highlight">voisins</span>
        </h1>
        <p class="hero-subtitle">
            Prêtez, donnez ou empruntez des objets à vos voisins.
            Réduisons notre consommation ensemble et renforçons le lien social local.
        </p>
        <div class="hero-actions">
            <a href="annonces/liste.php" class="btn btn-primary btn-lg">
                <i class="fa-solid fa-magnifying-glass"></i> Parcourir les annonces
            </a>
            <?php if (estConnecte()): ?>
                <a href="annonces/creer.php" class="btn btn-outline btn-lg">
                    <i class="fa-solid fa-plus"></i> Publier une annonce
                </a>
            <?php else: ?>
                <a href="inscription.php" class="btn btn-outline btn-lg">
                    <i class="fa-solid fa-user-plus"></i> Rejoindre la communauté
                </a>
            <?php endif; ?>
        </div>
    </div>
    <div class="hero-illustration">
        <i class="fa-solid fa-hands-helping hero-icon"></i>
    </div>
</section>

<!-- STATS -->
<section class="stats-section">
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon"><i class="fa-solid fa-tag"></i></div>
            <div class="stat-number"><?= $stats['annonces'] ?></div>
            <div class="stat-label">Annonces disponibles</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon"><i class="fa-solid fa-users"></i></div>
            <div class="stat-number"><?= $stats['users'] ?></div>
            <div class="stat-label">Voisins actifs</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon"><i class="fa-solid fa-handshake"></i></div>
            <div class="stat-number"><?= $stats['echanges'] ?></div>
            <div class="stat-label">Échanges réalisés</div>
        </div>
    </div>
</section>

<!-- CATÉGORIES -->
<section class="section">
    <div class="section-header">
        <h2><i class="fa-solid fa-grid-2"></i> Catégories</h2>
        <a href="annonces/liste.php" class="btn-link">Voir toutes les annonces →</a>
    </div>
    <div class="categories-grid">
        <?php foreach ($categories as $cat): ?>
            <a href="annonces/liste.php?categorie=<?= $cat['id_categorie'] ?>"
               class="category-card">
                <div class="category-icon">
                    <i class="<?= e($cat['icone']) ?>"></i>
                </div>
                <div class="category-name"><?= e($cat['nom']) ?></div>
                <div class="category-count">
                    <?= $cat['nb_annonces'] ?>
                    annonce<?= $cat['nb_annonces'] > 1 ? 's' : '' ?>
                </div>
            </a>
        <?php endforeach; ?>
    </div>
</section>

<!-- DERNIÈRES ANNONCES -->
<section class="section">
    <div class="section-header">
        <h2><i class="fa-solid fa-clock-rotate-left"></i> Dernières annonces</h2>
        <a href="annonces/liste.php" class="btn-link">Voir tout →</a>
    </div>

    <?php if (empty($dernieresAnnonces)): ?>
        <div class="empty-state">
            <i class="fa-solid fa-box-open"></i>
            <p>Aucune annonce disponible pour le moment.</p>
            <?php if (estConnecte()): ?>
                <a href="annonces/creer.php" class="btn btn-primary">
                    Publier la première annonce
                </a>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="annonces-grid">
            <?php foreach ($dernieresAnnonces as $a): ?>
                <article class="annonce-card">
                    <a href="annonces/detail.php?id=<?= $a['id_annonce'] ?>"
                       class="card-image-link">
                        <img src="<?= imgAnnonce($a['photo']) ?>"
                             alt="<?= e($a['titre']) ?>"
                             class="card-image"
                             onerror="this.onerror=null; this.src='<?= $base ?>/assets/images/no-image.png'">
                        <span class="badge-type badge-type-<?= $a['type'] ?>">
                            <?= $a['type'] === 'pret'
                                ? '<i class="fa-solid fa-rotate"></i> Prêt'
                                : '<i class="fa-solid fa-gift"></i> Don' ?>
                        </span>
                    </a>
                    <div class="card-body">
                        <div class="card-meta">
                            <span class="card-category">
                                <i class="<?= e($a['categorie_icone']) ?>"></i>
                                <?= e($a['categorie_nom']) ?>
                            </span>
                            <span class="card-date">
                                <?= formaterDate($a['date_publication']) ?>
                            </span>
                        </div>
                        <h3 class="card-title">
                            <a href="annonces/detail.php?id=<?= $a['id_annonce'] ?>">
                                <?= e($a['titre']) ?>
                            </a>
                        </h3>
                        <p class="card-desc">
                            <?= e(mb_substr($a['description'], 0, 100)) ?>...
                        </p>
                        <div class="card-footer">
                            <span class="card-user">
                                <i class="fa-solid fa-location-dot"></i>
                                <?= e($a['quartier'] ?? 'Non précisé') ?>
                            </span>
                            <a href="annonces/detail.php?id=<?= $a['id_annonce'] ?>"
                               class="btn btn-sm btn-primary">
                                Voir l'annonce
                            </a>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<!-- COMMENT ÇA MARCHE -->
<section class="section section-how">
    <h2 class="text-center">
        <i class="fa-solid fa-circle-question"></i> Comment ça marche ?
    </h2>
    <div class="steps-grid">
        <div class="step-card">
            <div class="step-number">1</div>
            <div class="step-icon"><i class="fa-solid fa-user-plus"></i></div>
            <h3>Inscrivez-vous</h3>
            <p>Créez votre compte gratuitement en quelques secondes.</p>
        </div>
        <div class="step-card">
            <div class="step-number">2</div>
            <div class="step-icon"><i class="fa-solid fa-camera"></i></div>
            <h3>Publiez une annonce</h3>
            <p>Ajoutez vos objets à prêter ou donner avec une photo.</p>
        </div>
        <div class="step-card">
            <div class="step-number">3</div>
            <div class="step-icon"><i class="fa-solid fa-envelope"></i></div>
            <h3>Contactez</h3>
            <p>Échangez via la messagerie privée intégrée.</p>
        </div>
        <div class="step-card">
            <div class="step-number">4</div>
            <div class="step-icon"><i class="fa-solid fa-handshake"></i></div>
            <h3>Partagez !</h3>
            <p>Rencontrez-vous et profitez de l'économie collaborative.</p>
        </div>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>