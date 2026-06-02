<?php
if (!isset($page_title)) {
    $page_title = "Application gestion Badge / Clé";
}
?>
<!-- header.php - En-tête commun à toutes les pages -->

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title) ?> — Gestion Badge / Clé</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<header>
    Application gestion Badge / Clé
</header>

<nav>
    <a href="index.php">Tableau de bord</a>
    <a href="personnes.php">Personnes</a>
    <a href="trousseaux.php">Trousseaux</a>
    <a href="inventaire.php">Inventaire</a>
    <a href="historique.php">Historique</a>
    <a href="recherche.php">Recherche</a>
</nav>

<main>
