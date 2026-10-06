<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/functions.php';

$nonLus = 0;
if (estConnecte()) {
    $nonLus = compterMessagesNonLus($_SESSION['user_id']);
}

// Déterminer le préfixe de chemin selon la profondeur
$depth  = substr_count(str_replace($_SERVER['DOCUMENT_ROOT'], '', $_SERVER['SCRIPT_FILENAME']), '/') - 1;
$prefix = str_repeat('../', max(0, $depth - 1));
// Méthode plus fiable : chemin relatif fixe depuis la racine web
$base   = '/voisinpartage/';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?? 'VoisinPartage' ?></title>
    <link rel="stylesheet" href="<?= $base ?>assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>

<header class="site-header">
    <div class="container header-inner">
        <a href="<?= $base ?>index.php" class="logo">
            <i class="fa-solid fa-hands-helping"></i>
            <span>VoisinPartage</span>
        </a>

        <button class="nav-toggle" id="navToggle" aria-label="Menu">
            <i class="fa-solid fa-bars"></i>
        </button>

        <nav class="main-nav" id="mainNav">
            <ul>
                <li><a href="<?= $base ?>index.php"><i class="fa-solid fa-house"></i> Accueil</a></li>
                <li><a href="<?= $base ?>annonces/liste.php"><i class="fa-solid fa-list"></i> Annonces</a></li>

                <?php if (estConnecte()): ?>
                    <li>
                        <a href="<?= $base ?>annonces/creer.php"><i class="fa-solid fa-plus"></i> Publier</a>
                    </li>
                    <li>
                        <a href="<?= $base ?>messages/messagerie.php" class="nav-messages">
                            <i class="fa-solid fa-envelope"></i> Messages
                            <?php if ($nonLus > 0): ?>
                                <span class="badge"><?= $nonLus ?></span>
                            <?php endif; ?>
                        </a>
                    </li>
                    <li>
                        <a href="<?= $base ?>profil/mon_profil.php">
                            <img src="<?= $base ?>assets/images/uploads/profils/<?= e($_SESSION['photo_profil'] ?? 'default.png') ?>"
                                 onerror="this.src='<?= $base ?>assets/images/default.png'"
                                 class="nav-avatar" alt="Profil">
                            <?= e($_SESSION['prenom'] ?? 'Mon profil') ?>
                        </a>
                    </li>
                    <?php if (estAdmin()): ?>
                        <li><a href="<?= $base ?>admin/dashboard.php" class="nav-admin"><i class="fa-solid fa-shield-halved"></i> Admin</a></li>
                    <?php endif; ?>
                    <li><a href="<?= $base ?>deconnexion.php" class="btn-nav-danger"><i class="fa-solid fa-right-from-bracket"></i> Déconnexion</a></li>
                <?php else: ?>
                    <li><a href="<?= $base ?>connexion.php" class="btn-nav"><i class="fa-solid fa-right-to-bracket"></i> Connexion</a></li>
                    <li><a href="<?= $base ?>inscription.php" class="btn-nav-primary"><i class="fa-solid fa-user-plus"></i> Inscription</a></li>
                <?php endif; ?>
            </ul>
        </nav>
    </div>
</header>

<main class="site-main">
<div class="container">
<?= afficherFlash() ?>