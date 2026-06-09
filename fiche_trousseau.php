<?php
require_once 'config/database.php';
require_once 'includes/fonctions.php';

$page_title = "Fiche trousseau";
include 'includes/header.php';


$id_trousseau = $_GET['id'] ?? null;
$message = '';
$onglet_actif = $_GET['onglet'] ?? 'informations';

// vérifier qu'un trousseau est bien sélectionné
if ($id_trousseau === null || !is_numeric($id_trousseau)) {
    echo "<h1>Fiche trousseau</h1>";
    echo "<div class='card'>";
    echo "<p>Aucun trousseau sélectionné.</p>";
    echo "<a href='trousseaux.php' class='btn'>Retour aux trousseaux</a>";
    echo "</div>";
    include 'includes/footer.php';
    exit;
}

// Message après redirection
if (isset($_GET['success']) && $_GET['success'] === 'element_ajoute') {
    $message = "Élément ajouté au trousseau.";
}

// Ajout d'un élément dans le trousseau
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajouter_element'])) {
    $type_element = $_POST['type_element'] ?? '';
    $id_reference_cle = $_POST['id_reference_cle'] ?? null;
    $id_badge = $_POST['id_badge'] ?? null;
    $date_ajout = $_POST['date_ajout'] ?? date('Y-m-d');
    $statut = 'Présent';
    $commentaire = trim($_POST['commentaire'] ?? '');
    $commentaire_horaires = trim($_POST['commentaire_horaires'] ?? '');

    if ($type_element === 'cle') {
        $id_badge = null;

        if (empty($id_reference_cle)) {
            $message = 'Veuillez choisir une référence de clé.';
        }
    } elseif ($type_element === 'badge') {
        $id_reference_cle = null;

        if (empty($id_badge)) {
            $message = 'Veuillez choisir un badge.';
        }
    } else {
        $message = "Type d'élément invalide.";
    }

    if ($message === '') {
        try {
            // Vérification doublon : éviter d'ajouter deux fois le même élément actif
            $requeteDoublonElement = $pdo->prepare("
                SELECT COUNT(*)
                FROM trousseau_elements
                WHERE id_trousseau = :id_trousseau
                AND type_element = :type_element
                AND (
                    (type_element = 'cle' AND id_reference_cle = :id_reference_cle)
                    OR
                    (type_element = 'badge' AND id_badge = :id_badge)
                )
                AND statut = 'Présent'
            ");

            $requeteDoublonElement->execute([
                ':id_trousseau' => $id_trousseau,
                ':type_element' => $type_element,
                ':id_reference_cle' => $id_reference_cle ?: null,
                ':id_badge' => $id_badge ?: null
            ]);

            $elementExisteDeja = $requeteDoublonElement->fetchColumn();

            if ($elementExisteDeja > 0) {
                $message = "Cet élément est déjà présent dans ce trousseau.";
            } else {
                $requeteAjoutElement = $pdo->prepare("
                    INSERT INTO trousseau_elements
                    (
                        id_trousseau,
                        type_element,
                        id_reference_cle,
                        id_badge,
                        date_ajout,
                        statut,
                        commentaire,
                        commentaire_horaires
                    )
                    VALUES
                    (
                        :id_trousseau,
                        :type_element,
                        :id_reference_cle,
                        :id_badge,
                        :date_ajout,
                        :statut,
                        :commentaire,
                        :commentaire_horaires
                    )
                ");

                $requeteAjoutElement->execute([
                    ':id_trousseau' => $id_trousseau,
                    ':type_element' => $type_element,
                    ':id_reference_cle' => $id_reference_cle ?: null,
                    ':id_badge' => $id_badge ?: null,
                    ':date_ajout' => $date_ajout,
                    ':statut' => $statut,
                    ':commentaire' => $commentaire !== '' ? $commentaire : null,
                    ':commentaire_horaires' => $commentaire_horaires !== '' ? $commentaire_horaires : null
                ]);

                // Si c'est un badge, passer son statut à Attribué
                if ($type_element === 'badge' && !empty($id_badge)) {
                    $requeteBadgeAttribue = $pdo->prepare("
                        UPDATE badges SET statut = 'Attribué' WHERE id_badge = :id_badge
                    ");
                    $requeteBadgeAttribue->execute([':id_badge' => $id_badge]);
                }

                // Redirection pour éviter le doublon lors d'une actualisation
                header("Location: fiche_trousseau.php?id=" . urlencode($id_trousseau) . "&success=element_ajoute");
                exit;
            }
        } catch (PDOException $e) {
            $message = "Erreur lors de l'ajout de l'élément : " . $e->getMessage();
        }
    }
}

//Retirer un élément du trousseau
if ($_SERVER['REQUEST_METHOD'] === "POST" && isset($_POST['retirer_element'])) {
    $id_trousseau_element = $_POST['id_trousseau_element'] ?? null;
    if (empty($id_trousseau_element)) {
        $message = 'Elément introuvable.';
    } else {
        try {
            // Récupérer le type et l'id_badge avant le retrait
            $requeteInfoRetrait = $pdo->prepare("
                SELECT type_element, id_badge
                FROM trousseau_elements
                WHERE id_trousseau_element = :id_trousseau_element
            ");
            $requeteInfoRetrait->execute([':id_trousseau_element' => $id_trousseau_element]);
            $elementRetrait = $requeteInfoRetrait->fetch(PDO::FETCH_ASSOC);

            $requeteRetraitElement = $pdo->prepare("
                UPDATE trousseau_elements
                SET statut ='Retiré',
                date_retrait = CURDATE()
                WHERE id_trousseau_element = :id_trousseau_element AND id_trousseau = :id_trousseau");
            $requeteRetraitElement->execute([
                ':id_trousseau_element' => $id_trousseau_element,
                ':id_trousseau' => $id_trousseau
            ]);

            // Si c'est un badge, repasser son statut à Disponible
            if ($elementRetrait && $elementRetrait['type_element'] === 'badge' && !empty($elementRetrait['id_badge'])) {
                $requeteBadgeDisponible = $pdo->prepare("
                    UPDATE badges SET statut = 'Disponible' WHERE id_badge = :id_badge
                ");
                $requeteBadgeDisponible->execute([':id_badge' => $elementRetrait['id_badge']]);
            }

            $message = 'Elément retiré du trousseau.';
        } catch (PDOException $e) {
            $message = 'Erreur lors du retrait : ' . $e->getMessage();
        }
    }
}

// Déclarer un élément perdu
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['declarer_element_perdu'])) {
    $id_trousseau_element = $_POST['id_trousseau_element'] ?? null;

    if (empty($id_trousseau_element)) {
        $message = "Élément introuvable.";
    } else {
        try {
            // Récupérer le type et l'id_badge avant la mise à jour
            $requeteInfoElement = $pdo->prepare("
                SELECT type_element, id_badge
                FROM trousseau_elements
                WHERE id_trousseau_element = :id_trousseau_element
            ");
            $requeteInfoElement->execute([':id_trousseau_element' => $id_trousseau_element]);
            $elementInfo = $requeteInfoElement->fetch(PDO::FETCH_ASSOC);

            $requeteElementPerdu = $pdo->prepare("
                UPDATE trousseau_elements
                SET statut = 'Perdu',
                    date_retrait = CURDATE(),
                    commentaire = CONCAT(COALESCE(commentaire, ''), CASE WHEN commentaire IS NOT NULL AND commentaire <> '' THEN ' | ' ELSE '' END, 'Déclaré perdu le ', CURDATE())
                WHERE id_trousseau_element = :id_trousseau_element
                AND id_trousseau = :id_trousseau
            ");

            $requeteElementPerdu->execute([
                ':id_trousseau_element' => $id_trousseau_element,
                ':id_trousseau' => $id_trousseau
            ]);

            // Si c'est un badge, propager le statut Perdu dans la table badges
            if ($elementInfo && $elementInfo['type_element'] === 'badge' && !empty($elementInfo['id_badge'])) {
                $requeteBadgePerdu = $pdo->prepare("
                    UPDATE badges SET statut = 'Perdu' WHERE id_badge = :id_badge
                ");
                $requeteBadgePerdu->execute([':id_badge' => $elementInfo['id_badge']]);
            }

            $message = "Élément déclaré perdu.";

        } catch (PDOException $e) {
            $message = "Erreur lors de la déclaration de perte : " . $e->getMessage();
        }
    }
}

// Déclarer le trousseau perdu
// Marquer le trousseau comme retrouvé
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['trousseau_retrouve'])) {
    try {
        // Vérifier s'il y avait un détenteur actif avant la perte
        $requeteVerifDetenteur = $pdo->prepare("
            SELECT COUNT(*) FROM historique_trousseaux
            WHERE id_trousseau = :id_trousseau AND date_restitution IS NULL
        ");
        $requeteVerifDetenteur->execute([':id_trousseau' => $id_trousseau]);
        $aDetenteur = $requeteVerifDetenteur->fetchColumn() > 0;

        // Si détenteur actif -> Attribué, sinon -> Disponible
        $nouveauStatut = $aDetenteur ? 'Attribué' : 'Disponible';

        $requeteTrousseauRetrouve = $pdo->prepare("
            UPDATE trousseaux SET statut = :statut
            WHERE id_trousseau = :id_trousseau AND statut = 'Perdu'
        ");
        $requeteTrousseauRetrouve->execute([
            ':statut'       => $nouveauStatut,
            ':id_trousseau' => $id_trousseau
        ]);

        $message = $aDetenteur
            ? "Trousseau retrouvé — repassé en Attribué, la personne le récupère automatiquement."
            : "Trousseau retrouvé — repassé en Disponible.";

    } catch (PDOException $e) {
        $message = "Erreur : " . $e->getMessage();
    }
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['declarer_trousseau_perdu'])) {
    try {
        // Mettre le trousseau en statut Perdu
        $requeteTrousseauPerdu = $pdo->prepare("
            UPDATE trousseaux
            SET statut = 'Perdu'
            WHERE id_trousseau = :id_trousseau
        ");

        $requeteTrousseauPerdu->execute([
            ':id_trousseau' => $id_trousseau
        ]);

        // Ajouter une trace dans l'historique actif si le trousseau est attribué
        $requeteHistoriquePerte = $pdo->prepare("
            UPDATE historique_trousseaux
            SET statut_evenement = 'Perdu',
                commentaire = CONCAT(
                    COALESCE(commentaire, ''),
                    ' | Trousseau déclaré perdu le ',
                    CURDATE()
                )
            WHERE id_trousseau = :id_trousseau
            AND date_restitution IS NULL
        ");

        $requeteHistoriquePerte->execute([
            ':id_trousseau' => $id_trousseau
        ]);

        $message = "Trousseau déclaré perdu.";

    } catch (PDOException $e) {
        $message = "Erreur lors de la déclaration de perte du trousseau : " . $e->getMessage();
    }
}

//Attribuer le trousseau à une personne
if ($_SERVER['REQUEST_METHOD'] === "POST" && isset($_POST['attribuer_trousseau'])) {
    $id_personne = $_POST['id_personne'] ?? null;
    $date_remise = $_POST['date_remise'] ?? date('Y-m-d');
    $decharge_signee = $_POST['decharge_signee'] ?? '0';
    $commentaire_attribution = trim($_POST['commentaire_attribution'] ?? '');

    if (empty($id_personne)) {
        $message = "Veuillez choisir une personne pour l'attribution.";
    } else {
        try {
            // Vérifier qu'il y a au moins un élément dans le trousseau
            $requeteNbElements = $pdo->prepare("
                SELECT COUNT(*) FROM trousseau_elements
                WHERE id_trousseau = :id_trousseau AND statut = 'Présent' AND date_retrait IS NULL
            ");
            $requeteNbElements->execute([':id_trousseau' => $id_trousseau]);

            if ($requeteNbElements->fetchColumn() == 0) {
                $message = "Impossible d'attribuer ce trousseau : il ne contient aucun élément. Ajoutez au moins une clé ou un badge avant l'attribution.";
            } else {
                // Vérifier qu'il n'existe pas une attribution active
                $requeteDoublonAttribution = $pdo->prepare("
                    SELECT COUNT(*)
                    FROM historique_trousseaux
                    WHERE id_trousseau = :id_trousseau AND date_restitution IS NULL
                ");
                $requeteDoublonAttribution->execute([":id_trousseau" => $id_trousseau]);

                if ($requeteDoublonAttribution-> fetchColumn() > 0) {
                    $message = "Ce trousseau est déjà attribué. Il faut d'abord le restituer.";}
                else {
                        $requeteAjoutAttribution = $pdo->prepare("
                        INSERT INTO historique_trousseaux (id_trousseau, id_personne, date_remise, date_restitution, decharge_signee, statut_evenement, commentaire)
                        VALUES (:id_trousseau, :id_personne, :date_remise, NULL, :decharge_signee, 'Remis', :commentaire )
                        ");

                        $requeteAjoutAttribution->execute([
                            ':id_trousseau' => $id_trousseau,
                            ':id_personne' => $id_personne,
                            ':date_remise' =>$date_remise,
                            ':decharge_signee' => (int)$decharge_signee,
                            ':commentaire' => $commentaire_attribution !== '' ? $commentaire_attribution : null
                        ]);

                        $requeteStatutAttribue = $pdo->prepare("
                        UPDATE trousseaux
                        SET statut = 'Attribué'
                        WHERE id_trousseau = :id_trousseau
                        ");
                        $requeteStatutAttribue->execute([':id_trousseau' => $id_trousseau]);

                        $message = "Trousseau attribué avec succès.";
                }
            } // fin else (au moins un élément)
        } catch (PDOException $e) {
            $message = "Erreur lors de l'attribution : " . $e->getMessage();
        }
    }
}

// Signer la décharge
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['signer_decharge'])) {
    try {
        $requeteSignerDecharge = $pdo->prepare("
            UPDATE historique_trousseaux
            SET decharge_signee = 1
            WHERE id_trousseau = :id_trousseau
            AND date_restitution IS NULL
        ");
        $requeteSignerDecharge->execute([':id_trousseau' => $id_trousseau]);
        $message = "Décharge marquée comme signée.";
    } catch (PDOException $e) {
        $message = "Erreur : " . $e->getMessage();
    }
}

//Restituer le trousseau

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['restituer_trousseau'])) {
    $date_restitution = $_POST['date_restitution'] ?? date('Y-m-d');
    $commentaire_restitution = trim($_POST['commentaire_restitution'] ?? '');

    try {
        $requeteRestitution = $pdo->prepare('
        UPDATE historique_trousseaux
        SET date_restitution = :date_restitution, statut_evenement = "Restitué", commentaire = :commentaire
        WHERE id_trousseau = :id_trousseau AND date_restitution IS NULL
        ');

        $requeteRestitution->execute([
            ':date_restitution' => $date_restitution,
            ':commentaire' => $commentaire_restitution !== '' ? $commentaire_restitution : null,
            ':id_trousseau' => $id_trousseau ]);

        if ($requeteRestitution->rowCount() > 0) {
            $requeteStatutDisponible = $pdo->prepare('
            UPDATE trousseaux
            SET statut = "Disponible"
            WHERE id_trousseau = :id_trousseau
            ');
            $requeteStatutDisponible->execute([':id_trousseau' => $id_trousseau]);

            // Repasser tous les badges actifs du trousseau à Disponible
            $requeteBadgesDisponibles = $pdo->prepare("
                UPDATE badges b
                JOIN trousseau_elements te ON b.id_badge = te.id_badge
                SET b.statut = 'Disponible'
                WHERE te.id_trousseau = :id_trousseau
                AND te.statut = 'Présent'
                AND te.type_element = 'badge'
                AND b.statut = 'Attribué'
            ");
            $requeteBadgesDisponibles->execute([':id_trousseau' => $id_trousseau]);

            $message = "Trousseau restitué avec succès.";
        } else {
            $message = "Aucune attribution active trouvée pour ce trousseau.";
        }
    } catch (PDOException $e) {
        $message = "Erreur lors de la restitution : " . $e->getMessage();}
}

// Modifier les horaires d'un badge dans le trousseau
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['modifier_horaires'])) {
    $id_trousseau_element = $_POST['id_trousseau_element'] ?? null;
    $nouveaux_horaires    = trim($_POST['nouveaux_horaires'] ?? '');

    if (empty($id_trousseau_element)) {
        $message = 'Élément introuvable.';
    } else {
        try {
            $requeteModifierHoraires = $pdo->prepare("
                UPDATE trousseau_elements
                SET commentaire_horaires = :commentaire_horaires
                WHERE id_trousseau_element = :id_trousseau_element
                AND id_trousseau = :id_trousseau
            ");
            $requeteModifierHoraires->execute([
                ':commentaire_horaires'  => $nouveaux_horaires !== '' ? $nouveaux_horaires : null,
                ':id_trousseau_element' => $id_trousseau_element,
                ':id_trousseau'         => $id_trousseau
            ]);
            $message = "Horaires mis à jour.";
        } catch (PDOException $e) {
            $message = "Erreur : " . $e->getMessage();
        }
    }
}



try {
    // Infos du trousseau + détenteur actuel
    $requeteInfoTrousseau = $pdo->prepare("
        SELECT
            t.id_trousseau,
            t.numero_trousseau,
            t.statut,
            t.commentaire,
            p.nom,
            p.prenom,
            p.service,
            h.date_remise,
            h.date_restitution,
            h.decharge_signee
        FROM trousseaux t
        LEFT JOIN historique_trousseaux h
            ON t.id_trousseau = h.id_trousseau
            AND h.date_restitution IS NULL
        LEFT JOIN personnes p
            ON h.id_personne = p.id_personne
        WHERE t.id_trousseau = :id_trousseau
    ");

    $requeteInfoTrousseau->execute([
        ':id_trousseau' => $id_trousseau
    ]);

    $trousseau = $requeteInfoTrousseau->fetch(PDO::FETCH_ASSOC);

    if (!$trousseau) {
        echo "<h1>Fiche trousseau</h1>";
        echo "<div class='card'>";
        echo "<p>Trousseau introuvable.</p>";
        echo "<a href='trousseaux.php' class='btn'>Retour aux trousseaux</a>";
        echo "</div>";
        include 'includes/footer.php';
        exit;
    }

    // Contenu du trousseau : clés + badges + accès associés
    $requeteElementsActifs = $pdo->prepare("
        SELECT
            te.id_trousseau_element,
            te.type_element,
            te.statut AS statut_element,
            te.date_ajout,
            te.date_retrait,
            te.commentaire,
            te.commentaire_horaires,
            rc.reference_cle,
            b.identifiant_interne AS badge,
            b.type_badge,
            GROUP_CONCAT(
                bat.nom_batiment,
                CASE WHEN p.nom_porte IS NOT NULL THEN CONCAT(' — ', p.nom_porte) ELSE '' END
                ORDER BY bat.nom_batiment
                SEPARATOR ' | '
            ) AS acces_batiments
        FROM trousseau_elements te
        LEFT JOIN references_cles rc ON te.id_reference_cle = rc.id_reference_cle
        LEFT JOIN badges b ON te.id_badge = b.id_badge
        LEFT JOIN element_acces ea ON (
            (te.type_element = 'cle'   AND ea.id_reference_cle = te.id_reference_cle)
            OR
            (te.type_element = 'badge' AND ea.id_badge = te.id_badge)
        )
        LEFT JOIN batiments bat ON ea.id_batiment = bat.id_batiment
        LEFT JOIN portes p ON ea.id_porte = p.id_porte
        WHERE te.id_trousseau = :id_trousseau
        AND te.statut = 'Présent'
        AND te.date_retrait IS NULL
        GROUP BY te.id_trousseau_element, te.type_element, te.statut, te.date_ajout,
                 te.date_retrait, te.commentaire, te.commentaire_horaires,
                 rc.reference_cle, b.identifiant_interne, b.type_badge
        ORDER BY te.type_element, rc.reference_cle, b.identifiant_interne
    ");

    $requeteElementsActifs->execute([
        ':id_trousseau' => $id_trousseau
    ]);

    $elements = $requeteElementsActifs->fetchAll(PDO::FETCH_ASSOC);


    $requeteAnciensElements = $pdo->prepare("
        SELECT
            te.id_trousseau_element,
            te.type_element,
            te.statut AS statut_element,
            te.date_ajout,
            te.date_retrait,
            te.commentaire,
            rc.reference_cle,
            b.identifiant_interne AS badge
        FROM trousseau_elements te
        LEFT JOIN references_cles rc ON te.id_reference_cle = rc.id_reference_cle
        LEFT JOIN badges b ON te.id_badge = b.id_badge
        WHERE te.id_trousseau = :id_trousseau
        AND (te.statut <> 'Présent' OR te.date_retrait IS NOT NULL)
        ORDER BY te.date_retrait DESC, te.type_element
    ");

    $requeteAnciensElements->execute([
    ':id_trousseau' => $id_trousseau]);

    $anciens_elements = $requeteAnciensElements->fetchAll(PDO::FETCH_ASSOC);




    // Liste des références de clés pour le formulaire
    $requeteListeRefs = $pdo->query("
        SELECT id_reference_cle, reference_cle
        FROM references_cles
        ORDER BY reference_cle
    ");

    $reference_cles = $requeteListeRefs->fetchAll(PDO::FETCH_ASSOC);

    // Liste des badges disponibles (exclus ceux déjà actifs dans un autre trousseau)
    $requeteListeBadges = $pdo->prepare("
        SELECT id_badge, identifiant_interne, type_badge
        FROM badges
        WHERE statut NOT IN ('Perdu')
        AND id_badge NOT IN (
            SELECT id_badge FROM trousseau_elements
            WHERE statut = 'Présent'
            AND date_retrait IS NULL
            AND id_badge IS NOT NULL
            AND id_trousseau != :id_trousseau_exclu
        )
        ORDER BY identifiant_interne
    ");
    $requeteListeBadges->execute([':id_trousseau_exclu' => $id_trousseau]);
    $badges = $requeteListeBadges->fetchAll(PDO::FETCH_ASSOC);

    //Liste des personnes pour Attribution
    $requeteListePersonnes = $pdo->query("
        SELECT id_personne, nom, prenom, service
        FROM personnes
        ORDER BY nom,prenom
    ");

    $personnes = $requeteListePersonnes->fetchAll(PDO::FETCH_ASSOC);



} catch (PDOException $e) {
    die("Erreur SQL : " . $e->getMessage());
}
?>

<!-- HTML -->

<h1>Fiche trousseau <?= htmlspecialchars($trousseau['numero_trousseau']) ?></h1>

<?php afficherMessage($message); ?>

<!-- Onglets -->
<div class="tabs">
    <a href="fiche_trousseau.php?id=<?= urlencode($id_trousseau) ?>&onglet=informations"
       class="tab-lien <?= $onglet_actif === 'informations' ? 'active' : '' ?>">Informations</a>
    <a href="fiche_trousseau.php?id=<?= urlencode($id_trousseau) ?>&onglet=contenu"
       class="tab-lien <?= $onglet_actif === 'contenu' ? 'active' : '' ?>">Contenu du trousseau</a>
    <a href="fiche_trousseau.php?id=<?= urlencode($id_trousseau) ?>&onglet=historique"
       class="tab-lien <?= $onglet_actif === 'historique' ? 'active' : '' ?>">Historique</a>
</div>

<!-- Onglet informations -->
<?php if ($onglet_actif === 'informations') : ?>

<div class="card">
    <h2>Informations générales</h2>

    <p><strong>Numéro :</strong> <?= htmlspecialchars($trousseau['numero_trousseau']) ?></p>
    <p><strong>Statut :</strong> <?= htmlspecialchars($trousseau['statut']) ?></p>

    <p>
        <strong>Détenteur actuel :</strong>
        <?php if (!empty($trousseau['nom'])): ?>
            <?= htmlspecialchars($trousseau['prenom'] . ' ' . $trousseau['nom']) ?>
            — <?= htmlspecialchars($trousseau['service'] ?? '') ?>
        <?php else: ?>
            Aucun
        <?php endif; ?>
    </p>

    <p>
        <strong>Date de remise :</strong>
        <?= htmlspecialchars($trousseau['date_remise'] ?? '-') ?>
    </p>

    <p>
        <strong>Décharge signée :</strong>
        <?= afficherDecharge($trousseau['decharge_signee']) ?>
        <?php if ($trousseau['statut'] === 'Attribué' && (int)$trousseau['decharge_signee'] !== 1) : ?>
            <form method="POST" action="fiche_trousseau.php?id=<?= urlencode($id_trousseau) ?>&onglet=informations" style="display:inline-block; margin-left:10px;">
                <input type="hidden" name="signer_decharge" value="1">
                <button type="submit" class="btn btn-success">Marquer comme signée</button>
            </form>
        <?php endif; ?>
    </p>

    <p><strong>Commentaire :</strong> <?= htmlspecialchars($trousseau['commentaire'] ?? '') ?></p>

    <a href="trousseaux.php" class="btn btn-secondary">Retour</a>
    <?php if ($trousseau['statut'] !== 'Perdu') : ?>
        <form
            method="POST"
            action="fiche_trousseau.php?id=<?= urlencode($id_trousseau) ?>&onglet=informations"
            style="display:inline-block;"
            onsubmit="return confirm('Confirmer la perte de ce trousseau ?');"
        >
            <input type="hidden" name="declarer_trousseau_perdu" value="1">
            <button type="submit" class="btn btn-danger">Déclarer le trousseau perdu</button>
        </form>
    <?php endif; ?>
    <?php if ($trousseau['statut'] === 'Perdu') : ?>
        <form
            method="POST"
            action="fiche_trousseau.php?id=<?= urlencode($id_trousseau) ?>&onglet=informations"
            style="display:inline-block;"
            onsubmit="return confirm('Marquer ce trousseau comme retrouvé ?');"
        >
            <input type="hidden" name="trousseau_retrouve" value="1">
            <button type="submit" class="btn btn-success">Marquer comme retrouvé</button>
        </form>
    <?php endif; ?>
</div>

<?php if ($trousseau['statut'] === 'Disponible') : ?>
<div class="card">
    <h2>Attribuer le trousseau</h2>
    <form method="POST" action="fiche_trousseau.php?id=<?= urlencode($id_trousseau) ?>&onglet=informations">
        <input type="hidden" name="attribuer_trousseau" value="1">
        <label>Personne *</label>
        <select name="id_personne" required>
            <option value="">-- Choisir une personne --</option>
            <?php foreach ($personnes as $personne): ?>
                <option value="<?= htmlspecialchars($personne['id_personne']) ?>">
                    <?= htmlspecialchars($personne['prenom'] . ' ' . $personne['nom'] . ' — ' . $personne['service']) ?>
                </option>
            <?php endforeach; ?>
        </select>
        <label>Date remise</label>
        <input type="date" name="date_remise" value="<?= date('Y-m-d') ?>" required>
        <label>Décharge signée</label>
        <select name="decharge_signee" required>
            <option value="0" selected>Non</option>
            <option value="1">Oui</option>
        </select>
        <label>Commentaire</label>
        <textarea name="commentaire_attribution" placeholder="Commentaire sur l'attribution"></textarea>
        <button type="submit" class="btn">Attribuer le trousseau</button>
    </form>
</div>
<?php endif; ?>

<?php if ($trousseau['statut'] === 'Attribué') : ?>
<div class="card">
    <h2>Restitution du trousseau</h2>
    <form method="POST" action="fiche_trousseau.php?id=<?= urlencode($id_trousseau) ?>&onglet=informations" onsubmit="return confirm('Confirmer la restitution de ce trousseau ?');">
        <input type="hidden" name="restituer_trousseau" value="1">
        <label>Date restitution</label>
        <input type="date" name="date_restitution" value="<?= date('Y-m-d') ?>" required>
        <label>Commentaire</label>
        <textarea name="commentaire_restitution" placeholder="Commentaire sur la restitution"></textarea>
        <button type="submit" class="btn">Restituer le trousseau</button>
    </form>
</div>
<?php endif; ?>

<?php endif; ?>

<!-- Onglet contenu -->
<?php if ($onglet_actif === 'contenu') : ?>

<?php if ($trousseau['statut'] === 'Perdu') : ?>
<div class="card">
    <p><strong>Ce trousseau est <?= htmlspecialchars($trousseau['statut']) ?>.</strong> Il n'est plus possible d'ajouter ou de modifier des éléments.</p>
</div>
<?php else : ?>

<div class="card">
    <h2>Ajouter un élément au trousseau</h2>
    <form method="POST" action="fiche_trousseau.php?id=<?= urlencode($id_trousseau) ?>&onglet=contenu">
        <input type="hidden" name="ajouter_element" value="1">
        <label>Type d'élément *</label>
        <select name="type_element" id="type_element" required onchange="basculerChamps(this.value)">
            <option value="">-- Choisir --</option>
            <option value="cle">Clé</option>
            <option value="badge">Badge</option>
        </select>

        <div id="champ-cle" style="display:none;">
            <label>Référence de clé</label>
            <select name="id_reference_cle">
                <option value="">-- Aucune --</option>
                <?php foreach ($reference_cles as $ref): ?>
                    <option value="<?= htmlspecialchars($ref['id_reference_cle']) ?>">
                        <?= htmlspecialchars($ref['reference_cle']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div id="champ-badge" style="display:none;">
            <label>Badge</label>
            <select name="id_badge">
                <option value="">-- Aucun --</option>
                <?php foreach ($badges as $badge): ?>
                    <option value="<?= htmlspecialchars($badge['id_badge']) ?>">
                        <?= htmlspecialchars($badge['identifiant_interne']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <label>Horaires personnalisés</label>
            <textarea name="commentaire_horaires"
                placeholder="Ex : Mairie : 08h00 - 16h00 ; Salle Aragon : 06h00 - 12h00"></textarea>
        </div>

        <label>Date d'ajout</label>
        <input type="date" name="date_ajout" value="<?= date('Y-m-d') ?>" required>
        <label>Commentaire</label>
        <textarea name="commentaire" placeholder="Commentaire sur l'ajout de l'élément"></textarea>
        <button type="submit" class="btn">Ajouter l'élément au trousseau</button>
    </form>
</div>

<?php endif; ?>

<div class="card">
    <h2>Contenu actuellement dans le trousseau</h2>
    <table>
        <thead>
            <tr>
                <th>Type</th>
                <th>Référence / ID</th>
                <th>Bâtiments / Portes</th>
                <th>Statut</th>
                <th>Date ajout</th>
                <th>Commentaire</th>
                <th>Horaires badge</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($elements)): ?>
                <tr>
                    <td colspan="8">Aucun élément dans ce trousseau.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($elements as $element): ?>
                    <tr>
                        <td><?= htmlspecialchars($element['type_element']) ?></td>
                        <td>
                            <?php if ($element['type_element'] === 'cle'): ?>
                                <?= htmlspecialchars($element['reference_cle'] ?? '-') ?>
                            <?php else: ?>
                                <?= htmlspecialchars($element['badge'] ?? '-') ?>
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars($element['acces_batiments'] ?? '—') ?></td>
                        <td><?= htmlspecialchars($element['statut_element'] ?? '-') ?></td>
                        <td><?= formaterDate($element['date_ajout'] ?? null) ?></td>
                        <td><?= htmlspecialchars($element['commentaire'] ?? '-') ?></td>
                        <td>
                            <?php if ($element['type_element'] === 'badge'): ?>
                                <form method="POST"
                                    action="fiche_trousseau.php?id=<?= urlencode($id_trousseau) ?>&onglet=contenu"
                                    style="display:flex; gap:6px; align-items:center;">
                                    <input type="hidden" name="modifier_horaires" value="1">
                                    <input type="hidden" name="id_trousseau_element" value="<?= htmlspecialchars($element['id_trousseau_element']) ?>">
                                    <input type="text" name="nouveaux_horaires"
                                        value="<?= htmlspecialchars($element['commentaire_horaires'] ?? '') ?>"
                                        placeholder="Ex : 08h00 - 18h00"
                                        style="flex:1; margin:0;">
                                    <button type="submit" class="btn btn-secondary" style="white-space:nowrap;">Modifier</button>
                                </form>
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($element['statut_element'] === 'Présent') : ?>
                                <form method="POST"
                                    action="fiche_trousseau.php?id=<?= urlencode($id_trousseau) ?>&onglet=contenu"
                                    style="display:inline-block;"
                                    onsubmit="return confirm('Retirer cet élément du trousseau ?');">
                                    <input type="hidden" name="retirer_element" value="1">
                                    <input type="hidden" name="id_trousseau_element" value="<?= htmlspecialchars($element['id_trousseau_element']) ?>">
                                    <button type="submit" class="btn btn-secondary">Retirer</button>
                                </form>
                                <form method="POST"
                                    action="fiche_trousseau.php?id=<?= urlencode($id_trousseau) ?>&onglet=contenu"
                                    style="display:inline-block;"
                                    onsubmit="return confirm('Déclarer cet élément comme perdu ?');">
                                    <input type="hidden" name="declarer_element_perdu" value="1">
                                    <input type="hidden" name="id_trousseau_element" value="<?= htmlspecialchars($element['id_trousseau_element']) ?>">
                                    <button type="submit" class="btn btn-danger">Perdu</button>
                                </form>
                            <?php else : ?>
                                -
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php endif; ?>

<!-- Onglet historique -->
<?php if ($onglet_actif === 'historique') : ?>

<div class="card">
    <h2>Historique des éléments retirés / perdus</h2>
    <table>
        <thead>
            <tr>
                <th>Type</th>
                <th>Référence / ID</th>
                <th>Statut</th>
                <th>Date ajout</th>
                <th>Date retrait</th>
                <th>Commentaire</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($anciens_elements)) : ?>
                <tr>
                    <td colspan="6">Aucun élément retiré ou perdu pour ce trousseau.</td>
                </tr>
            <?php else : ?>
                <?php foreach ($anciens_elements as $element) : ?>
                    <tr>
                        <td><?= htmlspecialchars($element['type_element']) ?></td>
                        <td>
                            <?php if ($element['type_element'] === 'cle') : ?>
                                <?= htmlspecialchars($element['reference_cle'] ?? '-') ?>
                            <?php else : ?>
                                <?= htmlspecialchars($element['badge'] ?? '-') ?>
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars($element['statut_element'] ?? '-') ?></td>
                        <td><?= formaterDate($element['date_ajout'] ?? null) ?></td>
                        <td><?= formaterDate($element['date_retrait'] ?? null) ?></td>
                        <td><?= htmlspecialchars($element['commentaire'] ?? '-') ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php endif; ?>

<script src="assets/js/fiche_trousseau.js"></script>

<?php include 'includes/footer.php'; ?>
