<?php
// fonctions.php - Fonction utilitaire réutilisables

// Formate une date Y-m-d en d/m/Y, retourne '-' si vide

function formaterDate(?string $date): string{
    if (empty($date)) return '-';
    return date('d/m/Y', strtotime($date));}

// Affiche Oui / Non / - selon la valeur de decharge_signee
function afficherDecharge($valeur): string{
    if ($valeur === null) return '-';
    return (int)$valeur === 1 ? 'Oui' : 'Non';}

function genererNumeroTrousseau(PDO $pdo): string {
    try {
        $dernierNumero = $pdo->query(
            "SELECT numero_trousseau FROM trousseaux ORDER BY id_trousseau DESC LIMIT 1"
        )->fetchColumn();

        if ($dernierNumero && preg_match('/TR-(\d+)$/i', $dernierNumero, $matches)) {
            return 'TR-' . str_pad((int)$matches[1] + 1, 3, '0', STR_PAD_LEFT);
        }
    } catch (PDOException $e) {
        // on retombe sur la valeur par défaut en cas d'erreur
    }

    return 'TR-001';
}

//Afficher la card du message si non vide
function afficherMessage(string $message): void{
    if ($message === '') return;
    echo '<div class = "card"><p>' . htmlspecialchars($message) . '</p></div>';}
