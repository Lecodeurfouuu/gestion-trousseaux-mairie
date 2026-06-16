-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1:3306
-- Généré le : lun. 15 juin 2026 à 11:54
-- Version du serveur : 8.4.7
-- Version de PHP : 8.3.28

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `stage_db`
--

-- --------------------------------------------------------

--
-- Structure de la table `badges`
--

DROP TABLE IF EXISTS `badges`;
CREATE TABLE IF NOT EXISTS `badges` (
  `id_badge` int NOT NULL AUTO_INCREMENT COMMENT 'Clé primaire du badge',
  `identifiant_interne` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Identifiant interne du badge si disponible, exemple : BLEU-001',
  `identifiant_officiel` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Identifiant officiel du badge si disponible',
  `type_badge` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Type de badge : Salto (bleu) ou Ela (noir)',
  `statut` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Le statut du badge :  disponible, attribué, perdu ou archivé',
  PRIMARY KEY (`id_badge`),
  UNIQUE KEY `identifiant_interne` (`identifiant_interne`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `batiments`
--

DROP TABLE IF EXISTS `batiments`;
CREATE TABLE IF NOT EXISTS `batiments` (
  `id_batiment` int NOT NULL AUTO_INCREMENT COMMENT 'Clé primaire du batiment',
  `nom_batiment` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Clé primaire du batiment',
  `adresse` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Adresse du bâtiment',
  `commentaire` text COLLATE utf8mb4_unicode_ci COMMENT 'Commentaire éventuel sur le bâtiment',
  PRIMARY KEY (`id_batiment`),
  UNIQUE KEY `nom_batiment` (`nom_batiment`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Table permettant la gestion des bâtiments et des plages horaires';

-- --------------------------------------------------------

--
-- Structure de la table `element_acces`
--

DROP TABLE IF EXISTS `element_acces`;
CREATE TABLE IF NOT EXISTS `element_acces` (
  `id_element_acces` int NOT NULL AUTO_INCREMENT COMMENT 'Clé primaire de l''accès',
  `type_element` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Type d''élément : cle ou badge',
  `id_reference_cle` int DEFAULT NULL COMMENT 'Référence de clé concernée si type_element = cle',
  `id_badge` int DEFAULT NULL COMMENT 'Badge concerné si type_element = badge',
  `id_batiment` int NOT NULL COMMENT 'Bâtiment accessible',
  `id_porte` int DEFAULT NULL COMMENT 'porte concernée par l''accès',
  PRIMARY KEY (`id_element_acces`),
  KEY `id_reference_cle` (`id_reference_cle`),
  KEY `id_badge` (`id_badge`),
  KEY `id_batiment` (`id_batiment`),
  KEY `fk_acces_porte` (`id_porte`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Table permettant de gérer les accès aux bâtiments pour les clés et les badges';

-- --------------------------------------------------------

--
-- Structure de la table `historique_trousseaux`
--

DROP TABLE IF EXISTS `historique_trousseaux`;
CREATE TABLE IF NOT EXISTS `historique_trousseaux` (
  `id_historique` int NOT NULL AUTO_INCREMENT COMMENT 'Clé primaire de l’historique',
  `id_trousseau` int NOT NULL COMMENT 'Trousseau concerné',
  `id_personne` int NOT NULL COMMENT 'Personne concernée par la remise ou restitution',
  `date_remise` date NOT NULL COMMENT 'Date de remise du trousseau',
  `date_restitution` date DEFAULT NULL COMMENT 'Date de restitution du trousseau, NULL si non rendu',
  `decharge_signee` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'Décharge signée : 1 = oui, 0 = non',
  `statut_evenement` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Type d’événement : Remis, Restitué, Perdu',
  `commentaire` text COLLATE utf8mb4_unicode_ci COMMENT 'Commentaire éventuel',
  PRIMARY KEY (`id_historique`),
  KEY `id_trousseau` (`id_trousseau`),
  KEY `id_personne` (`id_personne`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Table permettant de conserver l’historique des remises et restitutions des trousseaux';

-- --------------------------------------------------------

--
-- Structure de la table `personnes`
--

DROP TABLE IF EXISTS `personnes`;
CREATE TABLE IF NOT EXISTS `personnes` (
  `id_personne` int NOT NULL AUTO_INCREMENT COMMENT 'Clé primaire de la table personne',
  `nom` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Nom de la personne ',
  `prenom` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Prénom de la personne',
  `service` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Service de la personne mairie,association ect ',
  `groupe_personnel` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'sous catégorie de service (technicien, secrétaire, comptable, professeur )',
  `telephone` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Numéro de téléphone de la personne (NULL possible)',
  `mail` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Mail de la personne (NULL possible)',
  PRIMARY KEY (`id_personne`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Table permettant la gestion des personnes';

-- --------------------------------------------------------

--
-- Structure de la table `portes`
--

DROP TABLE IF EXISTS `portes`;
CREATE TABLE IF NOT EXISTS `portes` (
  `id_porte` int NOT NULL AUTO_INCREMENT COMMENT 'Clé primaire',
  `id_batiment` int NOT NULL COMMENT 'bâtiment concerné FK',
  `nom_porte` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Nom de la porte ex : Porte 36, Porte avant',
  `photo` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id_porte`),
  UNIQUE KEY `unique_batiment_porte` (`id_batiment`,`nom_porte`),
  KEY `id_batiment` (`id_batiment`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Portes associées aux bâtiments';

-- --------------------------------------------------------

--
-- Structure de la table `references_cles`
--

DROP TABLE IF EXISTS `references_cles`;
CREATE TABLE IF NOT EXISTS `references_cles` (
  `id_reference_cle` int NOT NULL AUTO_INCREMENT COMMENT 'Clé primaire de la référence de clé',
  `reference_cle` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Référence de la clé, exemple : REF-45',
  `commentaire` text COLLATE utf8mb4_unicode_ci COMMENT 'Commentaire éventuel sur la référence de clé',
  PRIMARY KEY (`id_reference_cle`),
  UNIQUE KEY `reference_cle` (`reference_cle`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Table permettant la gestion des références de clés';

-- --------------------------------------------------------

--
-- Structure de la table `trousseaux`
--

DROP TABLE IF EXISTS `trousseaux`;
CREATE TABLE IF NOT EXISTS `trousseaux` (
  `id_trousseau` int NOT NULL AUTO_INCREMENT COMMENT 'Clé primaire du trousseau',
  `numero_trousseau` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Numéro unique du trousseau, exemple : TR-001',
  `statut` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Disponible' COMMENT 'Statut du trousseau : Disponible, Attribué, Perdu',
  `commentaire` text COLLATE utf8mb4_unicode_ci COMMENT 'Commentaire éventuel sur le trousseau',
  PRIMARY KEY (`id_trousseau`),
  UNIQUE KEY `numero_trousseau` (`numero_trousseau`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Table permettant la gestion des trousseaux';

-- --------------------------------------------------------

--
-- Structure de la table `trousseau_elements`
--

DROP TABLE IF EXISTS `trousseau_elements`;
CREATE TABLE IF NOT EXISTS `trousseau_elements` (
  `id_trousseau_element` int NOT NULL AUTO_INCREMENT COMMENT 'Clé primaire de l’élément du trousseau',
  `id_trousseau` int NOT NULL COMMENT 'Trousseau concerné',
  `type_element` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Type d’élément : cle ou badge',
  `id_reference_cle` int DEFAULT NULL COMMENT 'Référence de clé si l’élément est une clé',
  `id_badge` int DEFAULT NULL COMMENT 'Badge si l’élément est un badge',
  `statut` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Présent' COMMENT 'Statut : Présent, Retiré, Perdu',
  `commentaire_horaires` text COLLATE utf8mb4_unicode_ci COMMENT 'Horaires personnalisés pour les badges selon la personne et les accès',
  `date_ajout` date DEFAULT NULL COMMENT 'Date d’ajout de l’élément dans le trousseau',
  `date_retrait` date DEFAULT NULL COMMENT 'Date de retrait de l’élément du trousseau',
  `commentaire` text COLLATE utf8mb4_unicode_ci COMMENT 'Commentaire sur l’élément dans le trousseau',
  PRIMARY KEY (`id_trousseau_element`),
  KEY `id_trousseau` (`id_trousseau`),
  KEY `id_reference_cle` (`id_reference_cle`),
  KEY `id_badge` (`id_badge`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Table permettant de gérer les clés et badges contenus dans les trousseaux';

--
-- Contraintes pour les tables déchargées
--

--
-- Contraintes pour la table `element_acces`
--
ALTER TABLE `element_acces`
  ADD CONSTRAINT `fk_acces_badge` FOREIGN KEY (`id_badge`) REFERENCES `badges` (`id_badge`) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_acces_batiment` FOREIGN KEY (`id_batiment`) REFERENCES `batiments` (`id_batiment`) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_acces_porte` FOREIGN KEY (`id_porte`) REFERENCES `portes` (`id_porte`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_acces_reference_cle` FOREIGN KEY (`id_reference_cle`) REFERENCES `references_cles` (`id_reference_cle`) ON DELETE RESTRICT ON UPDATE CASCADE;

--
-- Contraintes pour la table `historique_trousseaux`
--
ALTER TABLE `historique_trousseaux`
  ADD CONSTRAINT `fk_historique_personne` FOREIGN KEY (`id_personne`) REFERENCES `personnes` (`id_personne`) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_historique_trousseau` FOREIGN KEY (`id_trousseau`) REFERENCES `trousseaux` (`id_trousseau`) ON DELETE RESTRICT ON UPDATE CASCADE;

--
-- Contraintes pour la table `portes`
--
ALTER TABLE `portes`
  ADD CONSTRAINT `fk_porte_batiment` FOREIGN KEY (`id_batiment`) REFERENCES `batiments` (`id_batiment`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Contraintes pour la table `trousseau_elements`
--
ALTER TABLE `trousseau_elements`
  ADD CONSTRAINT `fk_element_badge` FOREIGN KEY (`id_badge`) REFERENCES `badges` (`id_badge`) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_element_reference_cle` FOREIGN KEY (`id_reference_cle`) REFERENCES `references_cles` (`id_reference_cle`) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_element_trousseau` FOREIGN KEY (`id_trousseau`) REFERENCES `trousseaux` (`id_trousseau`) ON DELETE RESTRICT ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
