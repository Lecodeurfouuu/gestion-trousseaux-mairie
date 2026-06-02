<?php
require_once 'config/database.php';
require_once 'includes/fonctions.php';

$page_title = 'Trousseaux';
include 'includes/header.php';

$message = '';

// Ajout d'un trousseau
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $numero_trousseau = trim($_POST['numero_trousseau'] ?? '');
  $statut = trim($_POST['statut'] ?? 'Disponible');
  $commentaire = trim($_POST['commentaire'] ?? '');

  if ($numero_trousseau === '') {
    $message = "Veuillez remplir le champ numéro de trousseau.";
  } else {
    try {
      // Vérification des doublons
      $requeteDoublonTrousseau = $pdo->prepare("
        SELECT COUNT(*)
        FROM trousseaux
        WHERE LOWER(numero_trousseau) = LOWER(:numero_trousseau)
      ");

      $requeteDoublonTrousseau->execute([
        ':numero_trousseau' => $numero_trousseau
      ]);

      $existeDeja = $requeteDoublonTrousseau->fetchColumn();

      if ($existeDeja > 0) {
        $message = "Attention : ce numéro de trousseau existe déjà.";
      } else {
        $requeteAjoutTrousseau = $pdo->prepare("
          INSERT INTO trousseaux
          (numero_trousseau, statut, commentaire)
          VALUES
          (:numero_trousseau, :statut, :commentaire)
        ");

        $requeteAjoutTrousseau->execute([
          ':numero_trousseau' => $numero_trousseau,
          ':statut' => $statut,
          ':commentaire' => $commentaire !== '' ? $commentaire : null
        ]);

        $message = 'Trousseau ajouté avec succès.';
      }
    } catch (PDOException $e) {
      $message = "Erreur lors de l'ajout : " . $e->getMessage();
    }
  }
}

// Récupération des trousseaux avec détenteur actuel si attribué
try {
  $requeteListeTrousseaux = $pdo->query("
    SELECT
      t.id_trousseau,
      t.numero_trousseau,
      t.statut,
      t.commentaire,
      p.nom,
      p.prenom,
      h.date_remise,
      h.decharge_signee
    FROM trousseaux t
    LEFT JOIN historique_trousseaux h
      ON t.id_trousseau = h.id_trousseau
      AND h.date_restitution IS NULL
    LEFT JOIN personnes p
      ON h.id_personne = p.id_personne
    ORDER BY t.numero_trousseau
  ");

  $trousseaux = $requeteListeTrousseaux->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
  die("Erreur SQL : " . $e->getMessage());
}
?>

  <h1>Gestion des trousseaux</h1>

 <?php afficherMessage($message); ?>

  <div class="card">
    <h2>Ajouter un trousseau</h2>

    <form method="POST" action="trousseaux.php">
      <label>Numéro de trousseau *</label>
      <input type="text" name="numero_trousseau" placeholder="Ex : TR-001" required>

      <label>Statut *</label>
      <select name="statut" required>
        <option value="Disponible" selected>Disponible</option>
      </select>

      <label>Commentaire</label>
      <textarea name="commentaire" placeholder="Commentaire éventuel sur le trousseau"></textarea>

      <button type="submit" class="btn">Ajouter le trousseau</button>
    </form>
  </div>

  <div class="card">
    <h2>Liste des trousseaux</h2>

    <table>
      <thead>
        <tr>
          <th>ID</th>
          <th>N° trousseau</th>
          <th>Statut</th>
          <th>Détenteur actuel</th>
          <th>Date remise</th>
          <th>Décharge</th>
          <th>Commentaire</th>
          <th>Action</th>
        </tr>
      </thead>

      <tbody>
        <?php if (empty($trousseaux)) : ?>
          <tr>
            <td colspan="8">Aucun trousseau enregistré.</td>
          </tr>
        <?php else : ?>
          <?php foreach ($trousseaux as $trousseau) : ?>
            <tr>
              <td><?= htmlspecialchars($trousseau['id_trousseau']) ?></td>
              <td><?= htmlspecialchars($trousseau['numero_trousseau']) ?></td>
              <td><?= htmlspecialchars($trousseau['statut']) ?></td>

              <td>
                <?php if (!empty($trousseau['nom'])) : ?>
                  <?= htmlspecialchars($trousseau['prenom'] . ' ' . $trousseau['nom']) ?>
                <?php else : ?>
                  Aucun
                <?php endif; ?>
              </td>

              <td><?= formaterDate($trousseau['date_remise']) ?></td>

              <td><?= afficherDecharge($trousseau['decharge_signee']) ?></td>

              <td><?= htmlspecialchars($trousseau['commentaire'] ?? '') ?></td>

              <td>
                <a
                  href="fiche_trousseau.php?id=<?= urlencode($trousseau['id_trousseau']) ?>"class="btn btn-secondary"> Voir détails
                </a>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <?php include 'includes/footer.php'; ?>