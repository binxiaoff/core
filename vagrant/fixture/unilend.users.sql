-- phpMyAdmin SQL Dump
-- version 4.4.13.1
-- http://www.phpmyadmin.net
--
-- Client :  localhost
-- Généré le :  Mer 19 Août 2015 à 08:46
-- Version du serveur :  5.5.44-37.3
-- Version de PHP :  5.3.10-1ubuntu3.19

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données :  `unilend`
--

-- --------------------------------------------------------

--
-- Structure de la table `users`
--

CREATE TABLE IF NOT EXISTS `users` (
  `id_user` int(11) NOT NULL,
  `id_user_type` int(11) NOT NULL,
  `firstname` varchar(255) NOT NULL COMMENT 'Prénom du user',
  `name` varchar(255) NOT NULL COMMENT 'Nom du user',
  `phone` varchar(50) NOT NULL COMMENT 'Numére de téléphone',
  `mobile` varchar(50) NOT NULL COMMENT 'Numéro de portable',
  `email` varchar(255) NOT NULL COMMENT 'Le mail qui nous sert pour le login',
  `password` varchar(255) NOT NULL COMMENT 'Mot de passe en MD5',
  `password_edited` datetime NOT NULL COMMENT 'date de maj du password',
  `id_tree` int(11) NOT NULL DEFAULT '0' COMMENT 'ID de la rubrique dans laquelle il arrive au login',
  `status` tinyint(1) NOT NULL COMMENT 'Statut de l''utilisateur (0 : offline | 1 : online | 2 : Intouchable)',
  `default_analyst` tinyint(1) NOT NULL,
  `added` datetime NOT NULL COMMENT 'Date d''ajout',
  `updated` datetime NOT NULL COMMENT 'Date de modification',
  `lastlogin` datetime NOT NULL COMMENT 'Date de la dernière connexion'
) ENGINE=InnoDB AUTO_INCREMENT=30 DEFAULT CHARSET=latin1;

--
-- Contenu de la table `users`
--

INSERT INTO `users` (`id_user`, `id_user_type`, `firstname`, `name`, `phone`, `mobile`, `email`, `password`, `password_edited`, `id_tree`, `status`, `default_analyst`, `added`, `updated`, `lastlogin`) VALUES
(1, 1, 'Dev', 'Unilend', '', '', 'admindev@unilend.fr', 'b6de6c44c85378caedcc11f9651fdab0', '2015-05-28 17:26:59', 1, 1, 0, '2015-05-27 14:19:13', '2015-06-05 19:08:23', '2015-08-19 07:45:00');

--
-- Index pour les tables exportées
--

--
-- Index pour la table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id_user`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT pour les tables exportées
--

--
-- AUTO_INCREMENT pour la table `users`
--
ALTER TABLE `users`
  MODIFY `id_user` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=30;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
