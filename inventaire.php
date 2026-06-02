<?php 
require_once 'config/database.php';
$page_title = 'Inventaire';
include 'includes/header.php';

$message = '';

// Onglet actif
// Marquer un badge comme retrouvé
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['badge_retrouve'])) {
    $id_badge_retrouve = (int)($_POST['id_badge_retrouve'] ?? 0);
    if ($id_badge_retrouve > 0) {
        try {
            $requeteBadgeRetrouve = $pdo->prepare("
                UPDATE badges SET statut = 'Disponible'
                WHERE id_badge = :id_badge AND statut = 'Perdu'
            ");
            $requeteBadgeRetrouve->execute([':id_badge' => $id_badge_retrouve]);
            $message = "Badge marqué comme retrouvé. Statut repassé à Disponible.";
        } catch (PDOException $e) {
            $message = "Erreur : " . $e->getMessage();
        }
    }
}

$onglet_actif = $_GET['onglet'] ?? 'cles';

// Ajout d'une référence de clé + ses accès bâtiments

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajouter_reference'])) {
    $reference_cle   = trim($_POST['reference_cle'] ?? '');
    $commentaire     = trim($_POST['commentaire'] ?? '');
    $acces_batiments = $_POST['acces_batiment'] ?? [];
    $acces_portes    = $_POST['acces_porte'] ?? [];

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
                    ':commentaire'   => $commentaire !== '' ? $commentaire : null
                ]);
                $id_nouvelle_reference = $pdo->lastInsertId();

                // Insertion des accès bâtiments (lignes non vides uniquement)
                foreach ($acces_batiments as $index => $id_batiment) {
                    if (!empty($id_batiment)) {
                        $porte = trim($acces_portes[$index] ?? '');
                        $requeteAjoutAccesCle = $pdo->prepare("
                            INSERT INTO element_acces
                            (type_element, id_reference_cle, id_batiment, porte_commentaire)
                            VALUES ('cle', :id_reference_cle, :id_batiment, :porte_commentaire)
                        ");
                        $requeteAjoutAccesCle->execute([
                            ':id_reference_cle'  => $id_nouvelle_reference,
                            ':id_batiment'       => $id_batiment,
                            ':porte_commentaire' => $porte !== '' ? $porte : null
                        ]);
                    }
                }
                $message = 'Référence de clé ajoutée avec succès.';
            }
        } catch (PDOException $e) {
            $message = 'Erreur lors de l\'ajout : ' . $e->getMessage();
        }
    }
}

