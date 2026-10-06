<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

exigerConnexion();
$pdo = getDB();
$id  = (int)($_GET['id'] ?? 0);

if (!$id) {
    redirigerAvecMessage('liste.php', 'Annonce invalide.', 'danger');
}

$stmt = $pdo->prepare('SELECT * FROM annonces WHERE id_annonce = ?');
$stmt->execute([$id]);
$annonce = $stmt->fetch();

if (!$annonce) {
    redirigerAvecMessage('liste.php', 'Annonce introuvable.', 'danger');
}

if ((int)$annonce['id_utilisateur'] !== (int)$_SESSION['user_id'] && !estAdmin()) {
    redirigerAvecMessage('liste.php', 'Action non autorisée.', 'danger');
}

// Supprimer la photo
supprimerImage($annonce['photo'], 'annonces');

// Supprimer l'annonce
$del = $pdo->prepare('DELETE FROM annonces WHERE id_annonce = ?');
$del->execute([$id]);

$redirect = estAdmin() ? '../admin/annonces.php' : '../profil/mon_profil.php';
redirigerAvecMessage($redirect, 'Annonce supprimée avec succès.', 'success');