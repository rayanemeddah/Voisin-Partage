<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

exigerConnexion();
$pageTitle = 'Modifier l\'annonce — VoisinPartage';
$pdo = getDB();
$id  = (int)($_GET['id'] ?? 0);

// Récupérer l'annonce
$stmt = $pdo->prepare('SELECT * FROM annonces WHERE id_annonce = ?');
$stmt->execute([$id]);
$annonce = $stmt->fetch();

if (!$annonce) {
    redirigerAvecMessage('liste.php', 'Annonce introuvable.', 'danger');
}

// Autorisation : propriétaire ou admin
if ((int)$annonce['id_utilisateur'] !== (int)$_SESSION['user_id'] && !estAdmin()) {
    redirigerAvecMessage('liste.php', 'Vous n\'avez pas la permission de modifier cette annonce.', 'danger');
}

$categories = $pdo->query('SELECT * FROM categories ORDER BY nom')->fetchAll();
$erreurs    = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifierTokenCSRF($_POST['csrf_token'] ?? '')) {
        $erreurs[] = 'Token de sécurité invalide.';
    } else {
        $titre       = trim($_POST['titre']       ?? '');
        $description = trim($_POST['description'] ?? '');
        $type        = $_POST['type']             ?? '';
        $statut      = $_POST['statut']           ?? '';
        $categorie   = (int)($_POST['id_categorie'] ?? 0);

        if (empty($titre))       $erreurs[] = 'Le titre est obligatoire.';
        if (empty($description)) $erreurs[] = 'La description est obligatoire.';
        if (!in_array($type,   ['pret', 'don'], true))                    $erreurs[] = 'Type invalide.';
        if (!in_array($statut, ['disponible', 'reserve', 'indisponible'], true)) $erreurs[] = 'Statut invalide.';
        if ($categorie <= 0)     $erreurs[] = 'Catégorie invalide.';

        // Nouvelle photo ?
        $photo = $annonce['photo'];
        if (!empty($_FILES['photo']['name'])) {
            $result = uploaderImage($_FILES['photo'], 'annonces');
            if ($result === false) {
                $erreurs[] = 'Erreur upload photo. Formats : JPG, PNG, GIF, WEBP. Max 5 Mo.';
            } else {
                supprimerImage($annonce['photo'], 'annonces');
                $photo = $result;
            }
        }

        if (empty($erreurs)) {
            $upd = $pdo->prepare("
                UPDATE annonces
                SET titre=?, description=?, type=?, statut=?, photo=?, id_categorie=?
                WHERE id_annonce=?
            ");
            $upd->execute([$titre, $description, $type, $statut, $photo, $categorie, $id]);
            redirigerAvecMessage("detail.php?id={$id}", 'Annonce modifiée avec succès !', 'success');
        } else {
            $annonce = array_merge($annonce, compact('titre', 'description', 'type', 'statut', 'categorie'));
        }
    }
}

require_once '../includes/header.php';
?>

<div class="page-header">
    <h1><i class="fa-solid fa-pen"></i> Modifier l'annonce</h1>
</div>

<div class="form-container">
    <div class="form-card">
        <?php if (!empty($erreurs)): ?>
            <div class="alert alert-danger">
                <ul>
                    <?php foreach ($erreurs as $e): ?>
                        <li><?= e($e) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="POST" action="modifier.php?id=<?= $id ?>" enctype="multipart/form-data" novalidate>
            <?= champCSRF() ?>

            <div class="form-group">
                <label for="titre">Titre *</label>
                <input type="text" id="titre" name="titre"
                       value="<?= e($annonce['titre']) ?>"
                       class="form-control" required maxlength="255">
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="type">Type *</label>
                    <select id="type" name="type" class="form-control">
                        <option value="pret" <?= $annonce['type'] === 'pret' ? 'selected' : '' ?>>🔄 Prêt</option>
                        <option value="don"  <?= $annonce['type'] === 'don'  ? 'selected' : '' ?>>🎁 Don</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="statut">Statut</label>
                    <select id="statut" name="statut" class="form-control">
                        <option value="disponible"   <?= $annonce['statut'] === 'disponible'   ? 'selected' : '' ?>>✅ Disponible</option>
                        <option value="reserve"      <?= $annonce['statut'] === 'reserve'      ? 'selected' : '' ?>>⏳ Réservé</option>
                        <option value="indisponible" <?= $annonce['statut'] === 'indisponible' ? 'selected' : '' ?>>❌ Indisponible</option>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label for="id_categorie">Catégorie *</label>
                <select id="id_categorie" name="id_categorie" class="form-control">
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['id_categorie'] ?>"
                            <?= (int)$annonce['id_categorie'] === (int)$cat['id_categorie'] ? 'selected' : '' ?>>
                            <?= e($cat['nom']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="description">Description *</label>
                <textarea id="description" name="description"
                          class="form-control" rows="6" required><?= e($annonce['description']) ?></textarea>
            </div>

            <div class="form-group">
                <label>Photo actuelle</label>
                <div class="current-photo">
                    <img src="<?= imgAnnonce($annonce['photo']) ?>"
                         alt="Photo actuelle"
                         style="max-height:200px;border-radius:8px;">
                </div>
            </div>

            <div class="form-group">
                <label for="photo">Nouvelle photo (optionnel, remplace l'actuelle)</label>
                <div class="upload-zone" id="uploadZone">
                    <input type="file" id="photo" name="photo"
                           accept="image/jpeg,image/png,image/gif,image/webp"
                           class="upload-input">
                    <div class="upload-placeholder">
                        <i class="fa-solid fa-cloud-arrow-up"></i>
                        <p>Sélectionner une nouvelle image</p>
                        <small>JPG, PNG, GIF, WEBP — Max 5 Mo</small>
                    </div>
                    <img id="previewImage" class="upload-preview" src="" alt="" style="display:none">
                </div>
            </div>

            <div class="form-actions">
                <a href="detail.php?id=<?= $id ?>" class="btn btn-outline">
                    <i class="fa-solid fa-xmark"></i> Annuler
                </a>
                <button type="submit" class="btn btn-warning btn-lg">
                    <i class="fa-solid fa-floppy-disk"></i> Enregistrer les modifications
                </button>
            </div>
        </form>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>