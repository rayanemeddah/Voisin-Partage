<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

exigerConnexion();
$pageTitle = 'Publier une annonce — VoisinPartage';
$pdo = getDB();
$erreurs = [];
$valeurs = [];

$categories = $pdo->query('SELECT * FROM categories ORDER BY nom')->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifierTokenCSRF($_POST['csrf_token'] ?? '')) {
        $erreurs[] = 'Token de sécurité invalide.';
    } else {
        $titre       = trim($_POST['titre']       ?? '');
        $description = trim($_POST['description'] ?? '');
        $type        = $_POST['type']             ?? '';
        $categorie   = (int)($_POST['id_categorie'] ?? 0);
        $valeurs     = compact('titre', 'description', 'type', 'categorie');

        if (empty($titre))       $erreurs[] = 'Le titre est obligatoire.';
        if (strlen($titre) > 255) $erreurs[] = 'Le titre ne doit pas dépasser 255 caractères.';
        if (empty($description)) $erreurs[] = 'La description est obligatoire.';
        if (!in_array($type, ['pret', 'don'], true)) $erreurs[] = 'Type invalide.';
        if ($categorie <= 0)     $erreurs[] = 'Veuillez sélectionner une catégorie.';

        // Photo (optionnelle)
        $photo = 'no-image.png';
        if (!empty($_FILES['photo']['name'])) {
            $result = uploaderImage($_FILES['photo'], 'annonces');
            if ($result === false) {
                $erreurs[] = 'Erreur lors de l\'upload de la photo. Formats acceptés : JPG, PNG, GIF, WEBP. Taille max : 5 Mo.';
            } else {
                $photo = $result;
            }
        }

        if (empty($erreurs)) {
            $stmt = $pdo->prepare("
                INSERT INTO annonces (titre, description, type, photo, id_utilisateur, id_categorie)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$titre, $description, $type, $photo, $_SESSION['user_id'], $categorie]);
            $newId = $pdo->lastInsertId();
            redirigerAvecMessage("detail.php?id={$newId}", 'Annonce publiée avec succès !', 'success');
        }
    }
}

require_once '../includes/header.php';
?>

<div class="page-header">
    <h1><i class="fa-solid fa-plus-circle"></i> Publier une annonce</h1>
    <p>Partagez un objet avec vos voisins</p>
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

        <form method="POST" action="creer.php" enctype="multipart/form-data" novalidate>
            <?= champCSRF() ?>

            <div class="form-group">
                <label for="titre"><i class="fa-solid fa-heading"></i> Titre de l'annonce *</label>
                <input type="text" id="titre" name="titre"
                       value="<?= e($valeurs['titre'] ?? '') ?>"
                       class="form-control" required maxlength="255"
                       placeholder="Ex: Perceuse Bosch sans fil">
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="type"><i class="fa-solid fa-arrow-right-arrow-left"></i> Type *</label>
                    <select id="type" name="type" class="form-control" required>
                        <option value="">-- Choisir --</option>
                        <option value="pret" <?= ($valeurs['type'] ?? '') === 'pret' ? 'selected' : '' ?>>
                            🔄 Prêt (je prête, je récupère)
                        </option>
                        <option value="don" <?= ($valeurs['type'] ?? '') === 'don' ? 'selected' : '' ?>>
                            🎁 Don (je donne définitivement)
                        </option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="id_categorie"><i class="fa-solid fa-tag"></i> Catégorie *</label>
                    <select id="id_categorie" name="id_categorie" class="form-control" required>
                        <option value="">-- Choisir --</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['id_categorie'] ?>"
                                <?= ((int)($valeurs['categorie'] ?? 0)) === (int)$cat['id_categorie'] ? 'selected' : '' ?>>
                                <?= e($cat['nom']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label for="description"><i class="fa-solid fa-align-left"></i> Description *</label>
                <textarea id="description" name="description" class="form-control"
                          rows="6" required
                          placeholder="Décrivez l'objet : état, dimensions, conditions d'emprunt..."><?= e($valeurs['description'] ?? '') ?></textarea>
            </div>

            <div class="form-group">
                <label for="photo"><i class="fa-solid fa-camera"></i> Photo (optionnelle)</label>
                <div class="upload-zone" id="uploadZone">
                    <input type="file" id="photo" name="photo"
                           accept="image/jpeg,image/png,image/gif,image/webp"
                           class="upload-input">
                    <div class="upload-placeholder">
                        <i class="fa-solid fa-cloud-arrow-up"></i>
                        <p>Glissez une image ici ou cliquez pour sélectionner</p>
                        <small>JPG, PNG, GIF, WEBP — Max 5 Mo</small>
                    </div>
                    <img id="previewImage" class="upload-preview" src="" alt="Aperçu" style="display:none">
                </div>
            </div>

            <div class="form-actions">
                <a href="liste.php" class="btn btn-outline">
                    <i class="fa-solid fa-xmark"></i> Annuler
                </a>
                <button type="submit" class="btn btn-primary btn-lg">
                    <i class="fa-solid fa-paper-plane"></i> Publier l'annonce
                </button>
            </div>
        </form>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>