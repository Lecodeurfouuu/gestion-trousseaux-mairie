<?php
require_once 'config/database.php';
require_once 'includes/fonctions.php';

$page_title = "Recherche";
include 'includes/header.php';

$terme              = trim($_GET['terme'] ?? '');
$id_batiment_search = (int)($_GET['id_batiment'] ?? 0);
$id_porte_search    = (int)($_GET['id_porte'] ?? 0);
$inclure_historique = isset($_GET['inclure_historique']) && $_GET['inclure_historique'] === '1';

$resultats_personnes  = [];
$resultats_elements   = [];
$resultats_batiment   = [];
$message = '';

// Récupération des bâtiments pour le select
try {
    $batiments = $pdo->query("SELECT id_batiment, nom_batiment FROM batiments ORDER BY nom_batiment")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $batiments = [];
}

// Récupération des portes du bâtiment sélectionné
$portes_du_batiment = [];
if ($id_batiment_search > 0) {
    try {
        $stmtPortes = $pdo->prepare("
            SELECT id_porte, nom_porte FROM portes
            WHERE id_batiment = :id_batiment
            ORDER BY nom_porte
        ");
        $stmtPortes->execute([':id_batiment' => $id_batiment_search]);
        $portes_du_batiment = $stmtPortes->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $portes_du_batiment = [];
    }
}

// Recherche bâtiment
if ($id_batiment_search > 0) {
    try {
        $requeteRechercheBatiment = $pdo->prepare("
            SELECT
                p.nom,
                p.prenom,
                p.service,
                t.numero_trousseau,
                t.id_trousseau,
                te.type_element,
                rc.reference_cle,
                b.identifiant_interne AS badge,
                GROUP_CONCAT(po.nom_porte ORDER BY po.nom_porte SEPARATOR ', ') AS portes_acces,
                te.commentaire_horaires
            FROM trousseau_elements te
            LEFT JOIN references_cles rc ON te.id_reference_cle = rc.id_reference_cle
            LEFT JOIN badges b ON te.id_badge = b.id_badge
            JOIN trousseaux t ON te.id_trousseau = t.id_trousseau
            JOIN historique_trousseaux h
                ON t.id_trousseau = h.id_trousseau
                AND h.date_restitution IS NULL
            JOIN personnes p ON h.id_personne = p.id_personne
            JOIN element_acces ea ON (
                (te.type_element = 'cle'   AND ea.id_reference_cle = te.id_reference_cle)
                OR
                (te.type_element = 'badge' AND ea.id_badge = te.id_badge)
            )
            LEFT JOIN portes po ON ea.id_porte = po.id_porte
            WHERE ea.id_batiment = :id_batiment
            AND te.statut = 'Présent'
            AND te.date_retrait IS NULL
            " . ($id_porte_search > 0 ? 'AND ea.id_porte = :id_porte' : '') . "
            GROUP BY te.id_trousseau_element, p.nom, p.prenom, p.service,
                     t.numero_trousseau, t.id_trousseau, te.type_element,
                     rc.reference_cle, b.identifiant_interne, te.commentaire_horaires
            ORDER BY p.nom, p.prenom, te.type_element
        ");
        $params = [':id_batiment' => $id_batiment_search];
        if ($id_porte_search > 0) $params[':id_porte'] = $id_porte_search;
        $requeteRechercheBatiment->execute($params);
        $resultats_batiment = $requeteRechercheBatiment->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Erreur SQL recherche bâtiment : " . $e->getMessage());
        $message = "Une erreur est survenue lors de la recherche. Veuillez réssayer.";
    }
}

// Recherche globale (personnes + clés/badges simultanément)
if ($terme !== '') {
    try {
        // Personnes
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
            ORDER BY p.nom, p.prenom
        ");
        $requeteRecherchePersonne->execute([':terme' => '%' . $terme . '%']);
        $resultats_personnes = $requeteRecherchePersonne->fetchAll(PDO::FETCH_ASSOC);

        // Clés et badges
        if ($inclure_historique) {
            $requeteRechercheElement = $pdo->prepare("
                SELECT
                        te.type_element,
                        rc.reference_cle,
                        b.identifiant_interne AS badge,
                        t.id_trousseau,
                        t.numero_trousseau,
                        t.statut AS statut_trousseau,
                        p.nom,
                        p.prenom,
                        p.service,
                        h.date_remise,
                        h.date_restitution,
                    te.statut AS statut_element,
                    GROUP_CONCAT(
                        bat.nom_batiment,
                        CASE WHEN po.nom_porte IS NOT NULL THEN CONCAT(' — ', po.nom_porte) ELSE '' END
                            ORDER BY bat.nom_batiment
                            SEPARATOR ' | '
                    ) AS acces_batiments
                FROM trousseau_elements te
                LEFT JOIN references_cles rc ON te.id_reference_cle = rc.id_reference_cle
                LEFT JOIN badges b ON te.id_badge = b.id_badge
                JOIN trousseaux t ON te.id_trousseau = t.id_trousseau
                LEFT JOIN historique_trousseaux h ON t.id_trousseau = h.id_trousseau
                LEFT JOIN personnes p ON h.id_personne = p.id_personne
                LEFT JOIN element_acces ea ON (
                    (te.type_element = 'cle'   AND ea.id_reference_cle = te.id_reference_cle)
                        OR
                        (te.type_element = 'badge' AND ea.id_badge = te.id_badge)
                )
                LEFT JOIN batiments bat ON ea.id_batiment = bat.id_batiment
                LEFT JOIN portes po ON ea.id_porte = po.id_porte
                WHERE (rc.reference_cle LIKE :terme OR b.identifiant_interne LIKE :terme)
                GROUP BY te.id_trousseau_element, te.type_element, rc.reference_cle,
                         b.identifiant_interne, t.id_trousseau, t.numero_trousseau,
                         t.statut, p.nom, p.prenom, p.service, h.date_remise, h.date_restitution, te.statut
                ORDER BY t.numero_trousseau, h.date_remise DESC
            ");
        } else {
            $requeteRechercheElement = $pdo->prepare("
                SELECT
                        te.type_element,
                        rc.reference_cle,
                        b.identifiant_interne AS badge,
                        t.id_trousseau,
                        t.numero_trousseau,
                        t.statut AS statut_trousseau,
                        p.nom,
                        p.prenom,
                        p.service,
                        h.date_remise,
                        NULL AS date_restitution,
                        te.statut AS statut_element,
                    GROUP_CONCAT(
                        bat.nom_batiment,
                        CASE WHEN po.nom_porte IS NOT NULL THEN CONCAT(' — ', po.nom_porte) ELSE '' END
                            ORDER BY bat.nom_batiment
                            SEPARATOR ' | '
                    ) AS acces_batiments
                FROM trousseau_elements te
                LEFT JOIN references_cles rc ON te.id_reference_cle = rc.id_reference_cle
                LEFT JOIN badges b ON te.id_badge = b.id_badge
                JOIN trousseaux t ON te.id_trousseau = t.id_trousseau
                LEFT JOIN historique_trousseaux h
                        ON t.id_trousseau = h.id_trousseau
                        AND h.date_restitution IS NULL
                LEFT JOIN personnes p ON h.id_personne = p.id_personne
                LEFT JOIN element_acces ea ON (
                    (te.type_element = 'cle'   AND ea.id_reference_cle = te.id_reference_cle)
                        OR
                        (te.type_element = 'badge' AND ea.id_badge = te.id_badge)
                )
                LEFT JOIN batiments bat ON ea.id_batiment = bat.id_batiment
                LEFT JOIN portes po ON ea.id_porte = po.id_porte
                WHERE (rc.reference_cle LIKE :terme OR b.identifiant_interne LIKE :terme)
                    AND te.statut = 'Présent'
                    AND te.date_retrait IS NULL
                GROUP BY te.id_trousseau_element, te.type_element, rc.reference_cle,
                         b.identifiant_interne, t.id_trousseau, t.numero_trousseau,
                         t.statut, p.nom, p.prenom, p.service, h.date_remise, te.statut
                ORDER BY t.numero_trousseau, te.type_element
            ");
        }

        $requeteRechercheElement->execute([':terme' => '%' . $terme . '%']);
        $resultats_elements = $requeteRechercheElement->fetchAll(PDO::FETCH_ASSOC);

    } catch (PDOException $e) {
        error_log("Erreur SQL recherche : " . $e->getMessage());
        $message = "Une erreur est survenue lors de la recherche. Veuillez réessayer.";
    }
}
?>

<h1>Recherche</h1>

<!-- Recherche globale -->
<div class="card">
    <h2>Recherche globale</h2>
    <form method="GET" action="recherche.php">
        <label for="terme">Rechercher une personne, une clé ou un badge</label>
        <input
            id="terme"
            type="text"
            name="terme"
            placeholder="Ex : Dupont, REF-45, BLEU-001"
            value="<?= htmlspecialchars($terme) ?>"
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

<!-- Recherche bâtiment -->
<div class="card">
    <h2>Recherche par bâtiment</h2>
    <form method="GET" action="recherche.php">
        <label for="id_batiment">Bâtiment</label>
        <select id="id_batiment" name="id_batiment" onchange="this.form.submit()">
            <option value="">-- Choisir un bâtiment --</option>
            <?php foreach ($batiments as $bat) : ?>
                <option value="<?= $bat['id_batiment'] ?>" <?= $id_batiment_search === (int)$bat['id_batiment'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($bat['nom_batiment']) ?>
                </option>
            <?php endforeach; ?>
        </select>
        <?php if (!empty($portes_du_batiment)) : ?>
            <label for="id_porte">Porte (optionnel)</label>
            <select id="id_porte" name="id_porte">
                <option value="">-- Toutes les portes --</option>
                <?php foreach ($portes_du_batiment as $porte) : ?>
                    <option value="<?= $porte['id_porte'] ?>" <?= $id_porte_search === (int)$porte['id_porte'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($porte['nom_porte']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        <?php endif; ?>
        <?php if ($id_batiment_search > 0) : ?>
            <button type="submit" style="margin-top:8px;">Rechercher</button>
        <?php endif; ?>
    </form>
</div>

<?php afficherMessage($message); ?>

<?php if ($terme !== '' && empty($resultats_personnes) && empty($resultats_elements)) : ?>
    <div class="card">
        <p>Aucun résultat trouvé pour "<?= htmlspecialchars($terme) ?>".</p>
    </div>
<?php endif; ?>

<!-- Résultats personnes -->
<?php if (!empty($resultats_personnes)) : ?>
    <div class="card">
        <h2>Personnes</h2>
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
                                <a href="fiche_trousseau.php?id=<?= (int)$personne['id_trousseau'] ?>">Voir la fiche</a>
                            <?php else : ?>
                                -
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<!-- Résultats clés/badges -->
<?php if (!empty($resultats_elements)) : ?>
    <div class="card">
        <h2>Clés et badges</h2>
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
                    <th>Bâtiments / Portes</th>
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
                        <td><?= htmlspecialchars($element['acces_batiments'] ?? '—') ?></td>
                        <td><?= htmlspecialchars($element['statut_element'] ?? '-') ?></td>
                        <td>
                            <?php if (!empty($element['id_trousseau'])) : ?>
                                <a href="fiche_trousseau.php?id=<?= (int)$element['id_trousseau'] ?>">Voir la fiche</a>
                            <?php else : ?>
                                -
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<!-- Résultats recherche bâtiment -->
<?php if ($id_batiment_search > 0) :
    $nomBatimentChoisi = '';
    foreach ($batiments as $bat) {
        if ((int)$bat['id_batiment'] === $id_batiment_search) {
            $nomBatimentChoisi = $bat['nom_batiment'];
            break;
        }
    }

    // Regrouper les résultats par personne
    $par_personne = [];
    foreach ($resultats_batiment as $r) {
        $cle_personne = $r['nom'] . '|' . $r['prenom'];
        $par_personne[$cle_personne][] = $r;
    }
    // Nom de la porte sélectionnée si filtrée
    $nomPorteChoisie = '';
    if ($id_porte_search > 0) {
        foreach ($portes_du_batiment as $po) {
            if ((int)$po['id_porte'] === $id_porte_search) {
                $nomPorteChoisie = $po['nom_porte'];
                break;
            }
        }
    }
?>

    <h2>Personnes ayant accès à : <?= htmlspecialchars($nomBatimentChoisi) ?>
        <?php if ($nomPorteChoisie !== '') : ?>
            — <?= htmlspecialchars($nomPorteChoisie) ?>
        <?php endif; ?>
        <small>(<?= count($par_personne) ?> personne<?= count($par_personne) > 1 ? 's' : '' ?>)</small>
    </h2>

    <?php if (empty($par_personne)) : ?>
        <div class="card">
            <p>Aucune personne n'a accès à ce bâtiment actuellement.</p>
        </div>
    <?php else : ?>
        <?php foreach ($par_personne as $lignes) : ?>
            <?php $premiere = $lignes[0]; ?>
            <div class="card">
                <h3 style="margin-bottom:4px;">
                    <?= htmlspecialchars($premiere['prenom'] . ' ' . $premiere['nom']) ?>
                </h3>
                <p style="color:#64748b; margin-bottom:12px;">
                    <?= htmlspecialchars($premiere['service'] ?? '') ?>
                    — Trousseau :
                    <a href="fiche_trousseau.php?id=<?= (int)$premiere['id_trousseau'] ?>">
                        <?= htmlspecialchars($premiere['numero_trousseau']) ?>
                    </a>
                </p>
                <table>
                    <thead>
                        <tr>
                            <th>Type</th>
                            <th>Référence / Badge</th>
                            <th>Porte(s)</th>
                            <th>Horaires badge</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($lignes as $r) : ?>
                            <tr>
                                <td><?= htmlspecialchars($r['type_element']) ?></td>
                                <td>
                                    <?php if ($r['type_element'] === 'cle') : ?>
                                        <?= htmlspecialchars($r['reference_cle'] ?? '-') ?>
                                    <?php else : ?>
                                        <?= htmlspecialchars($r['badge'] ?? '-') ?>
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($r['portes_acces'] ?? '—') ?></td>
                                <td>
                                    <?php if ($r['type_element'] === 'badge' && !empty($r['commentaire_horaires'])) : ?>
                                        <?= htmlspecialchars($r['commentaire_horaires']) ?>
                                    <?php else : ?>
                                        -
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>
