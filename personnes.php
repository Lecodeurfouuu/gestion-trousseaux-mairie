<?php
require_once 'config/database.php';
require_once 'includes/fonctions.php';

$page_title = "Personnes";
include 'includes/header.php';

$message = "";

// Ajout d'une personne
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
            $requeteDoublonPersonne = $pdo->prepare("
                SELECT COUNT(*) FROM personnes
                WHERE LOWER(nom) = LOWER(:nom)
                AND LOWER(prenom) = LOWER(:prenom)
            ");
            $requeteDoublonPersonne->execute([':nom' => $nom, ':prenom' => $prenom]);
            $existeDeja = $requeteDoublonPersonne->fetchColumn();

            if ($existeDeja > 0) {
                $message = 'Attention cette personne existe déjà.';
            } else {
                $requeteAjoutPersonne = $pdo->prepare("
                    INSERT INTO personnes (nom, prenom, service, groupe_personnel, telephone, mail)
                    VALUES (:nom, :prenom, :service, :groupe_personnel, :telephone, :mail)
                ");
                $requeteAjoutPersonne->execute([
                    ':nom'              => $nom,
                    ':prenom'           => $prenom,
                    ':service'          => $service,
                    ':groupe_personnel' => $groupe_personnel,
                    ':telephone'        => $telephone !== '' ? $telephone : null,
                    ':mail'             => $mail !== '' ? $mail : null,
                ]);
                $message = 'Personne ajoutée avec succès.';
            }
        } catch (PDOException $e) {
            $message = "Erreur SQL : " . $e->getMessage();
        }
    }
}

// Récupération des personnes avec leur trousseau actif éventuel
try {
    $requeteListePersonnes = $pdo->query("
        SELECT
            p.*,
            t.id_trousseau,
            t.numero_trousseau,
            t.statut AS statut_trousseau
        FROM personnes p
        LEFT JOIN historique_trousseaux h
            ON p.id_personne = h.id_personne
            AND h.date_restitution IS NULL
        LEFT JOIN trousseaux t
            ON h.id_trousseau = t.id_trousseau
            AND t.statut = 'Attribué'
        ORDER BY p.nom, p.prenom
    ");
    $personnes = $requeteListePersonnes->fetchAll(PDO::FETCH_ASSOC);

    // Séparer avec / sans trousseau actif
    $personnes_avec    = array_filter($personnes, fn($p) => !empty($p['id_trousseau']));
    $personnes_sans    = array_filter($personnes, fn($p) => empty($p['id_trousseau']));

} catch (PDOException $e) {
    die("Erreur SQL : " . $e->getMessage());
}
?>

<h1>Gestion des personnes</h1>

<?php afficherMessage($message); ?>

<div class="card">
    <h2>Ajouter une personne</h2>
    <form method="POST" action="personnes.php">
        <label>Nom *</label>
        <input type="text" name="nom" required>
        <label>Prénom *</label>
        <input type="text" name="prenom" required>
        <label>Service *</label>
        <input type="text" name="service" placeholder="Ex : Mairie, Association, Education..." required>
        <label>Groupe personnel *</label>
        <input type="text" name="groupe_personnel" placeholder="Ex : Maire, Élu, Agent, Bénévole, Autre..." required>
        <label>Téléphone</label>
        <input type="text" name="telephone" placeholder="Ex : 06 58 00 00 00">
        <label>Mail</label>
        <input type="email" name="mail" placeholder="exemple@mail.fr">
        <button type="submit" class="btn">Ajouter la personne</button>
    </form>
</div>

<!-- Personnes avec trousseau actif -->
<div class="card">
    <h2>Personnes avec trousseau actif (<?= count($personnes_avec) ?>)</h2>
    <?php if (empty($personnes_avec)) : ?>
        <p>Aucune personne avec un trousseau actuellement attribué.</p>
    <?php else : ?>
        <table>
            <thead>
                <tr>
                    <th>Nom</th>
                    <th>Prénom</th>
                    <th>Service</th>
                    <th>Groupe</th>
                    <th>Trousseau</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($personnes_avec as $personne) : ?>
                    <tr>
                        <td><?= htmlspecialchars($personne['nom']) ?></td>
                        <td><?= htmlspecialchars($personne['prenom']) ?></td>
                        <td><?= htmlspecialchars($personne['service']) ?></td>
                        <td><?= htmlspecialchars($personne['groupe_personnel']) ?></td>
                        <td>
                            <a href="fiche_trousseau.php?id=<?= (int)$personne['id_trousseau'] ?>" class="btn">
                                <?= htmlspecialchars($personne['numero_trousseau']) ?>
                            </a>
                        </td>
                        <td>
                            <a href="modifier_personne.php?id=<?= urlencode($personne['id_personne']) ?>" class="btn btn-secondary">
                                Modifier
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<!-- Personnes sans trousseau actif -->
<div class="card">
    <h2>Personnes sans trousseau actif (<?= count($personnes_sans) ?>)</h2>
    <?php if (empty($personnes_sans)) : ?>
        <p>Toutes les personnes ont un trousseau attribué.</p>
    <?php else : ?>
        <table>
            <thead>
                <tr>
                    <th>Nom</th>
                    <th>Prénom</th>
                    <th>Service</th>
                    <th>Groupe</th>
                    <th>Téléphone</th>
                    <th>Mail</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($personnes_sans as $personne) : ?>
                    <tr>
                        <td><?= htmlspecialchars($personne['nom']) ?></td>
                        <td><?= htmlspecialchars($personne['prenom']) ?></td>
                        <td><?= htmlspecialchars($personne['service']) ?></td>
                        <td><?= htmlspecialchars($personne['groupe_personnel']) ?></td>
                        <td><?= htmlspecialchars($personne['telephone'] ?? '') ?></td>
                        <td><?= htmlspecialchars($personne['mail'] ?? '') ?></td>
                        <td>
                            <a href="modifier_personne.php?id=<?= urlencode($personne['id_personne']) ?>" class="btn btn-secondary">
                                Modifier
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>