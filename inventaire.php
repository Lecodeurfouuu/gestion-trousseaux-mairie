<?php
require_once 'config/database.php';
require_once 'includes/fonctions.php';
$page_title = 'Inventaire';
include 'includes/header.php';

$message = '';
$onglet_actif = $_GET['onglet'] ?? 'cles';
$gerer_cle = (int)($_GET['gerer_cle'] ?? 0);
$gerer_badge = (int)($_GET['gerer_badge'] ?? 0);

// Supprimer un accès
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['supprimer_acces'])) {
    $id_element_acces = (int)($_POST['id_element_acces'] ?? 0);
    if ($id_element_acces > 0) {
        try {
            $requeteSupprimerAcces = $pdo->prepare("DELETE FROM element_acces WHERE id_element_acces = :id");
            $requeteSupprimerAcces->execute([':id' => $id_element_acces]);
            $message = "Accès supprimé avec succès.";
        } catch (PDOException $e) {
            $message = "Erreur : " . $e->getMessage();
        }
    }
}

// Modifier le nom/commentaire d'une référence de clé
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['modifier_reference'])) {
    $id_reference_cle = (int)($_POST['id_reference_cle'] ?? 0);
    $nouvelle_nom = trim($_POST['nouvelle_reference'] ?? '');
    $nouveau_commentaire = trim($_POST['nouveau_commentaire'] ?? '');
    if ($id_reference_cle > 0 && $nouveau_nom !== '') {
        try {
            $requeteModifierReference = $pdo->prepare("
                UPDATE references_cles SET reference_cle = :reference_cle, commentaire = :commentaire
                WHERE id_reference_cle = :id_reference_cle
            ");
            $requeteModifierReference->execute([
                ':reference_cle' => $nouvelle_reference,
                ':commentaire' => $nouveau_commentaire,
                ':id' => $id_reference_cle
            ]);
            $message = "Référence modifiée.";
        } catch (PDOException $e) {
            $message = "Erreur : " . $e->getMessage();
        }
    }
}

// Modifier l'identifiant d'un badge
if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['modifier_badge'])) {
    $id_badge = (int)($_POST['id_badge'] ?? 0);
    $nouvel_identifiant_interne = trim($_POST['nouvel_identifiant_interne'] ?? '');

    if ($id_badge > 0 && $nouvel_identifiant_interne !== '') {
        try {
            $requeteModifierBadge = $pdo->prepare("
                UPDATE badges SET identifiant_interne = :identifiant
                WHERE id_badge = :id
            ");
            $requeteModifierBadge->execute([
                ':identifiant' => $nouvel_identifiant_interne,
                ':id' => $id_badge
            ]);
            $message = "Badge modifié.";
        } catch (PDOException $e) {
            $message = "Erreur : " . $e->getMessage();
        }
    }
}

// Ajouter un accès à une clé ou un badge existant
if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajouter_acces_existant'])) {
    $type_element = $_POST['type_element_acces'] ?? '';
    $id_element = (int)($_POST['id_element_acces_existant'] ?? 0);
    $id_batiment_acces = (int)($_POST['id_batiment_acces'] ?? 0);
    $id_porte_acces = (int)($_POST['id_porte_acces'] ?? 0);

    if ($id_element > 0 && $id_batiment_acces > 0) {
        try {
            // Vérification doublon
            if ($type_element === 'cle') {
                $requeteDoublonAcces = $pdo->prepare("
                    SELECT COUNT(*) FROM element_acces
                    WHERE id_reference_cle = :id_element
                    AND id_batiment = :id_batiment
                    AND (id_porte = :id_porte OR (id_porte IS NULL AND :id_porte2 = 0))
                ");
            } else {
                $requeteDoublonAcces = $pdo->prepare("
                    SELECT COUNT(*) FROM element_acces
                    WHERE id_badge = :id_element
                    AND id_batiment = :id_batiment
                    AND (id_porte = :id_porte OR (id_porte IS NULL AND :id_porte2 = 0))
                ");
            }
            $requeteDoublonAcces->execute([
                ':id_element' => $id_element,
                ':id_batiment' => $id_batiment_acces,
                ':id_porte' => $id_porte_acces > 0 ? $id_porte_acces : null,
                ':id_porte2' => $id_porte_acces
            ]);

            if ($requeteDoublonAcces->fetchColumn() > 0) {
                $message = "Cet accès existe déjà.";
            } else {
                if ($type_element === 'cle') {
                    $requeteAjouterAccesExistant = $pdo->prepare("
                        INSERT INTO element_acces (type_element, id_reference_cle, id_batiment, id_porte)
                        VALUES ('cle', :id_element, :id_batiment, :id_porte)
                    ");
                } else {
                    $requeteAjouterAccesExistant = $pdo->prepare("
                        INSERT INTO element_acces (type_element, id_badge, id_batiment, id_porte)
                        VALUES ('badge', :id_element, :id_batiment, :id_porte)
                    ");
                }
                $requeteAjouterAccesExistant->execute([
                    ':id_element' => $id_element,
                    ':id_batiment' => $id_batiment_acces,
                    ':id_porte' => $id_porte_acces > 0 ? $id_porte_acces : null
                ]);
                $message = "Accès ajouté avec succès.";
            }
        } catch (PDOException $e) {
            $message = "Erreur : " . $e->getMessage();
        }
    }
}

// Badge retrouvé

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['badge_retrouve'])) {
    $id_badge_retrouve = (int)($_POST['id_badge_retrouve'] ?? 0);
    if ($id_badge_retrouve > 0) {
        try {
            $requeteBadgeRetrouve = $pdo->prepare("
                UPDATE badges SET statut = 'Disponible'
                WHERE id_badge = :id_badge AND statut = 'Perdu'
            ");
            $requeteBadgeRetrouve->execute([':id_badge' => $id_badge_retrouve]);
            $message = "Badge marqué comme retrouvé.";
        } catch (PDOException $e) {
            $message = "Erreur : " . $e->getMessage();
        }
    }
}

// Ajout d'une porte à un bâtiment

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajouter_porte'])) {
    $id_batiment_porte = (int)($_POST['id_batiment_porte'] ?? 0);
    $nom_porte = trim($_POST['nom_porte'] ?? '');

    if ($id_batiment_porte === 0 || $nom_porte === '') {
        $message = "Veuillez sélectionner un bâtiment et saisir un nom de porte.";
    } else {
        try {
            $requeteAjoutPorte = $pdo->prepare("
                INSERT INTO portes (id_batiment, nom_porte) VALUES (:id_batiment, :nom_porte)
            ");
            $requeteAjoutPorte->execute([':id_batiment' => $id_batiment_porte, ':nom_porte' => $nom_porte]);
            $message = "Porte ajoutée avec succès.";
        } catch (PDOException $e) {
            $message = "Erreur : " . $e->getMessage();
        }
    }
}

// Modification d'une porte

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['modifier_porte'])) {
    $id_porte = (int)($_POST['id_porte'] ?? 0);
    $nouveau_nom = trim($_POST['nouveau_nom_porte'] ?? '');

    if ($id_porte === 0 || $nouveau_nom === '') {
        $message = "Données invalides pour la modification de la porte.";
    } else {
        try {
            $requeteModifierPorte = $pdo->prepare("
                UPDATE portes SET nom_porte = :nom_porte WHERE id_porte = :id_porte
            ");
            $requeteModifierPorte->execute([':nom_porte' => $nouveau_nom, ':id_porte' => $id_porte]);
            $message = "Porte modifiée avec succès.";
        } catch (PDOException $e) {
            $message = "Erreur : " . $e->getMessage();
        }
    }
}

// Ajout d'une référence de clé + accès

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajouter_reference'])) {
    $reference_cle = trim($_POST['reference_cle'] ?? '');
    $commentaire = trim($_POST['commentaire'] ?? '');
    $acces_batiments = $_POST['acces_batiment'] ?? [];
    $acces_portes = $_POST['acces_porte'] ?? [];

    if ($reference_cle === '') {
        $message = 'Veuillez saisir une référence de clé.';
    } else {
        try {
            $requeteDoublonReference = $pdo->prepare('
                SELECT COUNT(*) FROM references_cles
                WHERE LOWER(reference_cle) = LOWER(:reference_cle)
            ');
            $requeteDoublonReference->execute([':reference_cle' => $reference_cle]);

            if ($requeteDoublonReference->fetchColumn() > 0) {
                $message = 'Cette référence de clé existe déjà.';
            } else {
                $requeteAjoutReference = $pdo->prepare('
                    INSERT INTO references_cles (reference_cle, commentaire)
                    VALUES (:reference_cle, :commentaire)
                ');
                $requeteAjoutReference->execute([
                    ':reference_cle' => $reference_cle,
                    ':commentaire' => $commentaire !== '' ? $commentaire : null
                ]);
                $id_nouvelle_reference = $pdo->lastInsertId();

                // Insertion des accès bâtiments (lignes non vides uniquement)
                foreach ($acces_batiments as $index => $id_batiment) {
                    if (!empty($id_batiment)) {
                        $id_porte = !empty($acces_portes[$index]) ? (int)$acces_portes[$index] : null;
                        $requeteAjoutAccesCle = $pdo->prepare("
                            INSERT INTO element_acces (type_element, id_reference_cle, id_batiment, id_porte)
                            VALUES ('cle', :id_reference_cle, :id_batiment, :id_porte)
                        ");
                        $requeteAjoutAccesCle->execute([
                            ':id_reference_cle' => $id_nouvelle_reference,
                            ':id_batiment' => $id_batiment,
                            ':id_porte' => $id_porte
                        ]);
                    }
                }
                $message = 'Référence de clé ajoutée avec succès.';
            }
        } catch (PDOException $e) {
            $message = 'Erreur lors de l\'ajout de la référence de clé: ' . $e->getMessage();
        }
    }
}

// Ajout d'un badge + accès

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajouter_badge'])) {
    $identifiant_interne  = trim($_POST['identifiant_interne'] ?? '');
    $type_badge = trim($_POST['type_badge'] ?? '');
    $acces_batiments = $_POST['acces_batiment'] ?? [];
    $acces_portes = $_POST['acces_porte'] ?? [];

    if ($identifiant_interne === '') {
        $message = 'Veuillez saisir un identifiant interne.';
    } else {
        try {
            $requeteDoublonBadge = $pdo->prepare('
                SELECT COUNT(*) FROM badges
                WHERE LOWER(identifiant_interne) = LOWER(:identifiant_interne)
            ');
            $requeteDoublonBadge->execute([':identifiant_interne' => $identifiant_interne]);

            if ($requeteDoublonBadge->fetchColumn() > 0) {
                $message = 'Ce badge existe déjà.';
            } else {
                $requeteAjoutBadge = $pdo->prepare('
                    INSERT INTO badges (identifiant_interne, type_badge, statut)
                    VALUES (:identifiant_interne, :type_badge, :statut)
                ');
                $requeteAjoutBadge->execute([
                    ':identifiant_interne' => $identifiant_interne,
                    ':type_badge' => $type_badge,
                    ':statut' => 'Disponible'
                ]);
                $id_nouveau_badge = $pdo->lastInsertId();

                foreach ($acces_batiments as $index => $id_batiment) {
                    if (!empty($id_batiment)) {
                        $id_porte = !empty($acces_portes[$index]) ? (int)$acces_portes[$index] : null;
                        $requeteAjoutAccesBadge = $pdo->prepare("
                            INSERT INTO element_acces (type_element, id_badge, id_batiment, id_porte)
                            VALUES ('badge', :id_badge, :id_batiment, :id_porte)
                        ");
                        $requeteAjoutAccesBadge->execute([
                            ':id_badge' => $id_nouveau_badge,
                            ':id_batiment' => $id_batiment,
                            ':id_porte' => $id_porte
                        ]);
                    }
                }
                $message = 'Badge ajouté avec succès.';
            }
        } catch (PDOException $e) {
            $message = "Erreur lors de l'ajout du badge : " . $e->getMessage();
        }
    }
}

// Ajout d'un bâtiment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajouter_batiment'])) {
    $nom_batiment = trim($_POST['nom_batiment'] ?? '');
    $adresse = trim($_POST['adresse'] ?? '');
    $commentaire = trim($_POST['commentaire'] ?? '');

    if ($nom_batiment === '') {
        $message = 'Veuillez saisir le nom du bâtiment.';
    } else {
        try {
            $requeteDoublonBatiment = $pdo->prepare("
                SELECT COUNT(*) FROM batiments
                WHERE LOWER(nom_batiment) = LOWER(:nom_batiment)
            ");
            $requeteDoublonBatiment->execute([':nom_batiment' => $nom_batiment]);

            if ($requeteDoublonBatiment->fetchColumn() > 0) {
                $message = 'Ce bâtiment existe déjà.';
            } else {
                $requeteAjoutBatiment = $pdo->prepare("
                    INSERT INTO batiments (nom_batiment, adresse, commentaire)
                    VALUES (:nom_batiment, :adresse, :commentaire)
                ");
                $requeteAjoutBatiment->execute([
                    ':nom_batiment' => $nom_batiment,
                    ':adresse' => $adresse !== '' ? $adresse : null,
                    ':commentaire' => $commentaire !== '' ? $commentaire : null
                ]);

                // Ajouter automatiquement la porte "Toutes les portes"
                $id_nouveau_batiment = $pdo->lastInsertId();
                $requetePorteDefaut = $pdo->prepare("
                    INSERT INTO portes (id_batiment, nom_porte) VALUES (:id_batiment, 'Toutes les portes')
                ");
                $requetePorteDefaut->execute([':id_batiment' => $id_nouveau_batiment]);

                $message = 'Bâtiment ajouté avec succès.';
            }
        } catch (PDOException $e) {
            $message = "Erreur lors de l'ajout du bâtiment : " . $e->getMessage();
        }
    }
}

// Récupération des données

try {
    // Références clés avec accès (bâtiment + porte)
    $references = $pdo->query("
        SELECT
            rc.id_reference_cle,
            rc.reference_cle,
            rc.commentaire,
            GROUP_CONCAT(
                bat.nom_batiment,
                CASE WHEN p.nom_porte IS NOT NULL THEN CONCAT(' — ', p.nom_porte) ELSE '' END
                ORDER BY bat.nom_batiment
                SEPARATOR ' | '
            ) AS acces_batiments
        FROM references_cles rc
        LEFT JOIN element_acces ea ON rc.id_reference_cle = ea.id_reference_cle
        LEFT JOIN batiments bat ON ea.id_batiment = bat.id_batiment
        LEFT JOIN portes p ON ea.id_porte = p.id_porte
        GROUP BY rc.id_reference_cle, rc.reference_cle, rc.commentaire
        ORDER BY rc.reference_cle
    ")->fetchAll(PDO::FETCH_ASSOC);

    // Badges avec accès
    $badges = $pdo->query("
        SELECT
            b.id_badge,
            b.identifiant_interne,
            b.type_badge,
            b.statut,
            GROUP_CONCAT(
                bat.nom_batiment,
                CASE WHEN p.nom_porte IS NOT NULL THEN CONCAT(' — ', p.nom_porte) ELSE '' END
                ORDER BY bat.nom_batiment
                SEPARATOR ' | '
            ) AS acces_batiments
        FROM badges b
        LEFT JOIN element_acces ea ON b.id_badge = ea.id_badge
        LEFT JOIN batiments bat ON ea.id_batiment = bat.id_batiment
        LEFT JOIN portes p ON ea.id_porte = p.id_porte
        GROUP BY b.id_badge, b.identifiant_interne, b.type_badge, b.statut
        ORDER BY b.identifiant_interne
    ")->fetchAll(PDO::FETCH_ASSOC);

    // Bâtiments
    $batiments = $pdo->query("
        SELECT id_batiment, nom_batiment, adresse, commentaire
        FROM batiments ORDER BY nom_batiment
    ")->fetchAll(PDO::FETCH_ASSOC);

    // Toutes les portes (pour JS + affichage bâtiments)
    $toutes_portes = $pdo->query("
        SELECT p.id_porte, p.id_batiment, p.nom_porte, bat.nom_batiment
        FROM portes p
        JOIN batiments bat ON p.id_batiment = bat.id_batiment
        ORDER BY bat.nom_batiment, p.nom_porte
    ")->fetchAll(PDO::FETCH_ASSOC);

    // Regrouper les portes par bâtiment pour le JS
    $portes_par_batiment = [];
    foreach ($toutes_portes as $porte) {
        $portes_par_batiment[$porte['id_batiment']][] = [
            'id' => $porte['id_porte'],
            'nom' => $porte['nom_porte']
        ];
    }

    // Portes par bâtiment pour l'affichage HTML
    $portes_par_batiment_html = [];
    foreach ($toutes_portes as $porte) {
        $portes_par_batiment_html[$porte['id_batiment']][] = $porte;
    }

    // Accès de la clé en cours de gestion
    $acces_cle_geree = [];
    $info_cle_geree = null;
    if ($gerer_cle > 0) {
        $requeteInfoCle = $pdo->prepare("SELECT * FROM references_cles WHERE id_reference_cle = :id");
        $requeteInfoCle->execute([':id' => $gerer_cle]);
        $info_cle_geree = $requeteInfoCle->fetch(PDO::FETCH_ASSOC);

        $requeteAccesCle = $pdo->prepare("
            SELECT ea.id_element_acces, bat.nom_batiment, p.nom_porte
            FROM element_acces ea
            JOIN batiments bat ON ea.id_batiment = bat.id_batiment
            LEFT JOIN portes p ON ea.id_porte = p.id_porte
            WHERE ea.id_reference_cle = :id
        ");
        $requeteAccesCle->execute([':id' => $gerer_cle]);
        $acces_cle_geree = $requeteAccesCle->fetchAll(PDO::FETCH_ASSOC);
    }

    // Accès du badge en cours de gestion
    $acces_badge_geree = [];
    $info_badge_geree = null;
    if ($gerer_badge > 0) {
        $requeteInfoBadge = $pdo->prepare("SELECT * FROM badges WHERE id_badge = :id");
        $requeteInfoBadge->execute([':id' => $gerer_badge]);
        $info_badge_geree = $requeteInfoBadge->fetch(PDO::FETCH_ASSOC);

        $requeteAccesBadge = $pdo->prepare("
            SELECT ea.id_element_acces, bat.nom_batiment, p.nom_porte
            FROM element_acces ea
            JOIN batiments bat ON ea.id_batiment = bat.id_batiment
            LEFT JOIN portes p ON ea.id_porte = p.id_porte
            WHERE ea.id_badge = :id
        ");
        $requeteAccesBadge->execute([':id' => $gerer_badge]);
        $acces_badge_geree = $requeteAccesBadge->fetchAll(PDO::FETCH_ASSOC);
    }

} catch (PDOException $e) {
    die("<p>Erreur SQL : " . $e->getMessage());
}
?>

<h1>Inventaire</h1>

<?php if ($message !== '') : ?>
    <div class="card">
        <p><?= htmlspecialchars($message) ?></p>
    </div>
<?php endif; ?>

<!-- Onglets internes -->
<div class="tabs">
    <a href="inventaire.php?onglet=cles" class="tab-lien <?= $onglet_actif === 'cles' ? 'active' : '' ?>">Références de clés</a>
    <a href="inventaire.php?onglet=badges" class="tab-lien <?= $onglet_actif === 'badges' ? 'active' : '' ?>">Badges</a>
    <a href="inventaire.php?onglet=batiments" class="tab-lien <?= $onglet_actif === 'batiments' ? 'active' : '' ?>">Bâtiments</a>
</div>

<!-- Onglet clés -->
<div id="tab-cles" class="tab-content" <?= $onglet_actif !== 'cles' ? 'style="display:none;"' : '' ?>>

    <div class="card">
        <h2>Ajouter une référence de clé</h2>
        <form method="POST" action="inventaire.php?onglet=cles">
            <input type="hidden" name="ajouter_reference" value="1">
            <label>Référence de clé *</label>
            <input type="text" name="reference_cle" placeholder="Ex : REF-45" required>
            <label>Commentaire</label>
            <textarea name="commentaire"></textarea>
            <label>Bâtiments et portes accessibles</label>
            <table id="table-acces-cle" style="margin-top:8px; margin-bottom:8px;">
                <thead><tr><th>Bâtiment</th><th>Porte</th><th></th></tr></thead>
                <tbody id="tbody-acces-cle"></tbody>
            </table>
            <button type="button" class="btn btn-secondary" onclick="ajouterLigneAcces('tbody-acces-cle')" style="margin-bottom:12px;">+ Ajouter un accès</button>
            <br>
            <button type="submit" class="btn">Ajouter la référence</button>
        </form>
    </div>

    <div class="card">
        <h2>Liste des références de clés</h2>
        <table>
            <thead><tr><th>Référence</th><th>Bâtiments / Portes</th><th>Commentaire</th><th>Action</th></tr></thead>
            <tbody>
                <?php if (empty($references)) : ?>
                    <tr><td colspan="4">Aucune référence de clé enregistrée.</td></tr>
                <?php else : ?>
                    <?php foreach ($references as $ref) : ?>
                        <tr>
                            <td><?= htmlspecialchars($ref['reference_cle']) ?></td>
                            <td><?= htmlspecialchars($ref['acces_batiments'] ?? '—') ?></td>
                            <td><?= htmlspecialchars($ref['commentaire'] ?? '') ?></td>
                            <td>
                                <a href="inventaire.php?onglet=cles&gerer_cle=<?= $ref['id_reference_cle'] ?>" class="btn btn-secondary">Gérer</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Panneau de gestion d'une clé -->
    <?php if ($gerer_cle > 0 && $info_cle_geree) : ?>
    <div class="card">
        <h2>Gérer : <?= htmlspecialchars($info_cle_geree['reference_cle']) ?></h2>

        <!-- Modifier les infos -->
        <h3>Modifier les informations</h3>
        <form method="POST" action="inventaire.php?onglet=cles&gerer_cle=<?= $gerer_cle ?>">
            <input type="hidden" name="modifier_reference" value="1">
            <input type="hidden" name="id_reference_cle" value="<?= $gerer_cle ?>">
            <label>Référence</label>
            <input type="text" name="nouveau_nom_reference" value="<?= htmlspecialchars($info_cle_geree['reference_cle']) ?>" required>
            <label>Commentaire</label>
            <textarea name="nouveau_commentaire_reference"><?= htmlspecialchars($info_cle_geree['commentaire'] ?? '') ?></textarea>
            <button type="submit" class="btn">Enregistrer</button>
        </form>

        <!-- Accès actuels -->
        <h3 style="margin-top:14px;">Accès bâtiments / portes</h3>
        <?php if (empty($acces_cle_geree)) : ?>
            <p>Aucun accès enregistré.</p>
        <?php else : ?>
            <table>
                <thead><tr><th>Bâtiment</th><th>Porte</th><th>Action</th></tr></thead>
                <tbody>
                    <?php foreach ($acces_cle_geree as $acces) : ?>
                        <tr>
                            <td><?= htmlspecialchars($acces['nom_batiment']) ?></td>
                            <td><?= htmlspecialchars($acces['nom_porte'] ?? '—') ?></td>
                            <td>
                                <form method="POST" action="inventaire.php?onglet=cles&gerer_cle=<?= $gerer_cle ?>"
                                    onsubmit="return confirm('Supprimer cet accès ?');">
                                    <input type="hidden" name="supprimer_acces" value="1">
                                    <input type="hidden" name="id_element_acces" value="<?= $acces['id_element_acces'] ?>">
                                    <button type="submit" class="btn btn-danger">Supprimer</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>

        <!-- Ajouter un accès -->
        <h3 style="margin-top:14px;">Ajouter un accès</h3>
        <form method="POST" action="inventaire.php?onglet=cles&gerer_cle=<?= $gerer_cle ?>">
            <input type="hidden" name="ajouter_acces_existant" value="1">
            <input type="hidden" name="type_element_acces" value="cle">
            <input type="hidden" name="id_element_acces_existant" value="<?= $gerer_cle ?>">
            <label>Bâtiment</label>
            <select name="id_batiment_acces" id="bat-select-cle" onchange="mettreAJourPortes(this, 'porte-select-cle')" required>
                <option value="">-- Choisir --</option>
                <?php foreach ($batiments as $bat) : ?>
                    <option value="<?= $bat['id_batiment'] ?>"><?= htmlspecialchars($bat['nom_batiment']) ?></option>
                <?php endforeach; ?>
            </select>
            <label>Porte</label>
            <select name="id_porte_acces" id="porte-select-cle">
                <option value="">-- Choisir d'abord un bâtiment --</option>
            </select>
            <button type="submit" class="btn" style="margin-top:12px;">Ajouter</button>
        </form>

        <div style="margin-top:12px;">
            <a href="inventaire.php?onglet=cles" class="btn btn-secondary">Fermer</a>
        </div>
    </div>
    <?php endif; ?>

<!-- Onglet badges -->
<div id="tab-badges" class="tab-content" <?= $onglet_actif !== 'badges' ? 'style="display:none;"' : '' ?>>

    <div class="card">
        <h2>Ajouter un badge</h2>
        <form method="POST" action="inventaire.php?onglet=badges">
            <input type="hidden" name="ajouter_badge" value="1">
            <label>Type de badge *</label>
            <select name="type_badge" required>
                <option value="">-- Choisir --</option>
                <option value="Ela">Ela (Noir)</option>
                <option value="Salto">Salto (Bleu)</option>
            </select>
            <label>Identifiant interne *</label>
            <input type="text" name="identifiant_interne" placeholder="Ex : ELA-6489, BLEU-001" required>
            <label>Bâtiments et portes accessibles</label>
            <table id="table-acces-badge" style="margin-top:8px; margin-bottom:8px;">
                <thead><tr><th>Bâtiment</th><th>Porte</th><th></th></tr></thead>
                <tbody id="tbody-acces-badge"></tbody>
            </table>
            <button type="button" class="btn btn-secondary" onclick="ajouterLigneAcces('tbody-acces-badge')" style="margin-bottom:12px;">+ Ajouter un accès</button>
            <br>
            <button type="submit" class="btn">Ajouter le badge</button>
        </form>
    </div>

    <div class="card">
        <h2>Liste des badges</h2>
        <table>
            <thead><tr><th>Identifiant interne</th><th>Type</th><th>Statut</th><th>Bâtiments / Portes</th><th>Actions</th></tr></thead>
            <tbody>
                <?php if (empty($badges)) : ?>
                    <tr><td colspan="6">Aucun badge enregistré.</td></tr>
                <?php else : ?>
                    <?php foreach ($badges as $badge) : ?>
                        <tr>
                            <td><?= htmlspecialchars($badge['identifiant_interne']) ?></td>
                            <td><?= htmlspecialchars($badge['type_badge'] ?? '') ?></td>
                            <td><?= htmlspecialchars($badge['statut'] ?? '') ?></td>
                            <td><?= htmlspecialchars($badge['acces_batiments'] ?? '—') ?></td>
                            <td>
                                <a href="inventaire.php?onglet=badges&gerer_badge=<?= $badge['id_badge'] ?>" class="btn btn-secondary">Gérer</a>
                                <?php if ($badge['statut'] === 'Perdu') : ?>
                                    <form method="POST" action="inventaire.php?onglet=badges"
                                        style="display:inline-block;"
                                        onsubmit="return confirm('Marquer ce badge comme retrouvé ?');">
                                        <input type="hidden" name="badge_retrouve" value="1">
                                        <input type="hidden" name="id_badge_retrouve" value="<?= (int)$badge['id_badge'] ?>">
                                        <button type="submit" class="btn btn-success">Retrouvé</button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Panneau de gestion d'un badge -->
    <?php if ($gerer_badge > 0 && $info_badge_geree) : ?>
    <div class="card">
        <h2>Gérer : <?= htmlspecialchars($info_badge_geree['identifiant_interne']) ?></h2>
        <!-- Modifier les infos -->
        <h3>Modifier les informations</h3>
        <form method="POST" action="inventaire.php?onglet=badges&gerer_badge=<?= $gerer_badge ?>">
            <input type="hidden" name="modifier_badge_info" value="1">
            <input type="hidden" name="id_badge" value="<?= $gerer_badge ?>">
            <label>Identifiant interne</label>
            <input type="text" name="nouveau_identifiant" value="<?= htmlspecialchars($info_badge_geree['identifiant_interne']) ?>" required>
            <button type="submit" class="btn">Enregistrer</button>
        </form>

        <!-- Accès actuels -->
        <h3 style="margin-top:14px;">Accès bâtiments / portes</h3>
        <?php if (empty($acces_badge_geree)) : ?>
            <p>Aucun accès enregistré.</p>
        <?php else : ?>
            <table>
                <thead><tr><th>Bâtiment</th><th>Porte</th><th>Action</th></tr></thead>
                <tbody>
                    <?php foreach ($acces_badge_geree as $acces) : ?>
                        <tr>
                            <td><?= htmlspecialchars($acces['nom_batiment']) ?></td>
                            <td><?= htmlspecialchars($acces['nom_porte'] ?? '—') ?></td>
                            <td>
                                <form method="POST" action="inventaire.php?onglet=badges&gerer_badge=<?= $gerer_badge ?>"
                                    onsubmit="return confirm('Supprimer cet accès ?');">
                                    <input type="hidden" name="supprimer_acces" value="1">
                                    <input type="hidden" name="id_element_acces" value="<?= $acces['id_element_acces'] ?>">
                                    <button type="submit" class="btn btn-danger">Supprimer</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>

        <!-- Ajouter un accès -->
        <h3 style="margin-top:14px;">Ajouter un accès</h3>
        <form method="POST" action="inventaire.php?onglet=badges&gerer_badge=<?= $gerer_badge ?>">
            <input type="hidden" name="ajouter_acces_existant" value="1">
            <input type="hidden" name="type_element_acces" value="badge">
            <input type="hidden" name="id_element_acces_existant" value="<?= $gerer_badge ?>">
            <label>Bâtiment</label>
            <select name="id_batiment_acces" id="bat-select-badge" onchange="mettreAJourPortes(this, 'porte-select-badge')" required>
                <option value="">-- Choisir --</option>
                <?php foreach ($batiments as $bat) : ?>
                    <option value="<?= $bat['id_batiment'] ?>"><?= htmlspecialchars($bat['nom_batiment']) ?></option>
                <?php endforeach; ?>
            </select>
            <label>Porte</label>
            <select name="id_porte_acces" id="porte-select-badge" required>
                <option value="">-- Choisir d'abord un bâtiment --</option>
            </select>
            <button type="submit" class="btn" style="margin-top:8px;">Ajouter</button>
        </form>

        <div style="margin-top:12px;">
            <a href="inventaire.php?onglet=badges" class="btn btn-secondary">Fermer</a>
        </div>
    </div>
    <?php endif; ?>
    </div>

<!-- Onglet bâtiments -->
<div id="tab-batiments" class="tab-content" <?= $onglet_actif !== 'batiments' ? 'style="display:none;"' : '' ?>>

    <!-- Formulaire ajout bâtiment -->
    <div class="card">
        <h2>Ajouter un bâtiment</h2>
        <form method="POST" action="inventaire.php?onglet=batiments">
            <input type="hidden" name="ajouter_batiment" value="1">
            <label>Nom du bâtiment *</label>
            <input type="text" name="nom_batiment" placeholder="Ex : Salle Aragon, Mairie..." required>
            <label>Adresse</label>
            <input type="text" name="adresse" placeholder="Ex : 1 rue de la Paix Maing">
            <label>Commentaire</label>
            <textarea name="commentaire"></textarea>
            <button type="submit" class="btn">Ajouter le bâtiment</button>
        </form>
    </div>

    <!-- Formulaire ajout porte -->
    <div class="card">
        <h2>Ajouter une porte</h2>
        <form method="POST" action="inventaire.php?onglet=batiments">
            <input type="hidden" name="ajouter_porte" value="1">
            <label>Bâtiment *</label>
            <select name="id_batiment_porte" required>
                <option value="">-- Choisir un bâtiment --</option>
                <?php foreach ($batiments as $bat) : ?>
                    <option value="<?= $bat['id_batiment'] ?>"><?= htmlspecialchars($bat['nom_batiment']) ?></option>
                <?php endforeach; ?>
            </select>
            <label>Nom de la porte *</label>
            <input type="text" name="nom_porte" placeholder="Ex : Porte 36, Entrée principale..." required>
            <button type="submit" class="btn">Ajouter la porte</button>
        </form>
    </div>

    <!-- Liste bâtiments avec leurs portes -->
    <div class="card">
        <h2>Liste des bâtiments et portes</h2>
        <?php if (empty($batiments)) : ?>
            <p>Aucun bâtiment enregistré.</p>
        <?php else : ?>
            <?php foreach ($batiments as $batiment) : ?>
                <div style="margin-bottom:16px; border:0.5px solid #e5e7eb; border-radius:6px; padding:12px;">
                    <strong><?= htmlspecialchars($batiment['nom_batiment']) ?></strong>
                    <?php if (!empty($batiment['adresse'])) : ?>
                        — <span style="color:#64748b;"><?= htmlspecialchars($batiment['adresse']) ?></span>
                    <?php endif; ?>
                    <?php if (!empty($batiment['commentaire'])) : ?>
                        <br><small><?= htmlspecialchars($batiment['commentaire']) ?></small>
                    <?php endif; ?>

                    <!-- Liste des portes -->
                    <div style="margin-top:10px;">
                        <?php $portes_bat = $portes_par_batiment_html[$batiment['id_batiment']] ?? []; ?>
                        <?php if (empty($portes_bat)) : ?>
                            <p style="color:#64748b; font-size:13px;">Aucune porte enregistrée.</p>
                        <?php else : ?>
                            <?php foreach ($portes_bat as $porte) : ?>
                                <form method="POST" action="inventaire.php?onglet=batiments"
                                    style="display:flex; gap:8px; align-items:center; margin-bottom:6px;">
                                    <input type="hidden" name="modifier_porte" value="1">
                                    <input type="hidden" name="id_porte" value="<?= $porte['id_porte'] ?>">
                                    <input type="text" name="nouveau_nom_porte"
                                        value="<?= htmlspecialchars($porte['nom_porte']) ?>"
                                        style="flex:1; margin:0;">
                                    <button type="submit" class="btn btn-secondary" style="white-space:nowrap;">Modifier</button>
                                </form>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Données portes pour le JS -->
<script>
const portesBatiment = <?= json_encode($portes_par_batiment, JSON_UNESCAPED_UNICODE) ?>;
const batiments = <?= json_encode(array_map(fn($b) => ['id' => $b['id_batiment'], 'nom' => $b['nom_batiment']], $batiments), JSON_UNESCAPED_UNICODE) ?>;
</script>
<script src="assets/js/inventaire.js"></script>

<?php include 'includes/footer.php'; ?>
