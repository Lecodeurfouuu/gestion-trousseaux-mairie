<?php
require_once 'config/database.php';
require_once 'includes/fonctions.php';

$page_title = "Recherche";
include 'includes/header.php';

$type_recherche    = $_GET['type_recherche'] ?? '';
$terme             = trim($_GET['terme'] ?? '');
$inclure_historique = isset($_GET['inclure_historique']) && $_GET['inclure_historique'] === '1';

$resultats_personnes = [];
$resultats_elements  = [];
$message = '';

if ($terme !== '' && $type_recherche !== '') {
    try {
        if ($type_recherche === 'personne') {
            $requeteRecherchePersonne = $pdo->prepare("
                SELECT
                    p.nom,
                    p.prenom,
                    p.service,
                    p.groupe_personnel,
                    p.telephone,
                    p.mail,
                    t.numero_trousseau,
                    t.statut AS statut_trousseau,
                    h.date_remise,
                    h.decharge_signee,
                    t.id_trousseau
                FROM personnes p
                LEFT JOIN historique_trousseaux h
                    ON p.id_personne = h.id_personne
                    AND h.date_restitution IS NULL
                LEFT JOIN trousseaux t
                    ON h.id_trousseau = t.id_trousseau
                WHERE p.nom LIKE :terme
                    OR p.prenom LIKE :terme
                    OR CONCAT(p.prenom, ' ', p.nom) LIKE :terme
                    OR CONCAT(p.nom, ' ', p.prenom) LIKE :terme
                ORDER BY p.nom, p.prenom, t.numero_trousseau
            ");
            $requeteRecherchePersonne->execute([':terme' => '%' . $terme . '%']);
            $resultats_personnes = $requeteRecherchePersonne->fetchAll(PDO::FETCH_ASSOC);

        } elseif ($type_recherche === 'element') {

            // Si historique inclus : on cherche dans tous les éléments (retirés/perdus aussi)
            // et on joint TOUS les anciens détenteurs via historique_trousseaux
            if ($inclure_historique) {
                $requeteRechercheElement = $pdo->prepare("
                    SELECT
                        te.type_element,
                        rc.reference_cle,
                        b.identifiant_interne AS badge,
                        b.identifiant_officiel,
                        t.id_trousseau,
                        t.numero_trousseau,
                        t.statut AS statut_trousseau,
                        p.nom,
                        p.prenom,
                        p.service,
                        h.date_remise,
                        h.date_restitution,
                        te.date_ajout,
                        te.date_retrait,
                        bat.nom_batiment,
                        ea.porte_commentaire,
                        te.statut AS statut_element
                    FROM trousseau_elements te
                    LEFT JOIN references_cles rc
                        ON te.id_reference_cle = rc.id_reference_cle
                    LEFT JOIN badges b
                        ON te.id_badge = b.id_badge
                    JOIN trousseaux t
                        ON te.id_trousseau = t.id_trousseau
                    LEFT JOIN historique_trousseaux h
                        ON t.id_trousseau = h.id_trousseau
                    LEFT JOIN personnes p
                        ON h.id_personne = p.id_personne
                    LEFT JOIN element_acces ea ON (
                        (te.type_element = 'cle'   AND ea.id_reference_cle = te.id_reference_cle)
                        OR
                        (te.type_element = 'badge' AND ea.id_badge = te.id_badge)
                    )
                    LEFT JOIN batiments bat
                        ON ea.id_batiment = bat.id_batiment
                    WHERE (rc.reference_cle LIKE :terme
                        OR b.identifiant_interne LIKE :terme
                        OR b.identifiant_officiel LIKE :terme)
                    ORDER BY t.numero_trousseau, h.date_remise DESC
                ");
            } else {
                $requeteRechercheElement = $pdo->prepare("
                    SELECT
                        te.type_element,
                        rc.reference_cle,
                        b.identifiant_interne AS badge,
                        b.identifiant_officiel,
                        t.id_trousseau,
                        t.numero_trousseau,
                        t.statut AS statut_trousseau,
                        p.nom,
                        p.prenom,
                        p.service,
                        h.date_remise,
                        NULL AS date_restitution,
                        te.date_ajout,
                        te.date_retrait,
                        bat.nom_batiment,
                        ea.porte_commentaire,
                        te.statut AS statut_element
                    FROM trousseau_elements te
                    LEFT JOIN references_cles rc
                        ON te.id_reference_cle = rc.id_reference_cle
                    LEFT JOIN badges b
                        ON te.id_badge = b.id_badge
                    JOIN trousseaux t
                        ON te.id_trousseau = t.id_trousseau
                    LEFT JOIN historique_trousseaux h
                        ON t.id_trousseau = h.id_trousseau
                        AND h.date_restitution IS NULL
                    LEFT JOIN personnes p
                        ON h.id_personne = p.id_personne
                    LEFT JOIN element_acces ea ON (
                        (te.type_element = 'cle'   AND ea.id_reference_cle = te.id_reference_cle)
                        OR
                        (te.type_element = 'badge' AND ea.id_badge = te.id_badge)
                    )
                    LEFT JOIN batiments bat
                        ON ea.id_batiment = bat.id_batiment
                    WHERE (rc.reference_cle LIKE :terme
                        OR b.identifiant_interne LIKE :terme
                        OR b.identifiant_officiel LIKE :terme)
                        AND te.statut = 'Présent'
                        AND te.date_retrait IS NULL
                    ORDER BY t.numero_trousseau, te.type_element
                ");
            }

            $requeteRechercheElement->execute([':terme' => '%' . $terme . '%']);
            $resultats_elements = $requeteRechercheElement->fetchAll(PDO::FETCH_ASSOC);

        } else {
            $message = "Type de recherche invalide.";
        }
    } catch (PDOException $e) {
        error_log("Erreur SQL recherche : " . $e->getMessage());
        $message = "Une erreur est survenue lors de la recherche. Veuillez réessayer.";
    }
}
?>

<!-- Formulaire de recherche -->
<h1>Recherche</h1>
<div class="card">
    <h2>Effectuer une recherche</h2>
    <form method="GET" action="recherche.php">
        <label for="type_recherche">Type de recherche</label>
        <select id="type_recherche" name="type_recherche" required>
            <option value="">-- Choisir --</option>
            <option value="personne" <?= $type_recherche === 'personne' ? 'selected' : '' ?>>Personne</option>
            <option value="element"  <?= $type_recherche === 'element'  ? 'selected' : '' ?>>Clé ou badge</option>
        </select>
        <label for="terme">Recherche</label>
        <input
            id="terme"
            type="text"
            name="terme"
            placeholder="Ex : Dupont, REF-45, BLEU-001"
            value="<?= htmlspecialchars($terme) ?>"
            required
        >
        <label style="font-weight:normal; display:flex; align-items:center; gap:8px; margin-bottom:12px;">
            <input type="checkbox"
                   name="inclure_historique"
                   value="1"
                   style="width:auto; margin:0;"
                   <?= $inclure_historique ? 'checked' : '' ?>>
            Inclure l'historique (anciens détenteurs, éléments retirés/perdus)
        </label>
        <button type="submit">Rechercher</button>
    </form>
</div>

<?php afficherMessage($message); ?>


<!-- Affichage des résultats de la recherche de personnes -->

<?php if ($type_recherche === 'personne' && $terme !== '') : ?>
    <div class="card">
        <h2>Résultats de la recherche de personnes</h2>

        <?php if (empty($resultats_personnes)) : ?>
            <p>Aucun résultat trouvé pour "<?= htmlspecialchars($terme) ?>".</p>
        <?php else : ?>
            <table>
                <thead>
                    <tr>
                        <th>Personne</th>
                        <th>Service</th>
                        <th>Groupe</th>
                        <th>Téléphone</th>
                        <th>Email</th>
                        <th>Trousseau attribué</th>
                        <th>Statut du trousseau</th>
                        <th>Date de remise</th>
                        <th>Décharge signée</th>
                        <th>Détail</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($resultats_personnes as $personne) : ?>
                        <tr>
                            <td><?= htmlspecialchars($personne['prenom'] . ' ' . $personne['nom']) ?></td>
                            <td><?= htmlspecialchars($personne['service'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($personne['groupe_personnel'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($personne['telephone'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($personne['mail'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($personne['numero_trousseau'] ?? 'Aucun') ?></td>
                            <td><?= htmlspecialchars($personne['statut_trousseau'] ?? '-') ?></td>

                            <td><?= formaterDate($personne['date_remise'] ?? null) ?></td>

                            <td><?= afficherDecharge($personne['decharge_signee']) ?></td>

                            <td>
                                <?php if (!empty($personne['id_trousseau'])) : ?>
                                    <a href="fiche_trousseau.php?id=<?= (int)$personne['id_trousseau'] ?>">
                                        Voir la fiche
                                    </a>
                                <?php else : ?>
                                    -
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>

    </div>
<?php endif; ?>


<!-- Affichage des résultats de la recherche clés/badges -->

<?php if ($type_recherche === 'element' && $terme !== '') : ?>
    <div class="card">
        <h2>Résultats de la recherche de clés/badges</h2>

        <?php if (empty($resultats_elements)) : ?>
            <p>Aucun résultat trouvé pour "<?= htmlspecialchars($terme) ?>".</p>
        <?php else : ?>
            <?php if ($inclure_historique) : ?>
                <p><small>Résultats incluant l'historique complet — actifs et anciens détenteurs.</small></p>
            <?php endif; ?>
            <table>
                <thead>
                    <tr>
                        <th>Type</th>
                        <th>Référence</th>
                        <th>Trousseau</th>
                        <th>Détenteur</th>
                        <th>Service</th>
                        <th>Date remise</th>
                        <?php if ($inclure_historique) : ?>
                            <th>Date restitution</th>
                        <?php endif; ?>
                        <th>Bâtiment</th>
                        <th>Porte</th>
                        <th>Statut élément</th>
                        <th>Détail</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($resultats_elements as $element) : ?>
                        <tr>
                            <td><?= htmlspecialchars($element['type_element']) ?></td>
                            <td>
                                <?php if ($element['type_element'] === 'cle') : ?>
                                    <?= htmlspecialchars($element['reference_cle'] ?? '-') ?>
                                <?php else : ?>
                                    <?= htmlspecialchars($element['badge'] ?? '-') ?>
                                    <?php if (!empty($element['identifiant_officiel'])) : ?>
                                        <br><small>Officiel : <?= htmlspecialchars($element['identifiant_officiel']) ?></small>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars($element['numero_trousseau'] ?? '-') ?></td>
                            <td>
                                <?php if (!empty($element['nom'])) : ?>
                                    <?= htmlspecialchars($element['prenom'] . ' ' . $element['nom']) ?>
                                <?php else : ?>
                                    Aucun détenteur
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars($element['service'] ?? '-') ?></td>
                            <td><?= formaterDate($element['date_remise'] ?? null) ?></td>
                            <?php if ($inclure_historique) : ?>
                                <td><?= formaterDate($element['date_restitution'] ?? null) ?></td>
                            <?php endif; ?>
                            <td><?= htmlspecialchars($element['nom_batiment'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($element['porte_commentaire'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($element['statut_element'] ?? '-') ?></td>
                            <td>
                                <?php if (!empty($element['id_trousseau'])) : ?>
                                    <a href="fiche_trousseau.php?id=<?= (int)$element['id_trousseau'] ?>">
                                        Voir la fiche
                                    </a>
                                <?php else : ?>
                                    -
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>

    </div>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>