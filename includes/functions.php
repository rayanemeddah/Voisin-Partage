<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ============================================================
// SÉCURITÉ — CONNEXION / RÔLES
// ============================================================

function exigerConnexion(): void {
    if (empty($_SESSION['user_id'])) {
        redirigerAvecMessage('../connexion.php', 'Vous devez être connecté pour accéder à cette page.', 'warning');
    }
}

function exigerAdmin(): void {
    exigerConnexion();
    if (($_SESSION['role'] ?? '') !== 'administrateur') {
        redirigerAvecMessage('../index.php', 'Accès réservé aux administrateurs.', 'danger');
    }
}

function estConnecte(): bool {
    return !empty($_SESSION['user_id']);
}

function estAdmin(): bool {
    return ($_SESSION['role'] ?? '') === 'administrateur';
}

// ============================================================
// CSRF
// ============================================================

function genererTokenCSRF(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifierTokenCSRF(string $token): bool {
    if (empty($_SESSION['csrf_token'])) return false;
    return hash_equals($_SESSION['csrf_token'], $token);
}

function champCSRF(): string {
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(genererTokenCSRF()) . '">';
}

// ============================================================
// FLASH MESSAGES
// ============================================================

function redirigerAvecMessage(string $url, string $message, string $type = 'success'): void {
    $_SESSION['flash'] = ['message' => $message, 'type' => $type];
    header('Location: ' . $url);
    exit;
}

function afficherFlash(): string {
    if (empty($_SESSION['flash'])) return '';
    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);
    $type = htmlspecialchars($flash['type']);
    $message = htmlspecialchars($flash['message']);
    $icons = [
        'success' => '✓',
        'danger'  => '✕',
        'warning' => '⚠',
        'info'    => 'ℹ',
    ];
    $icon = $icons[$flash['type']] ?? 'ℹ';
    return "<div class=\"alert alert-{$type}\"><span class=\"alert-icon\">{$icon}</span> {$message}</div>";
}

// ============================================================
// UPLOAD IMAGE
// ============================================================

function uploaderImage(array $file, string $dossier): string|false {
    if ($file['error'] !== UPLOAD_ERR_OK) return false;

    // Taille max : 5 Mo
    if ($file['size'] > 5 * 1024 * 1024) return false;

    // Vérification MIME réelle
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime  = $finfo->file($file['tmp_name']);
    $mimeAutorisés = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    if (!in_array($mime, $mimeAutorisés, true)) return false;

    $extensions = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/gif'  => 'gif',
        'image/webp' => 'webp',
    ];
    $ext      = $extensions[$mime];
    $nomFich  = uniqid('img_', true) . '.' . $ext;

    // Chemin absolu depuis la racine du projet
    $basePath = dirname(__DIR__) . '/assets/images/uploads/' . $dossier . '/';
    if (!is_dir($basePath)) {
        mkdir($basePath, 0755, true);
    }

    if (move_uploaded_file($file['tmp_name'], $basePath . $nomFich)) {
        return $nomFich;
    }
    return false;
}

function supprimerImage(string $nomFich, string $dossier): void {
    if ($nomFich === 'no-image.png' || $nomFich === 'default.png') return;
    $chemin = dirname(__DIR__) . '/assets/images/uploads/' . $dossier . '/' . $nomFich;
    if (file_exists($chemin)) {
        unlink($chemin);
    }
}

// ============================================================
// FORMATAGE DATE
// ============================================================

function formaterDate(string $dateStr): string {
    $now  = new DateTime();
    $date = new DateTime($dateStr);
    $diff = $now->diff($date);

    if ($diff->y > 0) return 'il y a ' . $diff->y . ' an' . ($diff->y > 1 ? 's' : '');
    if ($diff->m > 0) return 'il y a ' . $diff->m . ' mois';
    if ($diff->d > 0) return 'il y a ' . $diff->d . ' jour' . ($diff->d > 1 ? 's' : '');
    if ($diff->h > 0) return 'il y a ' . $diff->h . ' heure' . ($diff->h > 1 ? 's' : '');
    if ($diff->i > 0) return 'il y a ' . $diff->i . ' minute' . ($diff->i > 1 ? 's' : '');
    return 'à l\'instant';
}

function formaterDateComplete(string $dateStr): string {
    $date = new DateTime($dateStr);
    $mois = [
        1  => 'janvier', 2  => 'février',   3  => 'mars',
        4  => 'avril',   5  => 'mai',        6  => 'juin',
        7  => 'juillet', 8  => 'août',       9  => 'septembre',
        10 => 'octobre', 11 => 'novembre',   12 => 'décembre',
    ];
    return $date->format('d') . ' ' . $mois[(int)$date->format('n')] . ' ' . $date->format('Y');
}

// ============================================================
// MESSAGERIE
// ============================================================

function compterMessagesNonLus(int $userId): int {
    $pdo  = getDB();
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM messages WHERE id_destinataire = ? AND lu = 0');
    $stmt->execute([$userId]);
    return (int)$stmt->fetchColumn();
}

// ============================================================
// ÉCHAPPEMENT XSS
// ============================================================

function e(string $str): string {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

// ============================================================
// CHEMIN IMAGE ANNONCE / PROFIL
// ============================================================

// ============================================================
// CHEMINS IMAGES (Génère le bon lien URL pour les images)
// ============================================================

function getBaseUrl(): string {
    static $base = null;
    if ($base !== null) return $base;

    // Récupère le dossier racine du projet sur le serveur (ex: /voisinpartage)
    $docRoot = rtrim(str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']), '/');
    $selfDir = str_replace('\\', '/', dirname(__DIR__)); 

    $base = str_replace($docRoot, '', $selfDir);

    // Si on est à la racine absolue du serveur, on s'assure que ce n'est pas vide
    if (empty($base)) {
        $base = '';
    }

    return $base;
}

function imgAnnonce(string $photo): string {
    $base = getBaseUrl();

    // Si l'annonce n'a pas de photo, on affiche l'image par défaut
    if (empty($photo) || $photo === 'no-image.png') {
        return $base . '/assets/images/no-image.png';
    }

    // Sinon on renvoie le lien vers le dossier uploads
    return $base . '/assets/images/uploads/annonces/' . $photo;
}

function imgProfil(string $photo): string {
    $base = getBaseUrl();

    // Si l'utilisateur n'a pas de photo, on affiche l'avatar par défaut
    if (empty($photo) || $photo === 'default.png') {
        return $base . '/assets/images/default.png';
    }

    // Sinon on renvoie le lien vers sa photo
    return $base . '/assets/images/uploads/profils/' . $photo;
}