// Ajout d'un badge + ses accès bâtiments

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajouter_badge'])) {
    $identifiant_interne  = trim($_POST['identifiant_interne'] ?? '');
    $identifiant_officiel = trim($_POST['identifiant_officiel'] ?? '');
    $type_badge           = trim($_POST['type_badge'] ?? '');
    $acces_batiments      = $_POST['acces_batiment'] ?? [];
    $acces_portes         = $_POST['acces_porte'] ?? [];

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
                $message = 'Ce badge existe déjà (identifiant interne déjà utilisé).';
            } else {
                $requeteAjoutBadge = $pdo->prepare('
                    INSERT INTO badges (identifiant_interne, identifiant_officiel, type_badge, statut)
                    VALUES (:identifiant_interne, :identifiant_officiel, :type_badge, :statut)
                ');
                $requeteAjoutBadge->execute([
                    ':identifiant_interne'  => $identifiant_interne,
                    ':identifiant_officiel' => $identifiant_officiel !== '' ? $identifiant_officiel : null,
                    ':type_badge'           => $type_badge,
                    ':statut'               => 'Disponible'
                ]);
                $id_nouveau_badge = $pdo->lastInsertId();

                // Insertion des accès bâtiments (lignes non vides uniquement)
                foreach ($acces_batiments as $index => $id_batiment) {
                    if (!empty($id_batiment)) {
                        $porte = trim($acces_portes[$index] ?? '');
                        $requeteAjoutAccesBadge = $pdo->prepare("
                            INSERT INTO element_acces
                            (type_element, id_badge, id_batiment, porte_commentaire)
                            VALUES ('badge', :id_badge, :id_batiment, :porte_commentaire)
                        ");
                        $requeteAjoutAccesBadge->execute([
                            ':id_badge'          => $id_nouveau_badge,
                            ':id_batiment'       => $id_batiment,
                            ':porte_commentaire' => $porte !== '' ? $porte : null
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
    $adresse      = trim($_POST['adresse'] ?? '');
    $commentaire  = trim($_POST['commentaire'] ?? '');

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
                    ':adresse'      => $adresse !== '' ? $adresse : null,
                    ':commentaire'  => $commentaire !== '' ? $commentaire : null
                ]);
                $message = 'Bâtiment ajouté avec succès.';
            }
        } catch (PDOException $e) {
            $message = "Erreur lors de l'ajout du bâtiment : " . $e->getMessage();
        }
    }
}


// Récupération des données

try {
    // Références clés avec accès
    $references = $pdo->query("
        SELECT
            rc.id_reference_cle,
            rc.reference_cle,
            rc.commentaire,
            GROUP_CONCAT(
                bat.nom_batiment,
                CASE WHEN ea.porte_commentaire IS NOT NULL
                     THEN CONCAT(' — ', ea.porte_commentaire)
                     ELSE '' END
                ORDER BY bat.nom_batiment
                SEPARATOR ' | '
            ) AS acces_batiments
        FROM references_cles rc
        LEFT JOIN element_acces ea ON rc.id_reference_cle = ea.id_reference_cle
        LEFT JOIN batiments bat ON ea.id_batiment = bat.id_batiment
        GROUP BY rc.id_reference_cle, rc.reference_cle, rc.commentaire
        ORDER BY rc.reference_cle
    ")->fetchAll(PDO::FETCH_ASSOC);

    // Badges avec accès
    $badges = $pdo->query("
        SELECT
            b.id_badge,
            b.identifiant_interne,
            b.identifiant_officiel,
            b.type_badge,
            b.statut,
            GROUP_CONCAT(
                bat.nom_batiment,
                CASE WHEN ea.porte_commentaire IS NOT NULL
                     THEN CONCAT(' — ', ea.porte_commentaire)
                     ELSE '' END
                ORDER BY bat.nom_batiment
                SEPARATOR ' | '
            ) AS acces_batiments
        FROM badges b
        LEFT JOIN element_acces ea ON b.id_badge = ea.id_badge
        LEFT JOIN batiments bat ON ea.id_batiment = bat.id_batiment
        GROUP BY b.id_badge, b.identifiant_interne, b.identifiant_officiel, b.type_badge, b.statut
        ORDER BY b.identifiant_interne
    ")->fetchAll(PDO::FETCH_ASSOC);

    // Bâtiments
    $batiments = $pdo->query("
        SELECT id_batiment, nom_batiment, adresse, commentaire
        FROM batiments
        ORDER BY nom_batiment
    ")->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("<p>Erreur SQL : " . $e->getMessage());
}

// Nombre de lignes bâtiment affichées dans les formulaires
$nb_lignes_acces = 3;
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
            <label>Bâtiments accessibles</label>
            <small>Laissez vide les lignes non utilisées.</small>
            <table style="margin-top:8px; margin-bottom:12px;">
                <thead><tr><th>Bâtiment</th><th>Porte / commentaire</th></tr></thead>
                <tbody>
                    <?php for ($i = 0; $i < $nb_lignes_acces; $i++) : ?>
                        <tr>
                            <td>
                                <select name="acces_batiment[]">
                                    <option value="">-- Aucun --</option>
                                    <?php foreach ($batiments as $bat) : ?>
                                        <option value="<?= $bat['id_batiment'] ?>"><?= htmlspecialchars($bat['nom_batiment']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                            <td><input type="text" name="acces_porte[]" placeholder="Ex : Porte bureau urbanisme"></td>
                        </tr>
                    <?php endfor; ?>
                </tbody>
            </table>
            <button type="submit" class="btn">Ajouter la référence</button>
        </form>
    </div>

    <div class="card">
        <h2>Liste des références de clés</h2>
        <table>
            <thead><tr><th>Référence</th><th>Bâtiments / Portes</th><th>Commentaire</th></tr></thead>
            <tbody>
                <?php if (empty($references)) : ?>
                    <tr><td colspan="3">Aucune référence de clé enregistrée.</td></tr>
                <?php else : ?>
                    <?php foreach ($references as $ref) : ?>
                        <tr>
                            <td><?= htmlspecialchars($ref['reference_cle']) ?></td>
                            <td><?= htmlspecialchars($ref['acces_batiments'] ?? '—') ?></td>
                            <td><?= htmlspecialchars($ref['commentaire'] ?? '') ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Onglet badges -->
<div id="tab-badges" class="tab-content" <?= $onglet_actif !== 'badges' ? 'style="display:none;"' : '' ?>>

    <div class="card">
        <h2>Ajouter un badge</h2>
        <form method="POST" action="inventaire.php?onglet=badges">
            <input type="hidden" name="ajouter_badge" value="1">
            <label>Type de badge *</label>
            <select name="type_badge" id="type_badge" required>
                <option value="">-- Choisir --</option>
                <option value="Ela">Ela (Noir)</option>
                <option value="Salto">Salto (Bleu)</option>
            </select>
            <label>Identifiant interne *</label>
            <input type="text" name="identifiant_interne" id="identifiant_interne" placeholder="Choisir d'abord le type" required>
            <label>Identifiant officiel</label>
            <input type="text" name="identifiant_officiel" placeholder="Ex : 6489 ou laisser vide">
            <small>Uniquement pour les badges Ela (Noir). Laisser vide pour les Salto.</small>

            <p><label>Bâtiments accessibles</label></p>
            
            <small>Laissez vide les lignes non utilisées.</small>
            <table style="margin-top:8px; margin-bottom:12px;">
                <thead><tr><th>Bâtiment</th><th>Porte / commentaire</th></tr></thead>
                <tbody>
                    <?php for ($i = 0; $i < $nb_lignes_acces; $i++) : ?>
                        <tr>
                            <td>
                                <select name="acces_batiment[]">
                                    <option value="">-- Aucun --</option>
                                    <?php foreach ($batiments as $bat) : ?>
                                        <option value="<?= $bat['id_batiment'] ?>"><?= htmlspecialchars($bat['nom_batiment']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                            <td><input type="text" name="acces_porte[]" placeholder="Ex : Parking Nord"></td>
                        </tr>
                    <?php endfor; ?>
                </tbody>
            </table>
            <button type="submit" class="btn">Ajouter le badge</button>
        </form>
    </div>

    <div class="card">
        <h2>Liste des badges</h2>
        <table>
            <thead><tr><th>Identifiant interne</th><th>Identifiant officiel</th><th>Type</th><th>Statut</th><th>Bâtiments / Portes</th></tr></thead>
            <tbody>
                <?php if (empty($badges)) : ?>
                    <tr><td colspan="5">Aucun badge enregistré.</td></tr>
                <?php else : ?>
                    <?php foreach ($badges as $badge) : ?>
                        <tr>
                            <td><?= htmlspecialchars($badge['identifiant_interne']) ?></td>
                            <td><?= htmlspecialchars($badge['identifiant_officiel'] ?? '—') ?></td>
                            <td><?= htmlspecialchars($badge['type_badge'] ?? '') ?></td>
                            <td><?= htmlspecialchars($badge['statut'] ?? '') ?></td>
                            <td><?= htmlspecialchars($badge['acces_batiments'] ?? '—') ?></td>
                            <td>
                                <?php if ($badge['statut'] === 'Perdu') : ?>
                                    <form method="POST" action="inventaire.php?onglet=badges"
                                        style="display:inline-block;"
                                        onsubmit="return confirm('Marquer ce badge comme retrouvé ?');">
                                        <input type="hidden" name="badge_retrouve" value="1">
                                        <input type="hidden" name="id_badge_retrouve" value="<?= (int)$badge['id_badge'] ?>">
                                        <button type="submit" class="btn btn-success">Retrouvé</button>
                                    </form>
                                <?php else : ?>
                                    —
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Onglet bâtiments -->
<div id="tab-batiments" class="tab-content" <?= $onglet_actif !== 'batiments' ? 'style="display:none;"' : '' ?>>

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

    <div class="card">
        <h2>Liste des bâtiments</h2>
        <table>
            <thead><tr><th>Nom</th><th>Adresse</th><th>Commentaire</th></tr></thead>
            <tbody>
                <?php if (empty($batiments)) : ?>
                    <tr><td colspan="3">Aucun bâtiment enregistré.</td></tr>
                <?php else : ?>
                    <?php foreach ($batiments as $batiment) : ?>
                        <tr>
                            <td><?= htmlspecialchars($batiment['nom_batiment']) ?></td>
                            <td><?= htmlspecialchars($batiment['adresse'] ?? '') ?></td>
                            <td><?= htmlspecialchars($batiment['commentaire'] ?? '') ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script src="assets/js/inventaire.js"></script>

<?php include 'includes/footer.php'; ?>
