<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

if (estConnecte()) {
    header('Location: index.php');
    exit;
}

$pageTitle = 'Inscription — VoisinPartage';
$erreurs   = [];
$valeurs   = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Vérification CSRF
    if (!verifierTokenCSRF($_POST['csrf_token'] ?? '')) {
        $erreurs[] = 'Token de sécurité invalide. Veuillez réessayer.';
    } else {
        $nom      = trim($_POST['nom']      ?? '');
        $prenom   = trim($_POST['prenom']   ?? '');
        $email    = trim($_POST['email']    ?? '');
        $mdp      = $_POST['mot_de_passe']  ?? '';
        $mdpConf  = $_POST['mdp_confirm']   ?? '';
        $quartier = trim($_POST['quartier'] ?? '');
        $tel      = trim($_POST['telephone'] ?? '');

        $valeurs = compact('nom', 'prenom', 'email', 'quartier', 'tel');

        // Validations
        if (empty($nom))    $erreurs[] = 'Le nom est obligatoire.';
        if (empty($prenom)) $erreurs[] = 'Le prénom est obligatoire.';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $erreurs[] = 'Adresse email invalide.';
        if (strlen($mdp) < 8) $erreurs[] = 'Le mot de passe doit contenir au moins 8 caractères.';
        if ($mdp !== $mdpConf) $erreurs[] = 'Les mots de passe ne correspondent pas.';

        if (empty($erreurs)) {
            $pdo = getDB();
            // Email unique
            $stmt = $pdo->prepare('SELECT id_utilisateur FROM utilisateurs WHERE email = ?');
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $erreurs[] = 'Cette adresse email est déjà utilisée.';
            } else {
                $hash = password_hash($mdp, PASSWORD_BCRYPT, ['cost' => 12]);
                $ins  = $pdo->prepare("
                    INSERT INTO utilisateurs (nom, prenom, email, mot_de_passe, telephone, quartier)
                    VALUES (?, ?, ?, ?, ?, ?)
                ");
                $ins->execute([$nom, $prenom, $email, $hash, $tel ?: null, $quartier ?: null]);
                redirigerAvecMessage('connexion.php', 'Compte créé avec succès ! Vous pouvez maintenant vous connecter.', 'success');
            }
        }
    }
}

require_once 'includes/header.php';
?>

<div class="auth-container">
    <div class="auth-card">
        <div class="auth-header">
            <i class="fa-solid fa-user-plus auth-icon"></i>
            <h1>Créer un compte</h1>
            <p>Rejoignez la communauté VoisinPartage</p>
        </div>

        <?php if (!empty($erreurs)): ?>
            <div class="alert alert-danger">
                <ul class="mb-0">
                    <?php foreach ($erreurs as $e): ?>
                        <li><?= e($e) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="POST" action="inscription.php" novalidate>
            <?= champCSRF() ?>

            <div class="form-row">
                <div class="form-group">
                    <label for="prenom"><i class="fa-solid fa-user"></i> Prénom *</label>
                    <input type="text" id="prenom" name="prenom"
                           value="<?= e($valeurs['prenom'] ?? '') ?>"
                           class="form-control" required autocomplete="given-name">
                </div>
                <div class="form-group">
                    <label for="nom"><i class="fa-solid fa-user"></i> Nom *</label>
                    <input type="text" id="nom" name="nom"
                           value="<?= e($valeurs['nom'] ?? '') ?>"
                           class="form-control" required autocomplete="family-name">
                </div>
            </div>

            <div class="form-group">
                <label for="email"><i class="fa-solid fa-envelope"></i> Adresse email *</label>
                <input type="email" id="email" name="email"
                       value="<?= e($valeurs['email'] ?? '') ?>"
                       class="form-control" required autocomplete="email">
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="mot_de_passe"><i class="fa-solid fa-lock"></i> Mot de passe *</label>
                    <div class="input-password">
                        <input type="password" id="mot_de_passe" name="mot_de_passe"
                               class="form-control" required autocomplete="new-password"
                               minlength="8">
                        <button type="button" class="toggle-password" data-target="mot_de_passe">
                            <i class="fa-solid fa-eye"></i>
                        </button>
                    </div>
                    <small class="form-hint">Minimum 8 caractères</small>
                </div>
                <div class="form-group">
                    <label for="mdp_confirm"><i class="fa-solid fa-lock"></i> Confirmer *</label>
                    <div class="input-password">
                        <input type="password" id="mdp_confirm" name="mdp_confirm"
                               class="form-control" required autocomplete="new-password">
                        <button type="button" class="toggle-password" data-target="mdp_confirm">
                            <i class="fa-solid fa-eye"></i>
                        </button>
                    </div>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="quartier"><i class="fa-solid fa-map-marker-alt"></i> Quartier</label>
                    <input type="text" id="quartier" name="quartier"
                           value="<?= e($valeurs['quartier'] ?? '') ?>"
                           class="form-control" placeholder="Ex: Centre-ville">
                </div>
                <div class="form-group">
                    <label for="telephone"><i class="fa-solid fa-phone"></i> Téléphone</label>
                    <input type="tel" id="telephone" name="telephone"
                           value="<?= e($valeurs['tel'] ?? '') ?>"
                           class="form-control" placeholder="06 XX XX XX XX">
                </div>
            </div>

            <button type="submit" class="btn btn-primary btn-full btn-lg">
                <i class="fa-solid fa-user-plus"></i> Créer mon compte
            </button>
        </form>

        <div class="auth-footer">
            Déjà inscrit ? <a href="connexion.php">Se connecter</a>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>