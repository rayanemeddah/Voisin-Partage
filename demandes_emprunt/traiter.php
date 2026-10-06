<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

exigerConnexion();
$pdo = getDB();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../index.php');
    exit;
}

if (!verifierTokenCSRF($_POST['csrf_token'] ?? '')) {
    redirigerAvecMessage('../index.php', 'Token invalide.', 'danger');
}

$action    = $_POST['action']     ?? '';
$demandeId = (int)($_POST['id_demande'] ?? 0);
$userId    = (int)$_SESSION['user_id'];

if (!$demandeId) {
    redirigerAvecMessage('../index.php', 'Demande invalide.', 'danger');
}

// Récupérer la demande avec l'annonce
$stmt = $pdo->prepare("
    SELECT d.*, a.id_utilisateur AS proprio_id, a.id_annonce
    FROM demandes_emprunt d
    JOIN annonces a ON d.id_annonce = a.id_annonce
    WHERE d.id_demande = ?
");
$stmt->execute([$demandeId]);
$demande = $stmt->fetch();

if (!$demande) {
    redirigerAvecMessage('../index.php', 'Demande introuvable.', 'danger');
}

// Actions pour le propriétaire de l'annonce
if (in_array($action, ['accepter', 'refuser', 'terminer'], true)) {
    if ((int)$demande['proprio_id'] !== $userId && !estAdmin()) {
        redirigerAvecMessage('../index.php', 'Non autorisé.', 'danger');
    }

    $nouveauStatut = match($action) {
        'accepter' => 'acceptee',
        'refuser'  => 'refusee',
        'terminer' => 'terminee',
    };

    $pdo->prepare('UPDATE demandes_emprunt SET statut = ? WHERE id_demande = ?')
        ->execute([$nouveauStatut, $demandeId]);

    // Si acceptée, passer l'annonce en "reserve"
    if ($action === 'accepter') {
        $pdo->prepare("UPDATE annonces SET statut = 'reserve' WHERE id_annonce = ?")
            ->execute([$demande['id_annonce']]);
    }
    // Si terminée, passer l'annonce en "indisponible"
    if ($action === 'terminer') {
        $pdo->prepare("UPDATE annonces SET statut = 'indisponible' WHERE id_annonce = ?")
            ->execute([$demande['id_annonce']]);
    }

    redirigerAvecMessage(
        "../annonces/detail.php?id={$demande['id_annonce']}",
        'Demande mise à jour.',
        'success'
    );
}

// Action pour l'emprunteur : annuler
if ($action === 'annuler') {
    if ((int)$demande['id_emprunteur'] !== $userId) {
        redirigerAvecMessage('../index.php', 'Non autorisé.', 'danger');
    }
    $pdo->prepare('DELETE FROM demandes_emprunt WHERE id_demande = ?')->execute([$demandeId]);
    redirigerAvecMessage(
        "../annonces/detail.php?id={$demande['id_annonce']}",
        'Demande annulée.',
        'success'
    );
}

redirigerAvecMessage('../index.php', 'Action invalide.', 'danger');