<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

exigerConnexion();
$pageTitle = 'Mon profil — VoisinPartage';
$pdo    = getDB();
$userId = (int)$_SESSION['user_id'];

// Récupérer l'utilisateur
$stmt = $pdo->prepare('SELECT * FROM utilisateurs WHERE id_utilisateur = ?');
$stmt->execute([$userId]);
$user = $stmt->fetch();

$erreurs = [];
$succes  = '';

// Traitement formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifierTokenCSRF($_POST['csrf_token'] ?? '')) {
        $erreurs[] = 'Token de sécurité invalide.';
    } else {
        $action = $_POST['action'] ?? '';

        if ($action === 'profil') {
            $nom      = trim($_POST['nom']       ?? '');
            $prenom   = trim($_POST['prenom']    ?? '');
            $email    = trim($_POST['email']     ?? '');
            $quartier = trim($_POST['quartier']  ?? '');
            $tel      = trim($_POST['telephone'] ?? '');
            $adresse  = trim($_POST['adresse']   ?? '');

            if (empty($nom))    $erreurs[] = 'Le nom est obligatoire.';
            if (empty($prenom)) $erreurs[] = 'Le prénom est obligatoire.';
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $erreurs[] = 'Email invalide.';

            // Email unique (sauf le sien)
            if (empty($erreurs)) {
                $chk = $pdo->prepare('SELECT id_utilisateur FROM utilisateurs WHERE email = ? AND id_utilisateur != ?');
                $chk->execute([$email, $userId]);
                if ($chk->fetch()) $erreurs[] = 'Cet email est déjà utilisé.';
            }

            // Photo de profil
            $photo = $user['photo_profil'];
            if (!empty($_FILES['photo_profil']['name'])) {
                $result = uploaderImage($_FILES['photo_profil'], 'profils');
                if ($result === false) {
                    $erreurs[] = 'Erreur upload photo. Formats : JPG, PNG, GIF, WEBP. Max 5 Mo.';
                } else {
                    supprimerImage($user['photo_profil'], 'profils');
                    $photo = $result;
                }
            }

            if (empty($erreurs)) {
                $upd = $pdo->prepare("
                    UPDATE utilisateurs
                    SET nom=?, prenom=?, email=?, quartier=?, telephone=?, adresse=?, photo_profil=?
                    WHERE id_utilisateur=?
                ");
                $upd->execute([$nom, $prenom, $email, $quartier ?: null, $tel ?: null, $adresse ?: null, $photo, $userId]);

                // Mettre à jour la session
                $_SESSION['nom']          = $nom;
                $_SESSION['prenom']       = $prenom;
                $_SESSION['email']        = $email;
                $_SESSION['photo_profil'] = $photo;
                $_SESSION['quartier']     = $quartier;

                // Recharger
                $stmt->execute([$userId]);
                $user = $stmt->fetch();
                redirigerAvecMessage('mon_profil.php', 'Profil mis à jour avec succès !', 'success');
            }
        }

        // Changement de mot de passe
        if ($action === 'password') {
            $ancien   = $_POST['ancien_mdp']   ?? '';
            $nouveau  = $_POST['nouveau_mdp']  ?? '';
            $confirm  = $_POST['mdp_confirm']  ?? '';

            if (!password_verify($ancien, $user['mot_de_passe'])) {
                $erreurs[] = 'Mot de passe actuel incorrect.';
            }
            if (strlen($nouveau) < 8) $erreurs[] = 'Le nouveau mot de passe doit faire au moins 8 caractères.';
            if ($nouveau !== $confirm) $erreurs[] = 'Les mots de passe ne correspondent pas.';

            if (empty($erreurs)) {
                $hash = password_hash($nouveau, PASSWORD_BCRYPT, ['cost' => 12]);
                $pdo->prepare('UPDATE utilisateurs SET mot_de_passe = ? WHERE id_utilisateur = ?')
                    ->execute([$hash, $userId]);
                redirigerAvecMessage('mon_profil.php', 'Mot de passe modifié avec succès !', 'success');
            }
        }
    }
}

