README

___

Application web de gestion des trousseaux de clés et badges pour une mairie.
Développée dans le cadre d'un stage, elle permet de suivre qui détient quoi, quels accès sont associés, et de conserver un historique complet des mouvements.
___

## Prérequis
Pour l'utilisation de l'application il faut :
-Wamp avec apache et my SQL actifs
-PHP version a jour
-MySQL version a jour
-Navigateur Web
___

## Installation
1.Copier les fichiers
Placer le dossier 'stage' dans 'C\wamp64\www\stage\'
Vous êtes libre de changer le nom du fichier 'stage'

2.Créer la base de données
Ouvrir phpAdmin -> 'http://localhost/phpmyadmin'
Créer une base de données nommée `stage\_db` ou le nom que vous voulez mais pensez alors a modifer les informations de $dbname dans 'config/database.php'
Importer le script de création : `sql/create\_tables.sql`
Importer les données de test (optionnel) : `sql/donnees\_test.sql`

3.Configurer la connexion
Ouvrir `config/database.php` et vérifier les paramètres :
php
$host     = 'localhost';
$dbname   = 'stage_db'; *à modifer si vous avez nommer votre base de donnée diffférement*
$username = 'root';
$password = '';

4.Lancer l'application
Ouvrir le navigateur et accéder à http://localhost/stage/
___

## Structure des fichiers

stage *nom modifiable* /
---config/
	  ---database.php
---assets/
	  ---css/
		 ---style.css
	 ---js/
		 ---fiche_trousseau.js
		 ---inventaire.js
---includes/
	    ---footer.php
	    ---header.php
---fiche_trousseau.php
---historique.php
---index.php
---inventaire.php
---modifier_personne.php
---personnes.php
---recherche.php
---trousseaux.php
___

## Fonctionnalités principales

### Personnes
Ajouter, modifier une personne
Voir les trousseaux en cours et l'historique des anciens trousseaux
Liste séparée : personnes avec trousseau actif/ sans trousseau

### Trousseaux
Créer un trousseau (statut Disponible par défaut)
Attribué un trousseau à une personne avec date remise et décharge
Restituer, déclarer la perte
Marquer la décharge comme signée après coup
Ajouter / retirer des badges et des clés
Déclarer un élément perdu individuellement

### Inventaire
Gérer les références de clés avec leur accès aux bâtiments
Gérer les badges (Ela/Salto) avec leur accès aux bâtiments
Gérer les bâtiments

### Historique
Suivi globale de tous les mouvements de trousseaux (remises, restitutions, pertes)
Filtrage par type d'évènement

### Tableau de bord
Statistiques en temps réel (trousseaux attribués, Badges en circulation, Décharges manquantes)
5 derniers mouvements
___

##Scénario d'utilisation

### Ajouter une personne et lui attribuer un trousseau
**Inventaire** -> créer les bâtiments nécessaires
2. **Inventaire** -> créer les références de clés/badges avec leurs accès
3. **Personnes** -> ajouter la personnes
4. **Trousseaux** -> créer un nouveau trousseau
5. **Fiche trousseau** -> onglet Contenu -> ajouter les éléments (clés/badges)
6. **Fiche trousseau** -> onglet Informations -> Attribuer à la personne

### Restituer un trousseau
**Fiche trousseau** -> onglet Informations -> Restituer le trousseau
2. Le trousseau repasse automatiquement en "Disponible"
3. Les badges repassent automatiquement en "Disponible"

### Déclarer une perte
**Perte d'un trousseau complet** : Fiche trousseau → onglet Informations → Déclarer le trousseau perdu
**Perte d'un élément** : Fiche trousseau → onglet Contenu → bouton "Perdu" sur la ligne concernée
___

## Règles métiers importantes
un trousseau doit contenir au moins un élément avant d'être attribué
un badge ne peut être actif dans deux trousseaux différents
une même référence de clé peut être dans plusieurs trousseaux différents
Les accès au bâtiment sont définies sur la clé ou le badge a la création
Les horaires d'accès des badges sont personnalisés par personne (saisis a l'attribution)
Un trousseau perdu ne peut plus recevoir de nouveaux éléments
___

## Technologies utilisées
*PHP* Avec PDO
*MySQL*
*HTML/CSS* (externalisé dans assets/css/style.css)
*JavaScript* minimal (externalisé dans assets/js/)
Serveur local *WAMP*
 
