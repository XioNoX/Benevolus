-- phpMyAdmin SQL Dump
-- version 3.3.10deb1
-- http://www.phpmyadmin.net
--
-- Serveur: localhost
-- Généré le : Ven 15 Avril 2011 à 20:07
-- Version du serveur: 5.1.54
-- Version de PHP: 5.3.5-1ubuntu6

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Base de données: `mycharrues`
--

-- --------------------------------------------------------

--
-- Structure de la table `acces`
--
-- Création: Mar 12 Avril 2011 à 23:29
--

CREATE TABLE IF NOT EXISTS `acces` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `acces_type_id` int(10) NOT NULL,
  `individu_id` int(10) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_acces_acces_types1` (`acces_type_id`),
  KEY `fk_acces_individus1` (`individu_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `acces_types`
--
-- Création: Mar 12 Avril 2011 à 21:36
--

CREATE TABLE IF NOT EXISTS `acces_types` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `libelle` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `festival_id` int(10) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_acces_types_festivals1` (`festival_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- RELATIONS POUR LA TABLE `acces_types`:
--   `festival_id`
--       `festivals` -> `id`
--

-- --------------------------------------------------------

--
-- Structure de la table `affectations`
--
-- Création: Mar 12 Avril 2011 à 21:36
--

CREATE TABLE IF NOT EXISTS `affectations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `heure_debut` time DEFAULT NULL,
  `heure_fin` time DEFAULT NULL,
  `pas_travaille` tinyint(4) NOT NULL DEFAULT '1',
  `vacation_id` int(11) NOT NULL,
  `individu_id` int(10) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_affectations_vacations1` (`vacation_id`),
  KEY `fk_affectations_individus1` (`individu_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- RELATIONS POUR LA TABLE `affectations`:
--   `individu_id`
--       `individus` -> `id`
--   `vacation_id`
--       `vacations` -> `id`
--

-- --------------------------------------------------------

--
-- Structure de la table `destinataires`
--
-- Création: Mar 12 Avril 2011 à 21:36
--

CREATE TABLE IF NOT EXISTS `destinataires` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `message_id` int(10) NOT NULL,
  `individu_id` int(10) NOT NULL,
  `lu` tinyint(4) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_destinataires_individus1` (`individu_id`),
  KEY `fk_destinataires_messages1` (`message_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- RELATIONS POUR LA TABLE `destinataires`:
--   `message_id`
--       `messages` -> `id`
--

-- --------------------------------------------------------

--
-- Structure de la table `disponibilites`
--
-- Création: Mar 12 Avril 2011 à 21:36
--

CREATE TABLE IF NOT EXISTS `disponibilites` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `festival_jour_id` int(10) NOT NULL,
  `individu_id` int(10) NOT NULL,
  `heure_debut` time NOT NULL,
  `heure_fin` time NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_disponibilites_festivals_jours1` (`festival_jour_id`),
  KEY `fk_disponibilites_individus1` (`individu_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- RELATIONS POUR LA TABLE `disponibilites`:
--   `festival_jour_id`
--       `festivals_jours` -> `id`
--   `individu_id`
--       `individus` -> `id`
--

-- --------------------------------------------------------

--
-- Structure de la table `domaines`
--
-- Création: Mar 12 Avril 2011 à 21:37
--

CREATE TABLE IF NOT EXISTS `domaines` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `libelle` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `festivals`
--
-- Création: Mar 12 Avril 2011 à 21:37
--

CREATE TABLE IF NOT EXISTS `festivals` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `annee` year(4) NOT NULL,
  `libelle` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `taux_horaire` float NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `festivals_jours`
--
-- Création: Mar 12 Avril 2011 à 22:07
--

CREATE TABLE IF NOT EXISTS `festivals_jours` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `jour` date NOT NULL,
  `festival_id` int(10) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_festivals_jours_festivals1` (`festival_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- RELATIONS POUR LA TABLE `festivals_jours`:
--   `festival_id`
--       `festivals` -> `id`
--

-- --------------------------------------------------------

--
-- Structure de la table `historique`
--
-- Création: Mar 12 Avril 2011 à 22:07
--

CREATE TABLE IF NOT EXISTS `historique` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `date` datetime NOT NULL,
  `action` text COLLATE utf8_unicode_ci NOT NULL,
  `individu_id` int(10) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_editeurs_individus1` (`individu_id`),
  KEY `fk_historique_1` (`individu_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- RELATIONS POUR LA TABLE `historique`:
--   `individu_id`
--       `individus` -> `id`
--

-- --------------------------------------------------------

--
-- Structure de la table `historique_organismes`
--
-- Création: Mar 12 Avril 2011 à 22:07
--

CREATE TABLE IF NOT EXISTS `historique_organismes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `responsable` tinyint(1) NOT NULL DEFAULT '0',
  `festival_id` int(10) NOT NULL,
  `organisme_id` int(10) NOT NULL,
  `individu_id` int(10) NOT NULL,
  `present` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `fk_historique_organismes_festivals1` (`festival_id`),
  KEY `fk_historique_organismes_organismes1` (`organisme_id`),
  KEY `fk_historique_organismes_individus1` (`individu_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- RELATIONS POUR LA TABLE `historique_organismes`:
--   `festival_id`
--       `festivals` -> `id`
--   `individu_id`
--       `individus` -> `id`
--   `organisme_id`
--       `organismes` -> `id`
--

-- --------------------------------------------------------

--
-- Structure de la table `individus`
--
-- Création: Mar 12 Avril 2011 à 18:28
--

CREATE TABLE IF NOT EXISTS `individus` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `email` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `mot_de_passe` varchar(255) CHARACTER SET latin1 DEFAULT NULL,
  `nom` varchar(255) CHARACTER SET latin1 NOT NULL,
  `prenom` varchar(255) CHARACTER SET latin1 NOT NULL,
  `date_naissance` date DEFAULT NULL,
  `autorisation_parentale` tinyint(1) NOT NULL,
  `telephone_fixe` varchar(10) CHARACTER SET latin1 DEFAULT NULL,
  `telephone_portable` varchar(10) CHARACTER SET latin1 DEFAULT NULL,
  `adresse1` varchar(255) CHARACTER SET latin1 NOT NULL,
  `adresse2` varchar(255) CHARACTER SET latin1 DEFAULT NULL,
  `photo` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `commentaire` text CHARACTER SET latin1,
  `actif` tinyint(1) NOT NULL DEFAULT '0',
  `acces_web` int(10) NOT NULL,
  `ville_id` int(10) NOT NULL,
  `statut_id` smallint(6) NOT NULL,
  `identifier` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_individus_villes1` (`ville_id`),
  KEY `fk_individus_interdits1` (`statut_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `invitations`
--
-- Création: Mer 30 Mars 2011 à 17:38
--

CREATE TABLE IF NOT EXISTS `invitations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uuid` varchar(45) COLLATE utf8_unicode_ci NOT NULL,
  `nom` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `prenom` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `date_naissance` date NOT NULL,
  `email` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `date_invitation` datetime NOT NULL,
  `valide` tinyint(1) NOT NULL,
  `parrain_id` int(10) NOT NULL,
  `organisme_id` int(10) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_invitations_individus1` (`parrain_id`),
  KEY `fk_invitations_organisme1` (`organisme_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `lieux`
--
-- Création: Mer 30 Mars 2011 à 17:38
--

CREATE TABLE IF NOT EXISTS `lieux` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `libelle` varchar(255) CHARACTER SET latin1 NOT NULL,
  `domaine_id` int(10) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_lieux_domaines1` (`domaine_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `messages`
--
-- Création: Mer 30 Mars 2011 à 17:38
--

CREATE TABLE IF NOT EXISTS `messages` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `individu_id` int(10) NOT NULL,
  `sujet` varchar(255) CHARACTER SET latin1 NOT NULL,
  `message` text CHARACTER SET latin1 NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_messages_individus1` (`individu_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `organismes`
--
-- Création: Mer 30 Mars 2011 à 17:38
--

CREATE TABLE IF NOT EXISTS `organismes` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `libelle` varchar(255) CHARACTER SET latin1 NOT NULL,
  `adresse1` varchar(255) CHARACTER SET latin1 DEFAULT NULL,
  `adresse2` varchar(255) CHARACTER SET latin1 DEFAULT NULL,
  `ville_id` int(10) NOT NULL,
  `organisme_type_id` int(10) NOT NULL,
  `maximum` tinyint(4) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_organismes_villes1` (`ville_id`),
  KEY `fk_organismes_organismes_types1` (`organisme_type_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `organismes_types`
--
-- Création: Mar 12 Avril 2011 à 21:38
--

CREATE TABLE IF NOT EXISTS `organismes_types` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `libelle` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `organismes_vacations`
--
-- Création: Mar 12 Avril 2011 à 21:38
--

CREATE TABLE IF NOT EXISTS `organismes_vacations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `organisme_id` int(11) NOT NULL,
  `vacation_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_organismes_vacations_1` (`organisme_id`),
  KEY `fk_organismes_vacations_2` (`vacation_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- RELATIONS POUR LA TABLE `organismes_vacations`:
--   `organisme_id`
--       `organismes` -> `id`
--   `vacation_id`
--       `vacations` -> `id`
--

-- --------------------------------------------------------

--
-- Structure de la table `recommandations`
--
-- Création: Mar 12 Avril 2011 à 21:38
--

CREATE TABLE IF NOT EXISTS `recommandations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `vacation_id` int(11) NOT NULL,
  `individu_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `id_UNIQUE` (`id`),
  KEY `fk_recommandations_1` (`vacation_id`),
  KEY `fk_recommandations_2` (`individu_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- RELATIONS POUR LA TABLE `recommandations`:
--   `vacation_id`
--       `vacations` -> `id`
--   `individu_id`
--       `individus` -> `id`
--

-- --------------------------------------------------------

--
-- Structure de la table `responsables_domaines`
--
-- Création: Mar 12 Avril 2011 à 21:38
--

CREATE TABLE IF NOT EXISTS `responsables_domaines` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `domaine_id` int(10) NOT NULL,
  `individu_id` int(10) NOT NULL,
  `festival_id` int(10) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_responsables_domaines_domaines1` (`domaine_id`),
  KEY `fk_responsables_domaines_individus1` (`individu_id`),
  KEY `fk_responsables_domaines_festivals1` (`festival_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- RELATIONS POUR LA TABLE `responsables_domaines`:
--   `domaine_id`
--       `domaines` -> `id`
--   `festival_id`
--       `festivals` -> `id`
--   `individu_id`
--       `individus` -> `id`
--

-- --------------------------------------------------------

--
-- Structure de la table `responsables_lieux`
--
-- Création: Mar 12 Avril 2011 à 21:38
--

CREATE TABLE IF NOT EXISTS `responsables_lieux` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `lieu_id` int(10) NOT NULL,
  `individu_id` int(10) NOT NULL,
  `festival_id` int(10) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_responsables_lieux_lieux1` (`lieu_id`),
  KEY `fk_responsables_lieux_individus1` (`individu_id`),
  KEY `fk_responsables_lieux_festivals1` (`festival_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- RELATIONS POUR LA TABLE `responsables_lieux`:
--   `festival_id`
--       `festivals` -> `id`
--   `individu_id`
--       `individus` -> `id`
--   `lieu_id`
--       `lieux` -> `id`
--

-- --------------------------------------------------------

--
-- Structure de la table `statuts`
--
-- Création: Mar 12 Avril 2011 à 21:39
--

CREATE TABLE IF NOT EXISTS `statuts` (
  `id` smallint(6) NOT NULL AUTO_INCREMENT,
  `libelle` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `commentaire` text COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `tickets`
--
-- Création: Mar 12 Avril 2011 à 21:39
--

CREATE TABLE IF NOT EXISTS `tickets` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `libelle` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `festival_id` int(10) NOT NULL,
  `individu_id` int(10) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_tickets_festivals1` (`festival_id`),
  KEY `fk_tickets_individus1` (`individu_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- RELATIONS POUR LA TABLE `tickets`:
--   `festival_id`
--       `festivals` -> `id`
--   `individu_id`
--       `individus` -> `id`
--

-- --------------------------------------------------------

--
-- Structure de la table `types_individus`
--
-- Création: Mar 12 Avril 2011 à 21:39
--

CREATE TABLE IF NOT EXISTS `types_individus` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `libelle` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `individu_id` int(10) NOT NULL,
  `festival_id` int(10) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_types_individus_individus1` (`individu_id`),
  KEY `fk_types_individus_festivals1` (`festival_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- RELATIONS POUR LA TABLE `types_individus`:
--   `festival_id`
--       `festivals` -> `id`
--   `individu_id`
--       `individus` -> `id`
--

-- --------------------------------------------------------

--
-- Structure de la table `vacations`
--
-- Création: Mer 30 Mars 2011 à 17:38
--

CREATE TABLE IF NOT EXISTS `vacations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `libelle` varchar(255) CHARACTER SET latin1 NOT NULL,
  `heure_debut` time NOT NULL,
  `heure_fin` time NOT NULL,
  `nombre_minimum` tinyint(4) DEFAULT NULL,
  `nombre_maximum` tinyint(4) DEFAULT NULL,
  `lieu_id` int(10) NOT NULL,
  `responsable_id` int(10) NOT NULL,
  `festival_jour_id` int(10) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_vacations_lieux1` (`lieu_id`),
  KEY `fk_vacations_individus1` (`responsable_id`),
  KEY `fk_vacations_festivals_jours1` (`festival_jour_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Doublure de structure pour la vue `view_organismes_villes_type`
--
CREATE TABLE IF NOT EXISTS `view_organismes_villes_type` (
`id` int(10)
,`libelle` varchar(255)
,`adresse1` varchar(255)
,`adresse2` varchar(255)
,`cp` varchar(5)
,`nom` varchar(250)
,`type_struct` varchar(255)
);
-- --------------------------------------------------------

--
-- Structure de la table `villes`
--
-- Création: Mar 12 Avril 2011 à 21:39
--

CREATE TABLE IF NOT EXISTS `villes` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `cp` varchar(5) COLLATE utf8_unicode_ci NOT NULL,
  `nom` varchar(250) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la vue `view_organismes_villes_type`
--
DROP TABLE IF EXISTS `view_organismes_villes_type`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `view_organismes_villes_type` AS select `organismes`.`id` AS `id`,`organismes`.`libelle` AS `libelle`,`organismes`.`adresse1` AS `adresse1`,`organismes`.`adresse2` AS `adresse2`,`villes`.`cp` AS `cp`,`villes`.`nom` AS `nom`,`organismes_types`.`libelle` AS `type_struct` from ((`organismes` join `villes` on((`organismes`.`ville_id` = `villes`.`id`))) join `organismes_types` on((`organismes`.`organisme_type_id` = `organismes_types`.`id`)));

--
-- Contraintes pour les tables exportées
--

--
-- Contraintes pour la table `acces_types`
--
ALTER TABLE `acces_types`
  ADD CONSTRAINT `fk_acces_types_festivals1` FOREIGN KEY (`festival_id`) REFERENCES `festivals` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Contraintes pour la table `affectations`
--
ALTER TABLE `affectations`
  ADD CONSTRAINT `fk_affectations_individus1` FOREIGN KEY (`individu_id`) REFERENCES `individus` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_affectations_vacations1` FOREIGN KEY (`vacation_id`) REFERENCES `vacations` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Contraintes pour la table `destinataires`
--
ALTER TABLE `destinataires`
  ADD CONSTRAINT `fk_destinataires_individus1` FOREIGN KEY (`message_id`) REFERENCES `messages` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Contraintes pour la table `disponibilites`
--
ALTER TABLE `disponibilites`
  ADD CONSTRAINT `fk_disponibilites_festivals_jours1` FOREIGN KEY (`festival_jour_id`) REFERENCES `festivals_jours` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_disponibilites_individus1` FOREIGN KEY (`individu_id`) REFERENCES `individus` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Contraintes pour la table `festivals_jours`
--
ALTER TABLE `festivals_jours`
  ADD CONSTRAINT `fk_festivals_jours_festivals1` FOREIGN KEY (`festival_id`) REFERENCES `festivals` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Contraintes pour la table `historique`
--
ALTER TABLE `historique`
  ADD CONSTRAINT `fk_historique_1` FOREIGN KEY (`individu_id`) REFERENCES `individus` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Contraintes pour la table `historique_organismes`
--
ALTER TABLE `historique_organismes`
  ADD CONSTRAINT `fk_historique_organismes_festivals1` FOREIGN KEY (`festival_id`) REFERENCES `festivals` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_historique_organismes_individus1` FOREIGN KEY (`individu_id`) REFERENCES `individus` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_historique_organismes_organismes1` FOREIGN KEY (`organisme_id`) REFERENCES `organismes` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Contraintes pour la table `organismes_vacations`
--
ALTER TABLE `organismes_vacations`
  ADD CONSTRAINT `organismes_vacations_ibfk_1` FOREIGN KEY (`organisme_id`) REFERENCES `organismes` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `organismes_vacations_ibfk_2` FOREIGN KEY (`vacation_id`) REFERENCES `vacations` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Contraintes pour la table `recommandations`
--
ALTER TABLE `recommandations`
  ADD CONSTRAINT `fk_recommandations_1` FOREIGN KEY (`vacation_id`) REFERENCES `vacations` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_recommandations_2` FOREIGN KEY (`individu_id`) REFERENCES `individus` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Contraintes pour la table `responsables_domaines`
--
ALTER TABLE `responsables_domaines`
  ADD CONSTRAINT `fk_responsables_domaines_domaines1` FOREIGN KEY (`domaine_id`) REFERENCES `domaines` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_responsables_domaines_festivals1` FOREIGN KEY (`festival_id`) REFERENCES `festivals` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_responsables_domaines_individus1` FOREIGN KEY (`individu_id`) REFERENCES `individus` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Contraintes pour la table `responsables_lieux`
--
ALTER TABLE `responsables_lieux`
  ADD CONSTRAINT `fk_responsables_lieux_festivals1` FOREIGN KEY (`festival_id`) REFERENCES `festivals` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_responsables_lieux_individus1` FOREIGN KEY (`individu_id`) REFERENCES `individus` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_responsables_lieux_lieux1` FOREIGN KEY (`lieu_id`) REFERENCES `lieux` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Contraintes pour la table `tickets`
--
ALTER TABLE `tickets`
  ADD CONSTRAINT `fk_tickets_festivals1` FOREIGN KEY (`festival_id`) REFERENCES `festivals` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION,
  ADD CONSTRAINT `fk_tickets_individus1` FOREIGN KEY (`individu_id`) REFERENCES `individus` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION;

--
-- Contraintes pour la table `types_individus`
--
ALTER TABLE `types_individus`
  ADD CONSTRAINT `fk_types_individus_festivals1` FOREIGN KEY (`festival_id`) REFERENCES `festivals` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_types_individus_individus1` FOREIGN KEY (`individu_id`) REFERENCES `individus` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
