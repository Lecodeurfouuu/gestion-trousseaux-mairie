<?php
require_once 'config/database.php';
require_once 'includes/fonctions.php';

$page_title = "Modifier une personne";
include 'includes/header.php';

$id_personne = $_GET['id'] ?? null;
$message = "";

if ($id_personne === null || !is_numeric($id_personne)) {
    echo "<h1>Modifier une personne</h1>";
    echo "<div class='card'>";
    echo "<p>Aucune personne sélectionnée.</p>";
    echo "<a href='personnes.php' class='btn'>Retour aux personnes</a>";
    echo "</div>";
    include 'includes/footer.php';
    exit;
}

// Mise à jour de la personne
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom              = trim($_POST['nom'] ?? '');
    $prenom           = trim($_POST['prenom'] ?? '');
    $service          = trim($_POST['service'] ?? '');
    $groupe_personnel = trim($_POST['groupe_personnel'] ?? '');
    $telephone        = trim($_POST['telephone'] ?? '');
    $mail             = trim($_POST['mail'] ?? '');

    if ($nom === '' || $prenom === '' || $service === '' || $groupe_personnel === '') {
        $message = "Veuillez remplir les champs obligatoires.";
    } else {
        try {
            $requeteModificationPersonne = $pdo->prepare("
                UPDATE personnes
                SET nom = :nom,
                    prenom = :prenom,
                    service = :service,
                    groupe_personnel = :groupe_personnel,
                    telephone = :telephone,
                    mail = :mail
                WHERE id_personne = :id_personne
            ");

            $requeteModificationPersonne->execute([
                ':nom'              => $nom,
                ':prenom'           => $prenom,
                ':service'          => $service,
                ':groupe_personnel' => $groupe_personnel,
                ':telephone'        => $telephone !== '' ? $telephone : null,
                ':mail'             => $mail !== '' ? $mail : null,
                ':id_personne'      => $id_personne
            ]);

            $message = "Informations de la personne mises à jour avec succès.";

        } catch (PDOException $e) {
            $message = "Erreur lors de la modification : " . $e->getMessage();
        }
    }
}

// Récupération des informations actuelles
try {
    $requetePersonne = $pdo->prepare("
        SELECT * FROM personnes WHERE id_personne = :id_personne
    ");
    $requetePersonne->execute([':id_personne' => $id_personne]);
    $personne = $requetePersonne->fetch(PDO::FETCH_ASSOC);

    if (!$personne) {
        echo "<h1>Modifier une personne</h1>";
        echo "<div class='card'>";
        echo "<p>Personne introuvable.</p>";
        echo "<a href='personnes.php' class='btn'>Retour aux personnes</a>";
        echo "</div>";
        include 'includes/footer.php';
        exit;
    }

    // Trousseau actuellement attribué
    $requeteTrousseauActif = $pdo->prepare("
        SELECT t.id_trousseau, t.numero_trousseau, t.statut,
               h.date_remise, h.decharge_signee
        FROM historique_trousseaux h
        JOIN trousseaux t ON h.id_trousseau = t.id_trousseau
        WHERE h.id_personne = :id_personne
        AND h.date_restitution IS NULL
        ORDER BY h.date_remise DESC
    ");
    $requeteTrousseauActif->execute([':id_personne' => $id_personne]);
    $trousseaux_actifs = $requeteTrousseauActif->fetchAll(PDO::FETCH_ASSOC);

    // Anciens trousseaux restitués
    $requeteAncienssTrousseaux = $pdo->prepare("
        SELECT t.id_trousseau, t.numero_trousseau, t.statut,
               h.date_remise, h.date_restitution, h.decharge_signee, h.commentaire
        FROM historique_trousseaux h
        JOIN trousseaux t ON h.id_trousseau = t.id_trousseau
        WHERE h.id_personne = :id_personne
        AND h.date_restitution IS NOT NULL
        ORDER BY h.date_restitution DESC
    ");
    $requeteAncienssTrousseaux->execute([':id_personne' => $id_personne]);
    $anciens_trousseaux = $requeteAncienssTrousseaux->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Erreur SQL : " . $e->getMessage());
}
?>

<h1>Modifier une personne</h1>

<?php afficherMessage($message); ?>

<div class="card">
    <h2>Informations de la personne</h2>

    <form method="POST" action="modifier_personne.php?id=<?= urlencode($id_personne) ?>">
        <label>Nom *</label>
        <input type="text" name="nom" value="<?= htmlspecialchars($personne['nom']) ?>" required>

        <label>Prénom *</label>
        <input type="text" name="prenom" value="<?= htmlspecialchars($personne['prenom']) ?>" required>

        <label>Service *</label>
        <input type="text" name="service" value="<?= htmlspecialchars($personne['service']) ?>" required>

        <label>Groupe personnel *</label>
        <input type="text" name="groupe_personnel" value="<?= htmlspecialchars($personne['groupe_personnel']) ?>" required>

        <label>Téléphone</label>
        <input type="text" name="telephone" value="<?= htmlspecialchars($personne['telephone'] ?? '') ?>">

        <label>Mail</label>
        <input type="email" name="mail" value="<?= htmlspecialchars($personne['mail'] ?? '') ?>">

        <button type="submit" class="btn">Enregistrer les modifications</button>
        <a href="personnes.php" class="btn btn-secondary">Retour</a>
    </form>
</div>

<!-- Trousseau actuellement attribué(s) -->
<div class="card">
    <h2>Trousseau en cours</h2>
    <?php if (empty($trousseaux_actifs)) : ?>
        <p>Aucun trousseau actuellement attribué.</p>
    <?php else : ?>
        <table>
            <thead>
                <tr>
                    <th>Numéro</th>
                    <th>Statut</th>
                    <th>Date de remise</th>
                    <th>Décharge signée</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($trousseaux_actifs as $t) : ?>
                    <tr>
                        <td><?= htmlspecialchars($t['numero_trousseau']) ?></td>
                        <td><?= htmlspecialchars($t['statut']) ?></td>
                        <td><?= formaterDate($t['date_remise']) ?></td>
                        <td><?= afficherDecharge($t['decharge_signee']) ?></td>
                        <td>
                            <a href="fiche_trousseau.php?id=<?= (int)$t['id_trousseau'] ?>" class="btn">
                                Voir la fiche
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<!-- Anciens trousseaux -->
<div class="card">
    <h2>Historique des trousseaux</h2>
    <?php if (empty($anciens_trousseaux)) : ?>
        <p>Aucun ancien trousseau pour cette personne.</p>
    <?php else : ?>
        <table>
            <thead>
                <tr>
                    <th>Numéro</th>
                    <th>Date de remise</th>
                    <th>Date de restitution</th>
                    <th>Décharge signée</th>
                    <th>Commentaire</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($anciens_trousseaux as $t) : ?>
                    <tr>
                        <td><?= htmlspecialchars($t['numero_trousseau']) ?></td>
                        <td><?= formaterDate($t['date_remise']) ?></td>
                        <td><?= formaterDate($t['date_restitution']) ?></td>
                        <td><?= afficherDecharge($t['decharge_signee']) ?></td>
                        <td><?= htmlspecialchars($t['commentaire'] ?? '-') ?></td>
                        <td>
                            <a href="fiche_trousseau.php?id=<?= (int)$t['id_trousseau'] ?>" class="btn btn-secondary">
                                Voir la fiche
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>