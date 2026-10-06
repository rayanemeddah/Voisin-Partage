<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

if (estConnecte()) {
    header('Location: index.php');
    exit;
}

$pageTitle = 'Connexion — VoisinPartage';
$erreur    = '';
$email     = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifierTokenCSRF($_POST['csrf_token'] ?? '')) {
        $erreur = 'Token de sécurité invalide. Veuillez réessayer.';
    } else {
        $email = trim($_POST['email']        ?? '');
        $mdp   = $_POST['mot_de_passe']      ?? '';

        if (empty($email) || empty($mdp)) {
            $erreur = 'Veuillez remplir tous les champs.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $erreur = 'Adresse email invalide.';
        } else {
            $pdo  = getDB();
            $stmt = $pdo->prepare('SELECT * FROM utilisateurs WHERE email = ?');
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if (!$user || !password_verify($mdp, $user['mot_de_passe'])) {
                $erreur = 'Email ou mot de passe incorrect.';
            } elseif (!$user['actif']) {
                $erreur = 'Votre compte a été désactivé. Contactez un administrateur.';
            } else {
                // Régénérer l'ID de session (sécurité)
                session_regenerate_id(true);

                $_SESSION['user_id']     = $user['id_utilisateur'];
                $_SESSION['nom']         = $user['nom'];
                $_SESSION['prenom']      = $user['prenom'];
                $_SESSION['email']       = $user['email'];
                $_SESSION['role']        = $user['role'];
                $_SESSION['photo_profil']= $user['photo_profil'];
                $_SESSION['quartier']    = $user['quartier'];

                redirigerAvecMessage('index.php', 'Bienvenue, ' . $user['prenom'] . ' !', 'success');
            }
        }
    }
}

require_once 'includes/header.php';
?>

<div class="auth-container">
    <div class="auth-card">
        <div class="auth-header">
            <i class="fa-solid fa-right-to-bracket auth-icon"></i>
            <h1>Se connecter</h1>
            <p>Content de vous revoir !</p>
        </div>

        <?php if ($erreur): ?>
            <div class="alert alert-danger">
                <span class="alert-icon">✕</span> <?= e($erreur) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="connexion.php" novalidate>
            <?= champCSRF() ?>

            <div class="form-group">
                <label for="email"><i class="fa-solid fa-envelope"></i> Adresse email</label>
                <input type="email" id="email" name="email"
                       value="<?= e($email) ?>"
                       class="form-control" required autocomplete="email" autofocus>
            </div>

            <div class="form-group">
                <label for="mot_de_passe"><i class="fa-solid fa-lock"></i> Mot de passe</label>
                <div class="input-password">
                    <input type="password" id="mot_de_passe" name="mot_de_passe"
                           class="form-control" required autocomplete="current-password">
                    <button type="button" class="toggle-password" data-target="mot_de_passe">
                        <i class="fa-solid fa-eye"></i>
                    </button>
                </div>
            </div>

            <button type="submit" class="btn btn-primary btn-full btn-lg">
                <i class="fa-solid fa-right-to-bracket"></i> Se connecter
            </button>
        </form>

        <div class="auth-footer">
            Pas encore de compte ? <a href="inscription.php">S'inscrire gratuitement</a>
        </div>

        <!-- Accès rapide test -->
        <div class="demo-credentials">
            <p><strong>Comptes de test :</strong></p>
            <p>Admin : <code>toufik.ait@email.com</code> / <code>Toufik1234</code></p>
            <p>User : <code>rayane.med@email.com</code> / <code>Toufik1234</code></p>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>