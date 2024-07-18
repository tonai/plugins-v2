-- phpMyAdmin SQL Dump
-- version 3.3.2
-- http://www.phpmyadmin.net
--
-- Serveur: 127.0.0.1
-- Généré le : Dim 30 Mai 2010 à 12:17
-- Version du serveur: 5.1.45
-- Version de PHP: 5.3.2

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Base de données: `plugins`
--

-- --------------------------------------------------------

--
-- Structure de la table `content_manager`
--

CREATE TABLE IF NOT EXISTS `content_manager` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `page` int(11) NOT NULL,
  `texte` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

--
-- Contenu de la table `content_manager`
--


-- --------------------------------------------------------

--
-- Structure de la table `modules`
--

CREATE TABLE IF NOT EXISTS `modules` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `module` varchar(50) NOT NULL,
  `page` tinyint(4) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=4 ;

--
-- Contenu de la table `modules`
--

INSERT INTO `modules` (`id`, `module`, `page`) VALUES
(1, 'AdminManager', 0),
(2, 'PageManager', 0),
(3, 'ContentManager', 1);

-- --------------------------------------------------------

--
-- Structure de la table `page_manager`
--

CREATE TABLE IF NOT EXISTS `page_manager` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `pid` int(11) NOT NULL,
  `module` varchar(50) NOT NULL,
  `titre` varchar(50) NOT NULL,
  `menu` varchar(50) NOT NULL,
  `data` text NOT NULL,
  `sort` int(11) NOT NULL DEFAULT '0',
  `default_page` tinyint(4) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=5 ;

--
-- Contenu de la table `page_manager`
--

INSERT INTO `page_manager` (`id`, `pid`, `module`, `titre`, `menu`, `data`, `sort`, `default_page`) VALUES
(4, 0, '3', 'Accueil', 'Accueil', 's:0:"";', 0, 1);

-- --------------------------------------------------------

--
-- Structure de la table `users`
--

CREATE TABLE IF NOT EXISTS `users` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `login` varchar(50) NOT NULL,
  `password` varchar(50) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=2 ;

--
-- Contenu de la table `users`
--

INSERT INTO `users` (`id`, `login`, `password`) VALUES
(1, 'tonai', '6110a47557c6b9d446aa84d5760ba708');
