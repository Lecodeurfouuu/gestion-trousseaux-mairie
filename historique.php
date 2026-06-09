<?php
require_once 'config/database.php';
require_once 'includes/fonctions.php';

$page_title = "Historique";
include 'includes/header.php';

try {
    $requeteHistorique = $pdo->query("
        SELECT
            h.id_historique,
            h.date_remise,
            h.date_restitution,
            h.decharge_signee,
            h.statut_evenement,
            h.commentaire,

            t.numero_trousseau,

            p.nom,
            p.prenom,
            p.service,
            p.groupe_personnel

        FROM historique_trousseaux h

        JOIN trousseaux t
            ON h.id_trousseau = t.id_trousseau

        JOIN personnes p
            ON h.id_personne = p.id_personne

        ORDER BY h.date_remise DESC, h.id_historique DESC
    ");

    $historiques = $requeteHistorique->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Erreur SQL : " . $e->getMessage());
}
?>

<h1>Historique des mouvements</h1>

<div class="card">
    <h2>Remises et restitutions des trousseaux</h2>

    <table>
        <thead>
            <tr>
                <th>Trousseau</th>
                <th>Personne</th>
                <th>Service</th>
                <th>Groupe</th>
                <th>Date remise</th>
                <th>Date restitution</th>
                <th>Décharge</th>
                <th>Statut</th>
                <th>Commentaire</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($historiques)) : ?>
                <tr>
                    <td colspan="10">Aucun mouvement enregistré.</td>
                </tr>
            <?php else : ?>
                <?php foreach ($historiques as $historique) : ?>
                    <tr>

                        <td>
                            <strong><?= htmlspecialchars($historique['numero_trousseau']) ?></strong>
                        </td>

                        <td>
                            <?= htmlspecialchars($historique['prenom'] . ' ' . $historique['nom']) ?>
                        </td>

                        <td>
                            <?= htmlspecialchars($historique['service'] ?? '-') ?>
                        </td>

                        <td>
                            <?= htmlspecialchars($historique['groupe_personnel'] ?? '-') ?>
                        </td>

                        <td>
                            <?=formaterDate($historique['date_remise']) ?>
                        </td>

                        <td>
                            <?= formaterDate($historique['date_restitution']) ?>
                        </td>

                        <td>
                            <?=afficherDecharge($historique['decharge_signee']) ?>
                        </td>

                        <td>
                            <?= htmlspecialchars($historique['statut_evenement'] ?? '-') ?>
                        </td>

                         <td>
                            <?= htmlspecialchars($historique['commentaire'] ?? '') ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>