// Mes annonces
$stmtAnn = $pdo->prepare("
    SELECT a.*, c.nom AS cat_nom, c.icone AS cat_icone
    FROM annonces a
    JOIN categories c ON a.id_categorie = c.id_categorie
    WHERE a.id_utilisateur = ?
    ORDER BY a.date_publication DESC
");
$stmtAnn->execute([$userId]);
$mesAnnonces = $stmtAnn->fetchAll();

require_once '../includes/header.php';
?>

<div class="page-header">
    <h1><i class="fa-solid fa-user-circle"></i> Mon profil</h1>
</div>

<div class="profil-layout">
    <!-- COLONNE GAUCHE : Avatar + infos rapides -->
    <aside class="profil-sidebar">
        <div class="profil-avatar-card">
            <div class="profil-avatar-wrap">
                <img src="<?= imgProfil($user['photo_profil']) ?>"
                     alt="<?= e($user['prenom']) ?>"
                     class="profil-avatar"
                     id="avatarPreview"
                     onerror="this.src='/voisinpartage/assets/images/default.png'">
                <label for="photo_profil_quick" class="avatar-edit-btn" title="Changer la photo">
                    <i class="fa-solid fa-camera"></i>
                </label>
            </div>
            <h2><?= e($user['prenom']) ?> <?= e($user['nom']) ?></h2>
            <?php if ($user['quartier']): ?>
                <p class="profil-quartier">
                    <i class="fa-solid fa-location-dot"></i> <?= e($user['quartier']) ?>
                </p>
            <?php endif; ?>
            <p class="profil-since">
                <i class="fa-solid fa-calendar"></i>
                Membre depuis <?= formaterDateComplete($user['date_inscription']) ?>
            </p>
            <div class="profil-stats">
                <div class="profil-stat">
                    <strong><?= count($mesAnnonces) ?></strong>
                    <span>Annonces</span>
                </div>
                <div class="profil-stat">
                    <strong><?= array_sum(array_column(array_filter($mesAnnonces, fn($a) => $a['statut'] === 'disponible'), 'id_annonce')) > 0
                        ? count(array_filter($mesAnnonces, fn($a) => $a['statut'] === 'disponible'))
                        : 0 ?></strong>
                    <span>Disponibles</span>
                </div>
            </div>
        </div>
    </aside>

    <!-- COLONNE DROITE : Formulaires + annonces -->
    <div class="profil-main">
        <?php if (!empty($erreurs)): ?>
            <div class="alert alert-danger">
                <ul><?php foreach ($erreurs as $e): ?><li><?= e($e) ?></li><?php endforeach; ?></ul>
            </div>
        <?php endif; ?>

        <!-- Infos personnelles -->
        <div class="profil-section">
            <h3><i class="fa-solid fa-user-pen"></i> Informations personnelles</h3>
            <form method="POST" action="mon_profil.php" enctype="multipart/form-data" novalidate>
                <?= champCSRF() ?>
                <input type="hidden" name="action" value="profil">

                <!-- Upload photo caché (déclenché par le bouton avatar) -->
                <input type="file" id="photo_profil_quick" name="photo_profil"
                       accept="image/jpeg,image/png,image/gif,image/webp"
                       style="display:none"
                       onchange="previewAvatar(this)">

                <div class="form-row">
                    <div class="form-group">
                        <label for="prenom">Prénom *</label>
                        <input type="text" id="prenom" name="prenom"
                               value="<?= e($user['prenom']) ?>" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="nom">Nom *</label>
                        <input type="text" id="nom" name="nom"
                               value="<?= e($user['nom']) ?>" class="form-control" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="email">Email *</label>
                    <input type="email" id="email" name="email"
                           value="<?= e($user['email']) ?>" class="form-control" required>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="quartier">Quartier</label>
                        <input type="text" id="quartier" name="quartier"
                               value="<?= e($user['quartier'] ?? '') ?>" class="form-control">
                    </div>
                    <div class="form-group">
                        <label for="telephone">Téléphone</label>
                        <input type="tel" id="telephone" name="telephone"
                               value="<?= e($user['telephone'] ?? '') ?>" class="form-control">
                    </div>
                </div>

                <div class="form-group">
                    <label for="adresse">Adresse</label>
                    <input type="text" id="adresse" name="adresse"
                           value="<?= e($user['adresse'] ?? '') ?>" class="form-control">
                </div>

                <button type="submit" class="btn btn-primary">
                    <i class="fa-solid fa-floppy-disk"></i> Enregistrer
                </button>
            </form>
        </div>

        <!-- Mot de passe -->
        <div class="profil-section">
            <h3><i class="fa-solid fa-lock"></i> Changer le mot de passe</h3>
            <form method="POST" action="mon_profil.php" novalidate>
                <?= champCSRF() ?>
                <input type="hidden" name="action" value="password">
                <div class="form-group">
                    <label for="ancien_mdp">Mot de passe actuel</label>
                    <input type="password" id="ancien_mdp" name="ancien_mdp" class="form-control" required>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="nouveau_mdp">Nouveau mot de passe</label>
                        <input type="password" id="nouveau_mdp" name="nouveau_mdp"
                               class="form-control" required minlength="8">
                    </div>
                    <div class="form-group">
                        <label for="mdp_confirm">Confirmer</label>
                        <input type="password" id="mdp_confirm" name="mdp_confirm"
                               class="form-control" required>
                    </div>
                </div>
                <button type="submit" class="btn btn-warning">
                    <i class="fa-solid fa-key"></i> Changer le mot de passe
                </button>
            </form>
        </div>

        <!-- Mes annonces -->
        <div class="profil-section">
            <div class="section-header">
                <h3><i class="fa-solid fa-boxes-stacked"></i> Mes annonces (<?= count($mesAnnonces) ?>)</h3>
                <a href="../annonces/creer.php" class="btn btn-sm btn-primary">
                    <i class="fa-solid fa-plus"></i> Nouvelle annonce
                </a>
            </div>

            <?php if (empty($mesAnnonces)): ?>
                <div class="empty-state">
                    <i class="fa-solid fa-box-open"></i>
                    <p>Vous n'avez pas encore d'annonces.</p>
                    <a href="../annonces/creer.php" class="btn btn-primary">Publier ma première annonce</a>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Annonce</th>
                                <th>Type</th>
                                <th>Statut</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($mesAnnonces as $a): ?>
                                <tr>
                                    <td>
                                        <div class="table-annonce">
                                            <img src="<?= imgAnnonce($a['photo']) ?>"
                                                 alt="<?= e($a['titre']) ?>"
                                                 class="table-thumb">
                                            <div>
                                                <a href="../annonces/detail.php?id=<?= $a['id_annonce'] ?>">
                                                    <?= e($a['titre']) ?>
                                                </a>
                                                <small><?= e($a['cat_nom']) ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge badge-type-<?= $a['type'] ?>">
                                            <?= $a['type'] === 'pret' ? 'Prêt' : 'Don' ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge badge-statut-<?= $a['statut'] ?>">
                                            <?= match($a['statut']) {
                                                'disponible'   => 'Disponible',
                                                'reserve'      => 'Réservé',
                                                'indisponible' => 'Indisponible',
                                            } ?>
                                        </span>
                                    </td>
                                    <td><?= formaterDate($a['date_publication']) ?></td>
                                    <td class="table-actions">
                                        <a href="../annonces/modifier.php?id=<?= $a['id_annonce'] ?>"
                                           class="btn btn-sm btn-warning" title="Modifier">
                                            <i class="fa-solid fa-pen"></i>
                                        </a>
                                        <a href="../annonces/supprimer.php?id=<?= $a['id_annonce'] ?>"
                                           class="btn btn-sm btn-danger" title="Supprimer"
                                           onclick="return confirm('Supprimer cette annonce ?')">
                                            <i class="fa-solid fa-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function previewAvatar(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = e => {
            document.getElementById('avatarPreview').src = e.target.result;
        };
        reader.readAsDataURL(input.files[0]);
        // Soumettre le formulaire parent
        input.closest('form').submit();
    }
}
</script>

<?php require_once '../includes/footer.php'; ?>