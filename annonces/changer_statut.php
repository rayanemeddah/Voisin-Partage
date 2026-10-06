<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

exigerConnexion();
$pdo = getDB();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: liste.php');
    exit;
}

if (!verifierTokenCSRF($_POST['csrf_token'] ?? '')) {
    redirigerAvecMessage('liste.php', 'Token de sécurité invalide.', 'danger');
}

$id     = (int)($_POST['id_annonce'] ?? 0);
$statut = $_POST['statut'] ?? '';
$valid  = ['disponible', 'reserve', 'indisponible'];

if (!$id || !in_array($statut, $valid, true)) {
    redirigerAvecMessage('liste.php', 'Données invalides.', 'danger');
}

$stmt = $pdo->prepare('SELECT id_utilisateur FROM annonces WHERE id_annonce = ?');
$stmt->execute([$id]);
$annonce = $stmt->fetch();

if (!$annonce) {
    redirigerAvecMessage('liste.php', 'Annonce introuvable.', 'danger');
}

if ((int)$annonce['id_utilisateur'] !== (int)$_SESSION['user_id'] && !estAdmin()) {
    redirigerAvecMessage('liste.php', 'Action non autorisée.', 'danger');
}

$upd = $pdo->prepare('UPDATE annonces SET statut = ? WHERE id_annonce = ?');
$upd->execute([$statut, $id]);

$redirect = $_POST['redirect'] ?? 'liste.php';
$redirectSafe = filter_var($redirect, FILTER_SANITIZE_URL);
redirigerAvecMessage($redirectSafe, 'Statut mis à jour.', 'success');