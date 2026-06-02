<?php
require_once 'config/database.php';
require_once 'includes/fonctions.php';

$page_title = "Tableau de bord";
include 'includes/header.php';

// on récupère des statistiques pour le résumé du tableau de bord
try {
    $nb_personnes = $pdo->query("SELECT COUNT(*) FROM personnes")->fetchColumn();
    $nb_trousseaux_attribues = $pdo->query("SELECT COUNT(*) FROM trousseaux WHERE statut = 'Attribué'")->fetchColumn();
    $nb_trousseaux_disponibles = $pdo->query("SELECT COUNT(*) FROM trousseaux WHERE statut = 'Disponible'")->fetchColumn();
    $nb_trousseaux_perdus = $pdo->query("SELECT COUNT(*) FROM trousseaux WHERE statut = 'Perdu'")->fetchColumn();
    $nb_decharges_manquantes = $pdo->query("SELECT Count(*) FROM historique_trousseaux WHERE date_restitution IS NULL and decharge_signee = 0 ")->fetchColumn();
    $nb_badges_attribues = $pdo->query("SELECT COUNT(*) FROM badges WHERE statut = 'Attribué'")->fetchColumn();
    $nb_badges_disponibles = $pdo->query("SELECT COUNT(*) FROM badges WHERE statut = 'Disponible'")->fetchColumn();
    $nb_badges_perdus = $pdo->query("SELECT COUNT(*) FROM badges WHERE statut = 'Perdu'")->fetchColumn();
    $nb_references = $pdo->query("SELECT COUNT(*) FROM references_cles")->fetchColumn();
    $derniers_mouvement = $pdo->query("
    SELECT 
    h.statut_evenement,
    h.date_remise,
    h.date_restitution,
    t.numero_trousseau,
    t.id_trousseau,
    p.nom,
    p.prenom
    FROM historique_trousseaux h
    JOIN trousseaux t ON h.id_trousseau = t.id_trousseau
    JOIN personnes p ON h.id_personne = p.id_personne
    ORDER BY COALESCE(h.date_restitution, h.date_remise) DESC
    LIMIT 5
    ")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "<p>Erreur SQL : " . $e->getMessage() . "</p>";
}
?>


<!--  index.php - Tableau de bord -->

<h1>Tableau de bord</h1>

<!-- Trousseaux -->
<div class="card">
    <h2>Trousseaux</h2>
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-number"><?= $nb_trousseaux_attribues ?? 0 ?></div>
            <div class="stat-label">Attribués</div>
        </div>
        <div class="stat-card">
            <div class="stat-number"><?= $nb_trousseaux_disponibles ?? 0 ?></div>
            <div class="stat-label">Disponibles</div>
        </div>
        <div class="stat-card <?= ($nb_trousseaux_perdus > 0) ? 'alert' : '' ?>">
            <div class="stat-number"><?= $nb_trousseaux_perdus ?? 0 ?></div>
            <div class="stat-label">Perdus</div>
        </div>
        <div class="stat-card <?= ($nb_decharges_manquantes > 0) ? 'alert' : '' ?>">
            <div class="stat-number"><?= $nb_decharges_manquantes ?? 0 ?></div>
            <div class="stat-label">Décharges manquantes</div>
        </div>
    </div>
</div>

<!-- Badges -->
<div class="card">
    <h2>Badges</h2>
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-number"><?= $nb_badges_attribues ?? 0 ?></div>
            <div class="stat-label">Attribués</div>
        </div>
        <div class="stat-card">
            <div class="stat-number"><?= $nb_badges_disponibles ?? 0 ?></div>
            <div class="stat-label">Disponibles</div>
        </div>
        <div class="stat-card <?= ($nb_badges_perdus > 0) ? 'alert' : '' ?>">
            <div class="stat-number"><?= $nb_badges_perdus ?? 0 ?></div>
            <div class="stat-label">Perdus</div>
        </div>
    </div>
</div>

<!-- Divers -->
<div class="card">
    <h2>Divers</h2>
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-number"><?= $nb_personnes ?? 0 ?></div>
            <div class="stat-label">Personnes enregistrées</div>
        </div>
        <div class="stat-card">
            <div class="stat-number"><?= $nb_references ?? 0 ?></div>
            <div class="stat-label">Références de clés</div>
        </div>
    </div>
</div>

<!-- Derniers Mouvements -->
<div class="card">
    <h2>Derniers mouvements</h2>
    <?php if (empty($derniers_mouvement)) : ?>
        <p>Aucun mouvement enregistré.</p>
    <?php else : ?>
        <table>
            <thead>
                <tr>
                    <th>Événement</th>
                    <th>Trousseau</th>
                    <th>Personne</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($derniers_mouvement as $m) : ?>
                    <tr>
                        <td><?= htmlspecialchars($m['statut_evenement']) ?></td>
                        <td>
                            <a href = "fiche_trousseau.php?id=<?= (int)$m['id_trousseau'] ?>">
                                <?= htmlspecialchars($m['numero_trousseau']) ?>
                            </a>
                        </td>
                        <td><?= htmlspecialchars($m['prenom'] . ' ' . $m['nom']) ?></td>
                        <td>
                            <?php formaterDate($m['date_restitution'] ?? $m['date_remise'])?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>


<?php include 'includes/footer.php'; ?>