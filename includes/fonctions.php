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

//Afficher la card du message si non vide
function afficherMessage(string $message): void{
    if ($message === '') return;
    echo '<div class = "card"><p>' . htmlspecialchars($message) . '</p></div>';}