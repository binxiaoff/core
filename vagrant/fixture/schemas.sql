-- MySQL dump 10.13  Distrib 5.5.43-37.2, for Linux (x86_64)
--
-- Host: 10.0.68.124    Database: unilend
-- ------------------------------------------------------
-- Server version	5.5.39-36.0-log

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `accept_cookies`
--

DROP TABLE IF EXISTS `accept_cookies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `accept_cookies` (
  `id_accept_cookies` int(11) NOT NULL AUTO_INCREMENT,
  `ip` varchar(50) NOT NULL,
  `id_client` int(11) NOT NULL,
  `added` datetime NOT NULL,
  `updated` datetime NOT NULL,
  PRIMARY KEY (`id_accept_cookies`),
  KEY `ip` (`ip`),
  KEY `id_client` (`id_client`)
) ENGINE=InnoDB AUTO_INCREMENT=11819 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `acceptations_legal_docs`
--

DROP TABLE IF EXISTS `acceptations_legal_docs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `acceptations_legal_docs` (
  `id_acceptation` int(11) NOT NULL AUTO_INCREMENT,
  `id_legal_doc` int(11) NOT NULL,
  `id_client` int(11) NOT NULL,
  `added` datetime NOT NULL,
  `updated` datetime NOT NULL,
  PRIMARY KEY (`id_acceptation`)
) ENGINE=InnoDB AUTO_INCREMENT=21477 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `acceptations_legal_docs_relances`
--

DROP TABLE IF EXISTS `acceptations_legal_docs_relances`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `acceptations_legal_docs_relances` (
  `id_relance` int(11) NOT NULL AUTO_INCREMENT,
  `id_client` int(11) NOT NULL,
  `id_cgv` int(11) NOT NULL,
  `date_relance` datetime NOT NULL,
  `updated` datetime NOT NULL,
  `added` datetime NOT NULL,
  PRIMARY KEY (`id_relance`),
  KEY `id_client` (`id_client`),
  KEY `id_cgv` (`id_cgv`)
) ENGINE=InnoDB AUTO_INCREMENT=1579 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `alerte_solde_preteurs`
--

DROP TABLE IF EXISTS `alerte_solde_preteurs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `alerte_solde_preteurs` (
  `id_alerte_solde_preteur` int(11) NOT NULL AUTO_INCREMENT,
  `id_client` int(11) NOT NULL,
  `solde_display` int(11) NOT NULL,
  `solde` int(11) NOT NULL,
  `added` datetime NOT NULL,
  `updated` datetime NOT NULL,
  PRIMARY KEY (`id_alerte_solde_preteur`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `autobid`
--

DROP TABLE IF EXISTS `autobid`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `autobid` (
  `id_autobid` int(11) NOT NULL AUTO_INCREMENT,
  `id_lender` int(11) NOT NULL,
  `status` tinyint(4) NOT NULL,
  `risk_max` float NOT NULL,
  `rate` float NOT NULL,
  `amount_max` float NOT NULL,
  `pct_max` float NOT NULL,
  `added` datetime NOT NULL,
  `updated` datetime NOT NULL,
  PRIMARY KEY (`id_autobid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `backpayline`
--

DROP TABLE IF EXISTS `backpayline`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `backpayline` (
  `id_backpayline` int(11) NOT NULL AUTO_INCREMENT,
  `id` varchar(255) NOT NULL,
  `date` varchar(255) NOT NULL,
  `amount` int(11) NOT NULL,
  `token` varchar(255) NOT NULL,
  `serialize` text NOT NULL,
  `code` varchar(50) NOT NULL,
  `added` datetime NOT NULL,
  `updated` datetime NOT NULL,
  PRIMARY KEY (`id_backpayline`)
) ENGINE=InnoDB AUTO_INCREMENT=24730 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `backup_delete_clients`
--

DROP TABLE IF EXISTS `backup_delete_clients`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `backup_delete_clients` (
  `id_client_delete` int(11) NOT NULL AUTO_INCREMENT,
  `id_client` int(11) NOT NULL,
  `hash_client` varchar(255) NOT NULL,
  `id_langue` varchar(5) NOT NULL,
  `id_partenaire` int(11) NOT NULL,
  `id_partenaire_subcode` int(11) NOT NULL,
  `id_facebook` varchar(45) NOT NULL,
  `id_linkedin` varchar(45) NOT NULL,
  `id_viadeo` varchar(45) NOT NULL,
  `id_twitter` varchar(45) NOT NULL,
  `civilite` enum('M.','Mme','Mlle') NOT NULL,
  `nom` varchar(255) NOT NULL,
  `nom_usage` varchar(255) NOT NULL,
  `prenom` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `fonction` varchar(255) NOT NULL,
  `naissance` date NOT NULL,
  `ville_naissance` varchar(255) NOT NULL,
  `id_pays_naissance` int(11) NOT NULL,
  `id_nationalite` int(11) NOT NULL,
  `telephone` varchar(255) NOT NULL,
  `mobile` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `secrete_question` varchar(255) NOT NULL,
  `secrete_reponse` varchar(255) NOT NULL,
  `type` tinyint(1) NOT NULL COMMENT '(prêteur)1 : physi 2 : morale 3 : physi etran 4 : morale etran',
  `status_depot_dossier` tinyint(4) NOT NULL COMMENT 'etapes',
  `etape_inscription_preteur` tinyint(1) NOT NULL COMMENT 'etepe1 ,2 ,3',
  `status_inscription_preteur` tinyint(1) NOT NULL,
  `status_pre_emp` tinyint(1) NOT NULL COMMENT '1 : preteur | 2 : emprunteur | 3 : les deux',
  `status_transition` tinyint(1) NOT NULL COMMENT '1 : en transition | 0 : ok',
  `cni_passeport` varchar(255) NOT NULL,
  `signature` varchar(255) NOT NULL,
  `optin1` tinyint(1) NOT NULL COMMENT '0: Offline | 1: Online',
  `optin2` tinyint(1) NOT NULL COMMENT '0: Offline | 1: Online',
  `status` tinyint(1) NOT NULL COMMENT '0: Offline | 1: Online',
  `added_backup` datetime NOT NULL,
  `updated_backup` datetime NOT NULL,
  `added` datetime NOT NULL,
  `updated` datetime NOT NULL,
  `lastlogin` datetime NOT NULL,
  PRIMARY KEY (`id_client_delete`),
  KEY `hash` (`hash_client`),
  KEY `email` (`email`),
  KEY `id_client` (`id_client`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `backup_delete_clients_adresses`
--

DROP TABLE IF EXISTS `backup_delete_clients_adresses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `backup_delete_clients_adresses` (
  `id_client_adresse_delete` int(11) NOT NULL AUTO_INCREMENT,
  `id_adresse` int(11) NOT NULL,
  `id_client` int(11) NOT NULL,
  `defaut` tinyint(1) NOT NULL DEFAULT '0' COMMENT '0: Non | 1: Oui adresse par defaut',
  `type` tinyint(1) NOT NULL COMMENT '0: Livraison | 1: Facturation',
  `nom_adresse` varchar(255) NOT NULL,
  `civilite` enum('M.','Mme','Mlle') NOT NULL,
  `nom` varchar(255) NOT NULL,
  `prenom` varchar(255) NOT NULL,
  `societe` varchar(255) NOT NULL,
  `adresse1` varchar(255) NOT NULL,
  `adresse2` varchar(255) NOT NULL,
  `adresse3` varchar(255) NOT NULL,
  `cp` varchar(255) NOT NULL,
  `ville` varchar(255) NOT NULL,
  `id_pays` int(11) NOT NULL,
  `telephone` varchar(255) NOT NULL,
  `mobile` varchar(255) NOT NULL,
  `commentaire` text NOT NULL,
  `meme_adresse_fiscal` tinyint(4) NOT NULL COMMENT 'Preteur particulier',
  `adresse_fiscal` varchar(255) NOT NULL COMMENT 'Preteur particulier',
  `ville_fiscal` varchar(255) NOT NULL COMMENT 'Preteur particulier',
  `cp_fiscal` varchar(255) NOT NULL COMMENT 'Preteur particulier',
  `id_pays_fiscal` int(11) NOT NULL,
  `status` tinyint(1) NOT NULL COMMENT '0: Offline | 1: Online',
  `added_backup` datetime NOT NULL,
  `updated_backup` datetime NOT NULL,
  `added` datetime NOT NULL,
  `updated` datetime NOT NULL,
  PRIMARY KEY (`id_client_adresse_delete`),
  KEY `id_client` (`id_client`),
  KEY `type` (`type`),
  KEY `defaut` (`defaut`),
  KEY `id_adresse` (`id_adresse`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `backup_delete_companies`
--

DROP TABLE IF EXISTS `backup_delete_companies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `backup_delete_companies` (
  `id_company_delete` int(11) NOT NULL AUTO_INCREMENT,
  `id_company` int(11) NOT NULL,
  `id_client_owner` int(11) NOT NULL,
  `id_partenaire` int(11) NOT NULL,
  `id_partenaire_subcode` int(11) NOT NULL,
  `email_facture` varchar(255) NOT NULL,
  `name` text NOT NULL,
  `forme` varchar(255) NOT NULL,
  `siren` varchar(15) NOT NULL,
  `iban` varchar(28) NOT NULL,
  `bic` varchar(100) NOT NULL,
  `execices_comptables` tinyint(1) NOT NULL COMMENT '1 : au moins trois exercices comptables | 0 : non',
  `rcs` varchar(45) NOT NULL,
  `tribunal_com` varchar(255) NOT NULL,
  `activite` varchar(255) NOT NULL,
  `lieu_exploi` varchar(255) NOT NULL,
  `tva` float NOT NULL,
  `capital` double NOT NULL,
  `date_creation` date NOT NULL,
  `adresse1` varchar(255) NOT NULL,
  `adresse2` varchar(255) NOT NULL,
  `zip` varchar(10) NOT NULL,
  `city` varchar(255) NOT NULL,
  `id_pays` int(11) NOT NULL,
  `phone` varchar(45) NOT NULL,
  `status_adresse_correspondance` tinyint(1) NOT NULL COMMENT '1 : meme adresse que le siege | 0 : pas la meme adresse',
  `status_client` tinyint(4) NOT NULL COMMENT '1 : dirigeant | 2 : beneficie d''une délégation de pouvoir | 3 : externe à l''entreprise',
  `status_conseil_externe_entreprise` int(11) NOT NULL,
  `preciser_conseil_externe_entreprise` varchar(255) NOT NULL COMMENT 'quand status_conseil_externe_entreprise = autre',
  `civilite_dirigeant` enum('M.','Mme','Mlle') NOT NULL COMMENT 'rempli si client n''est pas le dirigeant',
  `nom_dirigeant` varchar(255) NOT NULL COMMENT 'rempli si client n''est pas le dirigeant',
  `prenom_dirigeant` varchar(255) NOT NULL COMMENT 'rempli si client n''est pas le dirigeant',
  `fonction_dirigeant` varchar(255) NOT NULL COMMENT 'rempli si client n''est pas le dirigeant',
  `email_dirigeant` varchar(255) NOT NULL COMMENT 'rempli si client n''est pas le dirigeant',
  `phone_dirigeant` varchar(45) NOT NULL COMMENT 'rempli si client n''est pas le dirigeant',
  `sector` int(11) NOT NULL,
  `risk` varchar(45) NOT NULL,
  `altares_eligibility` varchar(255) NOT NULL,
  `altares_dateValeur` date NOT NULL,
  `altares_niveauRisque` varchar(255) NOT NULL,
  `altares_scoreVingt` int(11) NOT NULL,
  `added_backup` datetime NOT NULL,
  `updated_backup` datetime NOT NULL,
  `added` datetime NOT NULL,
  `updated` datetime NOT NULL,
  PRIMARY KEY (`id_company_delete`),
  KEY `id_company` (`id_company`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `backup_delete_companies_actif_passif`
--

DROP TABLE IF EXISTS `backup_delete_companies_actif_passif`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `backup_delete_companies_actif_passif` (
  `id_actif_passif_delete` int(11) NOT NULL AUTO_INCREMENT,
  `id_actif_passif` int(11) NOT NULL,
  `id_company` int(11) NOT NULL,
  `ordre` int(4) NOT NULL,
  `annee` year(4) NOT NULL,
  `immobilisations_corporelles` double NOT NULL,
  `immobilisations_incorporelles` double NOT NULL,
  `immobilisations_financieres` double NOT NULL,
  `stocks` int(11) NOT NULL,
  `creances_clients` double NOT NULL,
  `disponibilites` double NOT NULL,
  `valeurs_mobilieres_de_placement` double NOT NULL,
  `capitaux_propres` double NOT NULL,
  `provisions_pour_risques_et_charges` double NOT NULL,
  `amortissement_sur_immo` double NOT NULL,
  `dettes_financieres` double NOT NULL,
  `dettes_fournisseurs` double NOT NULL,
  `autres_dettes` double NOT NULL,
  `added_backup` datetime NOT NULL,
  `updated_backup` datetime NOT NULL,
  `added` datetime NOT NULL,
  `updated` datetime NOT NULL,
  PRIMARY KEY (`id_actif_passif_delete`),
  KEY `id_company` (`id_company`),
  KEY `id_actif_passif` (`id_actif_passif`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `backup_delete_companies_bilans`
--

DROP TABLE IF EXISTS `backup_delete_companies_bilans`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `backup_delete_companies_bilans` (
  `id_bilan_delete` int(11) NOT NULL AUTO_INCREMENT,
  `id_bilan` int(11) NOT NULL,
  `id_company` int(11) NOT NULL,
  `ca` double NOT NULL,
  `resultat_brute_exploitation` double NOT NULL,
  `resultat_exploitation` double NOT NULL,
  `investissements` double NOT NULL,
  `date` year(4) NOT NULL COMMENT 'annee bilan',
  `added_backup` datetime NOT NULL,
  `updated_backup` datetime NOT NULL,
  `added` datetime NOT NULL,
  `updated` datetime NOT NULL,
  PRIMARY KEY (`id_bilan_delete`),
  UNIQUE KEY `id_company` (`id_company`,`date`),
  KEY `id_bilan` (`id_bilan`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `backup_delete_companies_details`
--

DROP TABLE IF EXISTS `backup_delete_companies_details`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `backup_delete_companies_details` (
  `id_company_detail_delete` int(11) NOT NULL AUTO_INCREMENT,
  `id_company_detail` int(11) NOT NULL,
  `id_company` int(11) NOT NULL,
  `date_dernier_bilan` date NOT NULL,
  `date_dernier_bilan_mois` int(11) NOT NULL,
  `date_dernier_bilan_annee` year(4) NOT NULL,
  `encours_actuel_dette_fianciere` double NOT NULL,
  `remb_a_venir_cette_annee` double NOT NULL,
  `remb_a_venir_annee_prochaine` double NOT NULL,
  `tresorie_dispo_actuellement` double NOT NULL,
  `autre_demandes_financements_prevues` double NOT NULL,
  `precisions` text NOT NULL,
  `decouverts_bancaires` double NOT NULL,
  `lignes_de_tresorerie` double NOT NULL,
  `affacturage` double NOT NULL,
  `escompte` double NOT NULL,
  `financement_dailly` double NOT NULL,
  `credit_de_tresorerie` double NOT NULL,
  `credit_bancaire_investissements_materiels` double NOT NULL,
  `credit_bancaire_investissements_immateriels` double NOT NULL,
  `rachat_entreprise_ou_titres` double NOT NULL,
  `credit_immobilier` double NOT NULL,
  `credit_bail_immobilier` double NOT NULL,
  `credit_bail` double NOT NULL,
  `location_avec_option_achat` double NOT NULL,
  `location_financiere` double NOT NULL,
  `location_longue_duree` double NOT NULL,
  `pret_oseo` double NOT NULL,
  `pret_participatif` double NOT NULL,
  `fichier_extrait_kbis` varchar(255) NOT NULL,
  `fichier_rib` varchar(255) NOT NULL,
  `fichier_delegation_pouvoir` varchar(255) NOT NULL,
  `fichier_logo_societe` varchar(255) NOT NULL,
  `fichier_photo_dirigeant` varchar(255) NOT NULL,
  `fichier_dernier_bilan_certifie` varchar(255) NOT NULL,
  `fichier_cni_passeport` varchar(255) NOT NULL,
  `fichier_derniere_liasse_fiscale` varchar(255) NOT NULL,
  `fichier_derniers_comptes_approuves` varchar(255) NOT NULL,
  `fichier_derniers_comptes_consolides_groupe` varchar(255) NOT NULL,
  `fichier_annexes_rapport_special_commissaire_compte` varchar(255) NOT NULL,
  `fichier_arret_comptable_recent` varchar(255) NOT NULL,
  `fichier_budget_exercice_en_cours_a_venir` varchar(255) NOT NULL,
  `fichier_notation_banque_france` varchar(255) NOT NULL,
  `fichier_autre_1` varchar(255) NOT NULL,
  `fichier_autre_2` varchar(255) NOT NULL,
  `fichier_autre_3` varchar(255) NOT NULL,
  `added_backup` datetime NOT NULL,
  `updated_backup` datetime NOT NULL,
  `added` datetime NOT NULL,
  `updated` datetime NOT NULL,
  PRIMARY KEY (`id_company_detail_delete`),
  UNIQUE KEY `id_company` (`id_company`),
  KEY `id_company_detail` (`id_company_detail`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `backup_delete_lenders_accounts`
--

DROP TABLE IF EXISTS `backup_delete_lenders_accounts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `backup_delete_lenders_accounts` (
  `id_lender_account_delete` int(11) NOT NULL AUTO_INCREMENT,
  `id_lender_account` int(11) NOT NULL,
  `id_client_owner` int(11) NOT NULL,
  `id_company_owner` int(11) NOT NULL,
  `exonere` tinyint(1) NOT NULL COMMENT '0 : non | 1 : oui',
  `iban` varchar(28) NOT NULL,
  `bic` varchar(100) NOT NULL,
  `origine_des_fonds` int(11) NOT NULL,
  `precision` varchar(255) NOT NULL,
  `id_partenaire` int(11) NOT NULL,
  `id_partenaire_subcode` int(11) NOT NULL,
  `status` tinyint(1) NOT NULL COMMENT '0:horsligne | 1:enligne',
  `type_transfert` tinyint(1) NOT NULL COMMENT '1 : virement | 2 : CB',
  `motif` varchar(50) NOT NULL,
  `fonds` int(4) NOT NULL COMMENT 'dans le cas d''un transfert de fonds par cb',
  `cni_passeport` tinyint(1) NOT NULL COMMENT '1 : cni | 2 : passeport',
  `fichier_cni_passeport` varchar(255) NOT NULL,
  `fichier_justificatif_domicile` varchar(255) NOT NULL,
  `fichier_rib` varchar(255) NOT NULL,
  `fichier_cni_passeport_dirigent` varchar(255) NOT NULL,
  `fichier_extrait_kbis` varchar(255) NOT NULL,
  `fichier_delegation_pouvoir` varchar(255) NOT NULL,
  `fichier_statuts` varchar(255) NOT NULL,
  `fichier_autre` varchar(255) NOT NULL,
  `added_backup` datetime NOT NULL,
  `updated_backup` datetime NOT NULL,
  `added` datetime NOT NULL,
  `updated` datetime NOT NULL,
  PRIMARY KEY (`id_lender_account_delete`),
  KEY `id_company_owner` (`id_company_owner`),
  KEY `id_client_owner` (`id_client_owner`),
  KEY `id_lender_account` (`id_lender_account`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `bank_lines`
--

DROP TABLE IF EXISTS `bank_lines`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `bank_lines` (
  `id_bank_line` int(11) NOT NULL AUTO_INCREMENT,
  `id_wallet_line` int(11) NOT NULL,
  `id_lender_account` int(11) NOT NULL,
  `id_company` int(11) NOT NULL,
  `id_term_for_company` int(11) NOT NULL,
  `id_project` int(11) NOT NULL,
  `type` tinyint(4) NOT NULL,
  `status` tinyint(4) NOT NULL,
  `amount` int(11) NOT NULL COMMENT 'x100',
  `added` datetime NOT NULL,
  `updated` datetime NOT NULL,
  PRIMARY KEY (`id_bank_line`),
  KEY `id_lender_account` (`id_lender_account`)
) ENGINE=InnoDB AUTO_INCREMENT=30600 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `bank_unilend`
--

DROP TABLE IF EXISTS `bank_unilend`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `bank_unilend` (
  `id_unilend` int(11) NOT NULL AUTO_INCREMENT,
  `id_transaction` int(11) NOT NULL,
  `id_echeance_emprunteur` int(11) NOT NULL,
  `id_project` int(11) NOT NULL,
  `montant` int(11) NOT NULL COMMENT 'montant pour unilend (*100)',
  `etat` int(11) NOT NULL COMMENT 'part pour l''etat (*100)',
  `type` tinyint(1) NOT NULL COMMENT '0 : 3%+tva | 1 : rembEmprunteur | 2 : rembPreteur | 3 : retrait unilend | 4 : 4 : unilend offre bienvenue ou parrainage',
  `status` tinyint(1) NOT NULL COMMENT '0 : chez unilend | 1 : remboursé aux preteurs (pour les remb) | 3 : retrait unilend',
  `retrait_fiscale` tinyint(1) NOT NULL,
  `added` datetime NOT NULL,
  `updated` datetime NOT NULL,
  PRIMARY KEY (`id_unilend`)
) ENGINE=InnoDB AUTO_INCREMENT=3669 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `banques`
--

DROP TABLE IF EXISTS `banques`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `banques` (
  `id_banque` int(11) NOT NULL DEFAULT '0',
  `nom_banque` varchar(255) DEFAULT NULL,
  `ville_banque` varchar(255) DEFAULT NULL,
  `swift_code_banque` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id_banque`),
  KEY `swift_code_banque` (`swift_code_banque`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `bids`
--

DROP TABLE IF EXISTS `bids`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `bids` (
  `id_bid` int(11) NOT NULL AUTO_INCREMENT,
  `id_lender_account` int(11) NOT NULL,
  `id_project` int(11) NOT NULL,
  `id_autobid` int(11) NOT NULL,
  `id_lender_wallet_line` int(11) NOT NULL,
  `amount` int(11) NOT NULL COMMENT 'x100',
  `rate` float NOT NULL,
  `ordre` int(11) NOT NULL,
  `status` tinyint(1) NOT NULL COMMENT '0 : encours 1 : OK 2 : NOK',
  `status_email_bid_ko` tinyint(1) NOT NULL COMMENT 'notif mail envoyé ou non pour les bids ko (0 : non envoyé | 1 : envoyé) | 3 : email non envoyé car choix du preteur',
  `checked` tinyint(1) NOT NULL COMMENT '1 : oui | 0 : non (deja passé dans le cron ou pas)',
  `added` datetime NOT NULL,
  `updated` datetime NOT NULL,
  PRIMARY KEY (`id_bid`),
  KEY `id_lender_account` (`id_lender_account`),
  KEY `id_project` (`id_project`)
) ENGINE=InnoDB AUTO_INCREMENT=173895 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `bids_logs`
--

DROP TABLE IF EXISTS `bids_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `bids_logs` (
  `id_bid_log` int(11) NOT NULL AUTO_INCREMENT,
  `id_project` int(11) NOT NULL,
  `debut` datetime NOT NULL,
  `fin` datetime NOT NULL,
  `nb_bids_encours` int(11) NOT NULL COMMENT 'Bids en cours avant le traitement',
  `nb_bids_ko` int(11) NOT NULL COMMENT 'Bids en cours passés en ko lors du traitement',
  `total_bids_ko` int(11) NOT NULL COMMENT 'Total de bids ko sur le projet',
  `total_bids` int(11) NOT NULL COMMENT 'Total de bids sur le projet',
  `added` datetime NOT NULL,
  `updated` datetime NOT NULL,
  PRIMARY KEY (`id_bid_log`),
  KEY `id_project` (`id_project`)
) ENGINE=InnoDB AUTO_INCREMENT=24114 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `blocs`
--

DROP TABLE IF EXISTS `blocs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `blocs` (
  `id_bloc` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL COMMENT 'Nom du bloc',
  `slug` varchar(255) NOT NULL COMMENT 'Identifiant permanent du bloc pour appeller le fichier qui sera du type : bloc_slug',
  `status` tinyint(1) NOT NULL COMMENT 'Statut du bloc (0 : offline | 1 : online)',
  `added` datetime NOT NULL COMMENT 'Date d''ajout',
  `updated` datetime NOT NULL COMMENT 'Date de modification',
  PRIMARY KEY (`id_bloc`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `blocs_elements`
--

DROP TABLE IF EXISTS `blocs_elements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `blocs_elements` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_bloc` int(11) NOT NULL COMMENT 'ID du bloc',
  `id_element` int(11) NOT NULL COMMENT 'ID de l''élément',
  `id_langue` varchar(2) NOT NULL,
  `value` text NOT NULL COMMENT 'Valeur de l''élément pour ce bloc',
  `complement` text NOT NULL,
  `status` tinyint(1) NOT NULL COMMENT 'Statut de l''élément (0 : offline | 1 : online)',
  `added` datetime NOT NULL COMMENT 'Date d''ajout',
  `updated` datetime NOT NULL COMMENT 'Date de modification',
  PRIMARY KEY (`id`),
  UNIQUE KEY `id_bloc` (`id_bloc`,`id_element`,`id_langue`),
  KEY `id_bloc_2` (`id_bloc`),
  KEY `id_element` (`id_element`)
) ENGINE=InnoDB AUTO_INCREMENT=2138 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `blocs_templates`
--

DROP TABLE IF EXISTS `blocs_templates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `blocs_templates` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_bloc` int(11) NOT NULL COMMENT 'ID du bloc pour ce template',
  `id_template` int(11) NOT NULL COMMENT 'ID du template',
  `position` enum('Haut','Droite','Bas','Gauche') NOT NULL COMMENT 'Position du bloc sur le template',
  `ordre` int(11) NOT NULL COMMENT 'Ordre du bloc sur le template',
  `status` tinyint(1) NOT NULL COMMENT 'Statut du bloc (0 : offline | 1 : online)',
  `added` datetime NOT NULL COMMENT 'Date d''ajout',
  `updated` datetime NOT NULL COMMENT 'Date de modification',
  PRIMARY KEY (`id`),
  UNIQUE KEY `id_bloc` (`id_bloc`,`id_template`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `clients`
--

DROP TABLE IF EXISTS `clients`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `clients` (
  `id_client` int(11) NOT NULL AUTO_INCREMENT,
  `hash` varchar(255) NOT NULL,
  `id_langue` varchar(5) NOT NULL,
  `id_partenaire` int(11) NOT NULL,
  `id_partenaire_subcode` int(11) NOT NULL,
  `id_facebook` varchar(45) NOT NULL,
  `id_linkedin` varchar(45) NOT NULL,
  `id_viadeo` varchar(45) NOT NULL,
  `id_twitter` varchar(45) NOT NULL,
  `civilite` enum('M.','Mme','Mlle') NOT NULL,
  `nom` varchar(255) NOT NULL,
  `nom_usage` varchar(255) NOT NULL,
  `prenom` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `fonction` varchar(255) NOT NULL,
  `naissance` date NOT NULL,
  `id_pays_naissance` int(11) NOT NULL,
  `ville_naissance` varchar(255) NOT NULL,
  `id_nationalite` int(11) NOT NULL,
  `telephone` varchar(255) NOT NULL,
  `mobile` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `secrete_question` varchar(255) NOT NULL,
  `secrete_reponse` varchar(255) NOT NULL,
  `type` tinyint(1) NOT NULL COMMENT '(prêteur)1 : physi 2 : morale 3 : physi etran 4 : morale etran',
  `status_depot_dossier` tinyint(4) NOT NULL,
  `etape_inscription_preteur` int(11) NOT NULL COMMENT 'etapes 1,2,3',
  `status_inscription_preteur` tinyint(1) NOT NULL,
  `status_pre_emp` tinyint(1) NOT NULL COMMENT '1 : preteur | 2 : emprunteur | 3 : les deux',
  `status_transition` tinyint(1) NOT NULL COMMENT '1 : en transition | 0 : ok',
  `cni_passeport` varchar(255) NOT NULL,
  `signature` varchar(255) NOT NULL,
  `source` varchar(255) NOT NULL,
  `source2` varchar(255) NOT NULL,
  `source3` varchar(255) NOT NULL,
  `slug_origine` varchar(255) NOT NULL,
  `origine` tinyint(1) NOT NULL COMMENT '1 : WS offre de Bienvenue',
  `optin1` tinyint(1) NOT NULL COMMENT '0: Offline | 1: Online',
  `optin2` tinyint(1) NOT NULL COMMENT '0: Offline | 1: Online',
  `status` tinyint(1) NOT NULL COMMENT '0: Offline | 1: Online',
  `added` datetime NOT NULL,
  `updated` datetime NOT NULL,
  `lastlogin` datetime NOT NULL,
  PRIMARY KEY (`id_client`),
  KEY `hash` (`hash`),
  KEY `email` (`email`(4))
) ENGINE=InnoDB AUTO_INCREMENT=33718 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `clients_140808`
--

DROP TABLE IF EXISTS `clients_140808`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `clients_140808` (
  `id_client` int(11) NOT NULL AUTO_INCREMENT,
  `hash` varchar(255) NOT NULL,
  `id_langue` varchar(5) NOT NULL,
  `id_partenaire` int(11) NOT NULL,
  `id_partenaire_subcode` int(11) NOT NULL,
  `id_facebook` varchar(45) NOT NULL,
  `id_linkedin` varchar(45) NOT NULL,
  `id_viadeo` varchar(45) NOT NULL,
  `id_twitter` varchar(45) NOT NULL,
  `civilite` enum('M.','Mme','Mlle') NOT NULL,
  `nom` varchar(255) NOT NULL,
  `nom_usage` varchar(255) NOT NULL,
  `prenom` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `fonction` varchar(255) NOT NULL,
  `naissance` date NOT NULL,
  `id_pays_naissance` int(11) NOT NULL,
  `ville_naissance` varchar(255) NOT NULL,
  `id_nationalite` int(11) NOT NULL,
  `telephone` varchar(255) NOT NULL,
  `mobile` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `secrete_question` varchar(255) NOT NULL,
  `secrete_reponse` varchar(255) NOT NULL,
  `type` tinyint(1) NOT NULL COMMENT '(prêteur)1 : physi 2 : morale 3 : physi etran 4 : morale etran',
  `status_depot_dossier` tinyint(4) NOT NULL,
  `etape_inscription_preteur` int(11) NOT NULL COMMENT 'etapes 1,2,3',
  `status_inscription_preteur` tinyint(1) NOT NULL,
  `status_pre_emp` tinyint(1) NOT NULL COMMENT '1 : preteur | 2 : emprunteur | 3 : les deux',
  `status_transition` tinyint(1) NOT NULL COMMENT '1 : en transition | 0 : ok',
  `cni_passeport` varchar(255) NOT NULL,
  `signature` varchar(255) NOT NULL,
  `optin1` tinyint(1) NOT NULL COMMENT '0: Offline | 1: Online',
  `optin2` tinyint(1) NOT NULL COMMENT '0: Offline | 1: Online',
  `status` tinyint(1) NOT NULL COMMENT '0: Offline | 1: Online',
  `added` datetime NOT NULL,
  `updated` datetime NOT NULL,
  `lastlogin` datetime NOT NULL,
  PRIMARY KEY (`id_client`),
  KEY `hash` (`hash`),
  KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=6475 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `clients_adresses`
--

DROP TABLE IF EXISTS `clients_adresses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `clients_adresses` (
  `id_adresse` int(11) NOT NULL AUTO_INCREMENT,
  `id_client` int(11) NOT NULL,
  `defaut` tinyint(1) NOT NULL DEFAULT '0' COMMENT '0: Non | 1: Oui adresse par defaut',
  `type` tinyint(1) NOT NULL COMMENT '0: Livraison | 1: Facturation',
  `nom_adresse` varchar(255) NOT NULL,
  `civilite` enum('M.','Mme','Mlle') NOT NULL,
  `nom` varchar(255) NOT NULL,
  `prenom` varchar(255) NOT NULL,
  `societe` varchar(255) NOT NULL,
  `adresse1` varchar(255) NOT NULL,
  `adresse2` varchar(255) NOT NULL,
  `adresse3` varchar(255) NOT NULL,
  `cp` varchar(255) NOT NULL,
  `ville` varchar(255) NOT NULL,
  `id_pays` int(11) NOT NULL,
  `telephone` varchar(255) NOT NULL,
  `mobile` varchar(255) NOT NULL,
  `commentaire` text NOT NULL,
  `meme_adresse_fiscal` tinyint(4) NOT NULL COMMENT 'Preteur particulier',
  `adresse_fiscal` varchar(255) NOT NULL COMMENT 'Preteur particulier',
  `ville_fiscal` varchar(255) NOT NULL COMMENT 'Preteur particulier',
  `cp_fiscal` varchar(255) NOT NULL COMMENT 'Preteur particulier',
  `id_pays_fiscal` int(11) NOT NULL,
  `status` tinyint(1) NOT NULL COMMENT '0: Offline | 1: Online',
  `added` datetime NOT NULL,
  `updated` datetime NOT NULL,
  PRIMARY KEY (`id_adresse`),
  KEY `id_client` (`id_client`),
  KEY `type` (`type`),
  KEY `defaut` (`defaut`)
) ENGINE=InnoDB AUTO_INCREMENT=33717 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `clients_gestion_mails_notif`
--

DROP TABLE IF EXISTS `clients_gestion_mails_notif`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `clients_gestion_mails_notif` (
  `id_clients_gestion_mails_notif` int(11) NOT NULL AUTO_INCREMENT,
  `id_client` int(11) NOT NULL,
  `id_notif` int(11) NOT NULL COMMENT 'table : clients_gestion_type_notif	',
  `id_project` int(11) NOT NULL COMMENT 'pour les notif nouveaux projets',
  `date_notif` datetime NOT NULL COMMENT 'date de repere pour le cron',
  `id_notification` int(11) NOT NULL COMMENT 'table notifications',
  `id_transaction` int(11) NOT NULL,
  `id_loan` int(11) NOT NULL COMMENT 'seulement pour les  offres acceptées',
  `immediatement` tinyint(1) NOT NULL COMMENT '0 : non envoyé | 1 : envoyé',
  `quotidienne` tinyint(1) NOT NULL COMMENT '0 : non envoyé | 1 : envoyé',
  `status_check_quotidienne` tinyint(1) NOT NULL,
  `hebdomadaire` tinyint(1) NOT NULL COMMENT '0 : non envoyé | 1 : envoyé',
  `status_check_hebdomadaire` tinyint(1) NOT NULL,
  `mensuelle` tinyint(1) NOT NULL COMMENT '0 : non envoyé | 1 : envoyé',
  `status_check_mensuelle` tinyint(1) NOT NULL,
  `added` datetime NOT NULL,
  `updated` datetime NOT NULL,
  PRIMARY KEY (`id_clients_gestion_mails_notif`),
  UNIQUE KEY `id_notification` (`id_notification`),
  KEY ` id_client` (`id_client`),
  KEY `id_notif` (`id_notif`),
  KEY `id_transaction` (`id_transaction`),
  KEY `id_project` (`id_project`),
  KEY `date_notif` (`date_notif`),
  KEY `status_check_quotidienne` (`status_check_quotidienne`),
  KEY `status_check_hebdomadaire` (`status_check_hebdomadaire`),
  KEY `status_check_mensuelle` (`status_check_mensuelle`)
) ENGINE=InnoDB AUTO_INCREMENT=824587 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `clients_gestion_notif_log`
--

DROP TABLE IF EXISTS `clients_gestion_notif_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `clients_gestion_notif_log` (
  `id_client_gestion_notif_log` int(11) NOT NULL AUTO_INCREMENT,
  `id_notif` int(11) NOT NULL,
  `type` varchar(50) NOT NULL COMMENT '1 : quotidien, 2 : hebod, 3 : mensuel',
  `debut` datetime NOT NULL,
  `fin` datetime NOT NULL,
  `added` datetime NOT NULL,
  `updated` datetime NOT NULL,
  PRIMARY KEY (`id_client_gestion_notif_log`)
) ENGINE=InnoDB AUTO_INCREMENT=2744 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `clients_gestion_notifications`
--

DROP TABLE IF EXISTS `clients_gestion_notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `clients_gestion_notifications` (
  `id_client` int(11) NOT NULL,
  `id_notif` int(11) NOT NULL COMMENT 'table : clients_gestion_type_notif',
  `immediatement` tinyint(1) NOT NULL,
  `quotidienne` tinyint(1) NOT NULL,
  `hebdomadaire` tinyint(1) NOT NULL,
  `mensuelle` tinyint(1) NOT NULL,
  `uniquement_notif` tinyint(1) NOT NULL,
  `added` datetime NOT NULL,
  `updated` datetime NOT NULL,
  PRIMARY KEY (`id_client`,`id_notif`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `clients_gestion_type_notif`
--

DROP TABLE IF EXISTS `clients_gestion_type_notif`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `clients_gestion_type_notif` (
  `id_client_gestion_type_notif` int(11) NOT NULL AUTO_INCREMENT,
  `nom` varchar(255) NOT NULL,
  `ordre` int(11) NOT NULL,
  PRIMARY KEY (`id_client_gestion_type_notif`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `clients_history`
--

DROP TABLE IF EXISTS `clients_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `clients_history` (
  `id_history` int(11) NOT NULL AUTO_INCREMENT,
  `id_client` int(11) NOT NULL,
  `type` tinyint(4) NOT NULL COMMENT '1 : preteur | 2 : emprunteur | 3 : les deux',
  `status` tinyint(1) NOT NULL COMMENT '1 : login | 2 : creation compte | 3 : depot dossier',
  `added` datetime NOT NULL,
  `updated` datetime NOT NULL,
  PRIMARY KEY (`id_history`),
  KEY `id_client` (`id_client`)
) ENGINE=InnoDB AUTO_INCREMENT=555281 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `clients_history_actions`
--

DROP TABLE IF EXISTS `clients_history_actions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `clients_history_actions` (
  `id_client_history_action` int(11) NOT NULL AUTO_INCREMENT,
  `id_form` int(11) NOT NULL,
  `nom_form` varchar(255) NOT NULL,
  `id_client` int(11) NOT NULL,
  `serialize` text NOT NULL,
  `added` datetime NOT NULL,
  `updated` datetime NOT NULL,
  PRIMARY KEY (`id_client_history_action`)
) ENGINE=InnoDB AUTO_INCREMENT=246372 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `clients_mandats`
--

DROP TABLE IF EXISTS `clients_mandats`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `clients_mandats` (
  `id_mandat` int(11) NOT NULL AUTO_INCREMENT,
  `id_client` int(11) NOT NULL,
  `id_project` int(11) NOT NULL COMMENT 'que pour emprunteur',
  `name` varchar(255) NOT NULL,
  `id_universign` varchar(255) NOT NULL,
  `url_universign` varchar(255) NOT NULL,
  `url_pdf` varchar(255) NOT NULL,
  `status` tinyint(1) NOT NULL COMMENT '0 : en cours | 1 : signé | 2 : annulé | 3 : fail',
  `updated` datetime NOT NULL,
  `added` datetime NOT NULL,
  PRIMARY KEY (`id_mandat`),
  KEY `id_client` (`id_client`)
) ENGINE=InnoDB AUTO_INCREMENT=201 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `clients_status`
--

DROP TABLE IF EXISTS `clients_status`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `clients_status` (
  `id_client_status` int(11) NOT NULL AUTO_INCREMENT,
  `label` varchar(255) NOT NULL,
  `status` int(11) NOT NULL,
  PRIMARY KEY (`id_client_status`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `clients_status_history`
--

DROP TABLE IF EXISTS `clients_status_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `clients_status_history` (
  `id_client_status_history` int(11) NOT NULL AUTO_INCREMENT,
  `id_client` int(11) NOT NULL,
  `id_client_status` int(11) NOT NULL,
  `content` text NOT NULL COMMENT 'contenu mail completude',
  `id_user` int(11) NOT NULL COMMENT '-1 : cron, -2 : fo',
  `numero_relance` int(11) NOT NULL COMMENT 'Sert pour le cron de relance completude',
  `added` datetime NOT NULL,
  `updated` datetime NOT NULL,
  PRIMARY KEY (`id_client_status_history`),
  KEY `id_client` (`id_client`),
  KEY `id_client_status` (`id_client_status`),
  KEY `id_user` (`id_user`)
) ENGINE=InnoDB AUTO_INCREMENT=18542 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `companies`
--

DROP TABLE IF EXISTS `companies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `companies` (
  `id_company` int(11) NOT NULL AUTO_INCREMENT,
  `id_client_owner` int(11) NOT NULL,
  `id_partenaire` int(11) NOT NULL,
  `id_partenaire_subcode` int(11) NOT NULL,
  `email_facture` varchar(255) NOT NULL,
  `name` text NOT NULL,
  `forme` varchar(255) NOT NULL,
  `siren` varchar(15) NOT NULL,
  `siret` varchar(14) NOT NULL,
  `iban` varchar(28) NOT NULL,
  `bic` varchar(100) NOT NULL,
  `execices_comptables` tinyint(1) NOT NULL COMMENT '1 : au moins trois exercices comptables | 0 : non',
  `rcs` varchar(45) NOT NULL,
  `tribunal_com` varchar(255) NOT NULL,
  `activite` varchar(255) NOT NULL,
  `lieu_exploi` varchar(255) NOT NULL,
  `tva` float NOT NULL,
  `capital` double NOT NULL,
  `date_creation` date NOT NULL,
  `adresse1` varchar(255) NOT NULL,
  `adresse2` varchar(255) NOT NULL,
  `zip` varchar(10) NOT NULL,
  `city` varchar(255) NOT NULL,
  `id_pays` int(11) NOT NULL,
  `phone` varchar(45) NOT NULL,
  `status_adresse_correspondance` tinyint(1) NOT NULL COMMENT '1 : meme adresse que le siege | 0 : pas la meme adresse',
  `status_client` tinyint(4) NOT NULL COMMENT '1 : dirigeant | 2 : beneficie d''une délégation de pouvoir | 3 : externe à l''entreprise',
  `status_conseil_externe_entreprise` int(11) NOT NULL,
  `preciser_conseil_externe_entreprise` varchar(255) NOT NULL COMMENT 'quand status_conseil_externe_entreprise = autre',
  `civilite_dirigeant` enum('M.','Mme','Mlle') NOT NULL COMMENT 'rempli si client n''est pas le dirigeant',
  `nom_dirigeant` varchar(255) NOT NULL COMMENT 'rempli si client n''est pas le dirigeant',
  `prenom_dirigeant` varchar(255) NOT NULL COMMENT 'rempli si client n''est pas le dirigeant',
  `fonction_dirigeant` varchar(255) NOT NULL COMMENT 'rempli si client n''est pas le dirigeant',
  `email_dirigeant` varchar(255) NOT NULL COMMENT 'rempli si client n''est pas le dirigeant',
  `phone_dirigeant` varchar(45) NOT NULL COMMENT 'rempli si client n''est pas le dirigeant',
  `sector` int(11) NOT NULL,
  `risk` varchar(45) NOT NULL,
  `altares_eligibility` varchar(255) NOT NULL,
  `altares_dateValeur` date NOT NULL,
  `altares_niveauRisque` varchar(255) NOT NULL,
  `altares_scoreVingt` int(11) NOT NULL,
  `added` datetime NOT NULL,
  `updated` datetime NOT NULL,
  PRIMARY KEY (`id_company`),
  KEY `id_client_owner` (`id_client_owner`)
) ENGINE=InnoDB AUTO_INCREMENT=14901 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `companies_actif_passif`
--

DROP TABLE IF EXISTS `companies_actif_passif`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `companies_actif_passif` (
  `id_actif_passif` int(11) NOT NULL AUTO_INCREMENT,
  `id_company` int(11) NOT NULL,
  `ordre` int(4) NOT NULL,
  `annee` year(4) NOT NULL,
  `immobilisations_corporelles` double NOT NULL,
  `immobilisations_incorporelles` double NOT NULL,
  `immobilisations_financieres` double NOT NULL,
  `stocks` int(11) NOT NULL,
  `creances_clients` double NOT NULL,
  `disponibilites` double NOT NULL,
  `valeurs_mobilieres_de_placement` double NOT NULL,
  `capitaux_propres` double NOT NULL,
  `provisions_pour_risques_et_charges` double NOT NULL,
  `amortissement_sur_immo` double NOT NULL,
  `dettes_financieres` double NOT NULL,
  `dettes_fournisseurs` double NOT NULL,
  `autres_dettes` double NOT NULL,
  `added` datetime NOT NULL,
  `updated` datetime NOT NULL,
  PRIMARY KEY (`id_actif_passif`),
  KEY `id_company` (`id_company`),
  KEY `annee` (`annee`)
) ENGINE=InnoDB AUTO_INCREMENT=47600 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `companies_bilans`
--

DROP TABLE IF EXISTS `companies_bilans`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `companies_bilans` (
  `id_bilan` int(11) NOT NULL AUTO_INCREMENT,
  `id_company` int(11) NOT NULL,
  `ca` double NOT NULL,
  `resultat_brute_exploitation` double NOT NULL,
  `resultat_exploitation` double NOT NULL,
  `investissements` double NOT NULL,
  `date` year(4) NOT NULL COMMENT 'annee bilan',
  `added` datetime NOT NULL,
  `updated` datetime NOT NULL,
  PRIMARY KEY (`id_bilan`),
  UNIQUE KEY `id_company` (`id_company`,`date`),
  KEY `date` (`date`)
) ENGINE=InnoDB AUTO_INCREMENT=217434 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `companies_details`
--

DROP TABLE IF EXISTS `companies_details`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `companies_details` (
  `id_company_detail` int(11) NOT NULL AUTO_INCREMENT,
  `id_company` int(11) NOT NULL,
  `date_dernier_bilan` date NOT NULL,
  `date_dernier_bilan_mois` int(11) NOT NULL,
  `date_dernier_bilan_annee` year(4) NOT NULL,
  `encours_actuel_dette_fianciere` double NOT NULL,
  `remb_a_venir_cette_annee` double NOT NULL,
  `remb_a_venir_annee_prochaine` double NOT NULL,
  `tresorie_dispo_actuellement` double NOT NULL,
  `autre_demandes_financements_prevues` double NOT NULL,
  `precisions` text NOT NULL,
  `decouverts_bancaires` double NOT NULL,
  `lignes_de_tresorerie` double NOT NULL,
  `affacturage` double NOT NULL,
  `escompte` double NOT NULL,
  `financement_dailly` double NOT NULL,
  `credit_de_tresorerie` double NOT NULL,
  `credit_bancaire_investissements_materiels` double NOT NULL,
  `credit_bancaire_investissements_immateriels` double NOT NULL,
  `rachat_entreprise_ou_titres` double NOT NULL,
  `credit_immobilier` double NOT NULL,
  `credit_bail_immobilier` double NOT NULL,
  `credit_bail` double NOT NULL,
  `location_avec_option_achat` double NOT NULL,
  `location_financiere` double NOT NULL,
  `location_longue_duree` double NOT NULL,
  `pret_oseo` double NOT NULL,
  `pret_participatif` double NOT NULL,
  `fichier_extrait_kbis` varchar(255) NOT NULL,
  `fichier_rib` varchar(255) NOT NULL,
  `fichier_delegation_pouvoir` varchar(255) NOT NULL,
  `fichier_logo_societe` varchar(255) NOT NULL,
  `fichier_photo_dirigeant` varchar(255) NOT NULL,
  `fichier_dernier_bilan_certifie` varchar(255) NOT NULL,
  `fichier_cni_passeport` varchar(255) NOT NULL,
  `fichier_derniere_liasse_fiscale` varchar(255) NOT NULL,
  `fichier_derniers_comptes_approuves` varchar(255) NOT NULL,
  `fichier_derniers_comptes_consolides_groupe` varchar(255) NOT NULL,
  `fichier_annexes_rapport_special_commissaire_compte` varchar(255) NOT NULL,
  `fichier_arret_comptable_recent` varchar(255) NOT NULL,
  `fichier_budget_exercice_en_cours_a_venir` varchar(255) NOT NULL,
  `fichier_notation_banque_france` varchar(255) NOT NULL,
  `fichier_autre_1` varchar(255) NOT NULL,
  `fichier_autre_2` varchar(255) NOT NULL,
  `fichier_autre_3` varchar(255) NOT NULL,
  `added` datetime NOT NULL,
  `updated` datetime NOT NULL,
  PRIMARY KEY (`id_company_detail`),
  UNIQUE KEY `id_company` (`id_company`)
) ENGINE=InnoDB AUTO_INCREMENT=13539 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `compteur_factures`
--

DROP TABLE IF EXISTS `compteur_factures`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `compteur_factures` (
  `id_compteur_facture` int(11) NOT NULL AUTO_INCREMENT,
  `id_project` int(11) NOT NULL,
  `ordre` int(11) NOT NULL,
  `added` datetime NOT NULL,
  `updated` datetime NOT NULL,
  PRIMARY KEY (`id_compteur_facture`)
) ENGINE=InnoDB AUTO_INCREMENT=1700 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `compteur_transferts`
--

DROP TABLE IF EXISTS `compteur_transferts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `compteur_transferts` (
  `id_compteur` int(11) NOT NULL AUTO_INCREMENT,
  `type` tinyint(1) NOT NULL COMMENT '1 : virement | 2 : prelevement',
  `ordre` int(11) NOT NULL,
  `added` datetime NOT NULL,
  `updated` datetime NOT NULL,
  PRIMARY KEY (`id_compteur`)
) ENGINE=InnoDB AUTO_INCREMENT=1307 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `convert_api_compteur`
--

DROP TABLE IF EXISTS `convert_api_compteur`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `convert_api_compteur` (
  `id_convert_api_compteur` int(11) NOT NULL AUTO_INCREMENT,
  `compteur` int(11) NOT NULL,
  `added` datetime NOT NULL,
  `updated` datetime NOT NULL,
  PRIMARY KEY (`id_convert_api_compteur`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `demande_contact`
--

DROP TABLE IF EXISTS `demande_contact`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `demande_contact` (
  `id_demande_contact` int(11) NOT NULL AUTO_INCREMENT,
  `demande` tinyint(4) NOT NULL COMMENT '1 : relastion presse | 2 : demande preteur | 3 : demande emprunteur | 4 : recrutement | 5 : autre',
  `preciser` varchar(255) NOT NULL COMMENT 'dans le cas ou la demande est 5 (autre)',
  `nom` varchar(255) NOT NULL,
  `prenom` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `telephone` varchar(45) NOT NULL,
  `message` text NOT NULL,
  `societe` varchar(255) NOT NULL,
  `added` datetime NOT NULL,
  `updated` datetime NOT NULL,
  PRIMARY KEY (`id_demande_contact`)
) ENGINE=InnoDB AUTO_INCREMENT=2742 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `documents`
--

DROP TABLE IF EXISTS `documents`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `documents` (
  `id_document` int(11) NOT NULL,
  `id_company` int(11) NOT NULL,
  `id_user` int(11) NOT NULL,
  `id_lender` int(11) NOT NULL,
  `id_loan` varchar(45) NOT NULL,
  `id_loanrequest` varchar(45) NOT NULL,
  `objet` varchar(45) NOT NULL,
  `title` text NOT NULL,
  `path` varchar(45) NOT NULL,
  `type` tinyint(4) NOT NULL,
  `status` tinyint(4) NOT NULL,
  `visibility` varchar(45) NOT NULL,
  `added` datetime NOT NULL,
  `updated` datetime NOT NULL,
  `validated` varchar(45) NOT NULL,
  PRIMARY KEY (`id_document`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `echeanciers`
--

DROP TABLE IF EXISTS `echeanciers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `echeanciers` (
  `id_echeancier` int(11) NOT NULL AUTO_INCREMENT,
  `id_lender` int(11) NOT NULL,
  `id_project` int(11) NOT NULL,
  `id_loan` int(11) NOT NULL,
  `ordre` int(11) NOT NULL,
  `montant` int(11) NOT NULL COMMENT 'x100',
  `capital` int(11) NOT NULL COMMENT 'x100',
  `interets` int(11) NOT NULL COMMENT 'x100',
  `commission` int(11) NOT NULL COMMENT 'x100',
  `tva` int(11) NOT NULL COMMENT '*100',
  `prelevements_obligatoires` float NOT NULL,
  `retenues_source` float NOT NULL,
  `csg` float NOT NULL,
  `prelevements_sociaux` float NOT NULL,
  `contributions_additionnelles` float NOT NULL,
  `prelevements_solidarite` float NOT NULL,
  `crds` float NOT NULL,
  `date_echeance` datetime NOT NULL,
  `date_echeance_reel` datetime NOT NULL,
  `status` int(11) NOT NULL COMMENT '0 : non remboursé | 1 : remboursé',
  `status_email_remb` tinyint(4) NOT NULL COMMENT '0 : non envoyé | 1 : envoyé',
  `date_echeance_emprunteur` datetime NOT NULL,
  `date_echeance_emprunteur_reel` datetime NOT NULL,
  `status_emprunteur` tinyint(1) NOT NULL COMMENT '0 : en cours | 1 : remb',
  `status_ra` int(11) NOT NULL COMMENT '0  non / 1 remboursé anticipé',
  `added` datetime NOT NULL,
  `updated` datetime NOT NULL,
  PRIMARY KEY (`id_echeancier`),
  KEY `id_lender` (`id_lender`),
  KEY `id_project` (`id_project`),
  KEY `id_loan` (`id_loan`),
  KEY `date_echeance_reel` (`date_echeance_reel`),
  KEY `date_echeance_emprunteur_reel` (`date_echeance_emprunteur_reel`),
  KEY `ordre` (`ordre`),
  KEY `status` (`status`),
  KEY `status_emprunteur` (`status_emprunteur`)
) ENGINE=InnoDB AUTO_INCREMENT=3303492 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `echeanciers_emprunteur`
--

DROP TABLE IF EXISTS `echeanciers_emprunteur`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `echeanciers_emprunteur` (
  `id_echeancier_emprunteur` int(11) NOT NULL AUTO_INCREMENT,
  `id_project` int(11) NOT NULL,
  `ordre` int(11) NOT NULL,
  `montant` int(11) NOT NULL,
  `capital` int(11) NOT NULL,
  `interets` int(11) NOT NULL,
  `commission` int(11) NOT NULL,
  `tva` int(11) NOT NULL,
  `date_echeance_emprunteur` datetime NOT NULL,
  `date_echeance_emprunteur_reel` datetime NOT NULL,
  `status_emprunteur` tinyint(1) NOT NULL,
  `status_ra` int(11) NOT NULL COMMENT '0  non / 1 remboursé anticipé',
  `added` datetime NOT NULL,
  `updated` datetime NOT NULL,
  PRIMARY KEY (`id_echeancier_emprunteur`),
  KEY `id_project` (`id_project`),
  KEY `date_echeance_emprunteur_reel` (`date_echeance_emprunteur_reel`),
  KEY `ordre` (`ordre`),
  KEY `status_emprunteur` (`status_emprunteur`)
) ENGINE=InnoDB AUTO_INCREMENT=7837 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `elements`
--

DROP TABLE IF EXISTS `elements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `elements` (
  `id_element` int(11) NOT NULL AUTO_INCREMENT,
  `id_template` int(11) NOT NULL COMMENT 'ID du template pour cet élément',
  `id_bloc` int(11) NOT NULL COMMENT 'ID du bloc pour cet élément',
  `name` varchar(255) NOT NULL COMMENT 'Nom de l''élément',
  `slug` varchar(255) NOT NULL COMMENT 'Slug de l''élément pour l''appeler dans les pages',
  `ordre` int(11) NOT NULL DEFAULT '0' COMMENT 'Ordre de l''élément sur le template ou le bloc, on commence à 0',
  `type_element` varchar(50) NOT NULL COMMENT 'Liste des types d''élément pour l''affichage du formulaire',
  `status` tinyint(1) NOT NULL COMMENT 'Statut de l''élément (0 : offline | 1 : online | 2: intouchable)',
  `added` datetime NOT NULL COMMENT 'Date d''ajout',
  `updated` datetime NOT NULL COMMENT 'Date de modification',
  PRIMARY KEY (`id_element`)
) ENGINE=InnoDB AUTO_INCREMENT=280 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `errors`
--

DROP TABLE IF EXISTS `errors`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `errors` (
  `id_error` int(11) NOT NULL AUTO_INCREMENT,
  `errid` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `trace` text NOT NULL,
  `session` text NOT NULL,
  `post` text NOT NULL,
  `server` text NOT NULL,
  `added` datetime NOT NULL,
  `updated` datetime NOT NULL,
  PRIMARY KEY (`id_error`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `etat_quotidien`
--

DROP TABLE IF EXISTS `etat_quotidien`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `etat_quotidien` (
  `id_etat_quotidien` int(11) NOT NULL AUTO_INCREMENT,
  `date` varchar(7) NOT NULL,
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `val` int(11) NOT NULL COMMENT '*100',
  `added` datetime NOT NULL,
  `updated` datetime NOT NULL,
  PRIMARY KEY (`id_etat_quotidien`),
  KEY `date` (`date`),
  KEY `name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=654 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `factures`
--

DROP TABLE IF EXISTS `factures`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `factures` (
  `id_facture` int(11) NOT NULL AUTO_INCREMENT,
  `num_facture` varchar(255) NOT NULL,
  `date` date NOT NULL,
  `id_company` int(11) NOT NULL,
  `id_project` int(11) NOT NULL,
  `ordre` int(11) NOT NULL,
  `type_commission` tinyint(1) NOT NULL COMMENT '1 : finance | 2 : remb',
  `commission` int(11) NOT NULL,
  `montant_ht` int(11) NOT NULL,
  `tva` int(11) NOT NULL,
  `montant_ttc` int(11) NOT NULL,
  `added` datetime NOT NULL,
  `updated` datetime NOT NULL,
  PRIMARY KEY (`id_facture`),
  KEY `id_company` (`id_company`)
) ENGINE=InnoDB AUTO_INCREMENT=1345 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `favoris`
--

DROP TABLE IF EXISTS `favoris`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `favoris` (
  `id_favori` int(11) NOT NULL AUTO_INCREMENT,
  `id_client` int(11) NOT NULL,
  `id_project` int(11) NOT NULL,
  `added` datetime NOT NULL,
  `updated` datetime NOT NULL,
  PRIMARY KEY (`id_favori`),
  UNIQUE KEY `id_client` (`id_client`,`id_project`)
) ENGINE=InnoDB AUTO_INCREMENT=16537 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `ifu`
--

DROP TABLE IF EXISTS `ifu`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ifu` (
  `id_ifu` int(11) NOT NULL AUTO_INCREMENT,
  `id_client` int(11) NOT NULL,
  `annee` int(11) NOT NULL,
  `nom` varchar(255) NOT NULL,
  `chemin` varchar(255) NOT NULL,
  `statut` int(11) NOT NULL COMMENT '0 : off / 1: on',
  `updated` datetime NOT NULL,
  `added` datetime NOT NULL,
  PRIMARY KEY (`id_ifu`),
  KEY `id_client` (`id_client`)
) ENGINE=InnoDB AUTO_INCREMENT=2170 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `indexage_suivi`
--

DROP TABLE IF EXISTS `indexage_suivi`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `indexage_suivi` (
  `id_indexage_suivi` int(11) NOT NULL AUTO_INCREMENT,
  `id_client` int(11) NOT NULL,
  `date_derniere_indexation` datetime NOT NULL,
  `deja_indexe` int(11) NOT NULL,
  `nb_entrees` int(11) NOT NULL COMMENT 'a titre informatif, le nombre d''entree enregistrees',
  `updated` datetime NOT NULL,
  `added` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_indexage_suivi`),
  KEY `id_client` (`id_client`)
) ENGINE=InnoDB AUTO_INCREMENT=5862 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `indexage_vos_operations`
--

DROP TABLE IF EXISTS `indexage_vos_operations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `indexage_vos_operations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_client` int(11) NOT NULL,
  `id_transaction` int(11) NOT NULL,
  `id_echeancier` int(11) NOT NULL,
  `id_projet` int(11) NOT NULL,
  `type_transaction` int(11) NOT NULL,
  `libelle_operation` varchar(255) NOT NULL,
  `bdc` int(11) NOT NULL COMMENT 'Bon de caisse (Id_loan)',
  `libelle_projet` varchar(255) NOT NULL,
  `date_operation` datetime NOT NULL,
  `solde` int(11) NOT NULL COMMENT 'X100',
  `montant_operation` int(11) NOT NULL COMMENT 'X100',
  `montant_capital` int(11) NOT NULL,
  `montant_interet` int(11) NOT NULL,
  `libelle_prelevement` varchar(255) NOT NULL,
  `montant_prelevement` int(11) NOT NULL,
  `updated` datetime NOT NULL,
  `added` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `id_client` (`id_client`,`id_transaction`),
  KEY `id_echeancier` (`id_echeancier`),
  KEY `bdc` (`bdc`),
  KEY `id_projet` (`id_projet`),
  KEY `type_transaction` (`type_transaction`)
) ENGINE=InnoDB AUTO_INCREMENT=735055 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `indexage_vos_operations_bu01-06-15`
--

DROP TABLE IF EXISTS `indexage_vos_operations_bu01-06-15`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `indexage_vos_operations_bu01-06-15` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_client` int(11) NOT NULL,
  `id_transaction` int(11) NOT NULL,
  `id_echeancier` int(11) NOT NULL,
  `id_projet` int(11) NOT NULL,
  `type_transaction` int(11) NOT NULL,
  `libelle_operation` varchar(255) NOT NULL,
  `bdc` int(11) NOT NULL COMMENT 'Bon de caisse (Id_loan)',
  `libelle_projet` varchar(255) NOT NULL,
  `date_operation` datetime NOT NULL,
  `solde` int(11) NOT NULL COMMENT 'X100',
  `montant_operation` int(11) NOT NULL COMMENT 'X100',
  `montant_capital` int(11) NOT NULL,
  `montant_interet` int(11) NOT NULL,
  `libelle_prelevement` varchar(255) NOT NULL,
  `montant_prelevement` int(11) NOT NULL,
  `updated` datetime NOT NULL,
  `added` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `id_client` (`id_client`,`id_transaction`),
  KEY `id_echeancier` (`id_echeancier`),
  KEY `bdc` (`bdc`),
  KEY `id_projet` (`id_projet`),
  KEY `type_transaction` (`type_transaction`)
) ENGINE=InnoDB AUTO_INCREMENT=588521 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `indexage_vos_operations_sav7mars`
--

DROP TABLE IF EXISTS `indexage_vos_operations_sav7mars`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `indexage_vos_operations_sav7mars` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_client` int(11) NOT NULL,
  `id_transaction` int(11) NOT NULL,
  `id_echeancier` int(11) NOT NULL,
  `id_projet` int(11) NOT NULL,
  `type_transaction` int(11) NOT NULL,
  `libelle_operation` varchar(255) NOT NULL,
  `bdc` int(11) NOT NULL COMMENT 'Bon de caisse (Id_loan)',
  `libelle_projet` varchar(255) NOT NULL,
  `date_operation` datetime NOT NULL,
  `solde` int(11) NOT NULL COMMENT 'X100',
  `montant_operation` int(11) NOT NULL COMMENT 'X100',
  `montant_capital` int(11) NOT NULL,
  `montant_interet` int(11) NOT NULL,
  `libelle_prelevement` varchar(255) NOT NULL,
  `montant_prelevement` int(11) NOT NULL,
  `updated` datetime NOT NULL,
  `added` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `id_client` (`id_client`,`id_transaction`),
  KEY `id_echeancier` (`id_echeancier`),
  KEY `bdc` (`bdc`),
  KEY `id_projet` (`id_projet`),
  KEY `type_transaction` (`type_transaction`)
) ENGINE=InnoDB AUTO_INCREMENT=464972 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `insee`
--

DROP TABLE IF EXISTS `insee`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `insee` (
  `id_insee` int(11) NOT NULL AUTO_INCREMENT,
  `CDC` int(1) DEFAULT NULL,
  `CHEFLIEU` int(1) DEFAULT NULL,
  `REG` int(2) DEFAULT NULL,
  `DEP` int(1) DEFAULT NULL,
  `COM` int(1) DEFAULT NULL COMMENT '3 chiffres a chaque fois, zero a rajouter devant',
  `AR` int(1) DEFAULT NULL,
  `CT` int(2) DEFAULT NULL,
  `TNCC` int(1) DEFAULT NULL,
  `ARTMAJ` varchar(4) DEFAULT NULL,
  `NCC` varchar(50) DEFAULT NULL,
  `ARTMIN` varchar(4) DEFAULT NULL,
  `NCCENR` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id_insee`),
  KEY `NCCENR` (`NCCENR`),
  KEY `NCC` (`NCC`)
) ENGINE=InnoDB AUTO_INCREMENT=36682 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `insee_pays`
--

DROP TABLE IF EXISTS `insee_pays`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `insee_pays` (
  `COG` varchar(5) DEFAULT NULL,
  `ACTUAL` int(1) DEFAULT NULL,
  `CAPAY` varchar(5) DEFAULT NULL,
  `CRPAY` int(5) DEFAULT NULL,
  `ANI` int(4) DEFAULT NULL,
  `LIBCOG` varchar(44) DEFAULT NULL,
  `LIBENR` varchar(54) DEFAULT NULL,
  `ANCNOM` varchar(20) DEFAULT NULL,
  KEY `COG` (`COG`),
  KEY `LIBCOG` (`LIBCOG`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `lenders_accounts`
--

DROP TABLE IF EXISTS `lenders_accounts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `lenders_accounts` (
  `id_lender_account` int(11) NOT NULL AUTO_INCREMENT,
  `id_client_owner` int(11) NOT NULL,
  `id_company_owner` int(11) NOT NULL,
  `exonere` tinyint(1) NOT NULL COMMENT '0 : non | 1 : oui',
  `debut_exoneration` date NOT NULL COMMENT 'si exoneration',
  `fin_exoneration` date NOT NULL COMMENT 'si exoneration',
  `iban` varchar(28) NOT NULL,
  `bic` varchar(100) NOT NULL,
  `origine_des_fonds` int(11) NOT NULL,
  `precision` varchar(255) NOT NULL,
  `id_partenaire` int(11) NOT NULL,
  `id_partenaire_subcode` int(11) NOT NULL,
  `status` tinyint(1) NOT NULL COMMENT '0:horsligne | 1:enligne',
  `type_transfert` tinyint(1) NOT NULL COMMENT '1 : virement | 2 : CB',
  `motif` varchar(50) NOT NULL,
  `fonds` int(4) NOT NULL COMMENT 'dans le cas d''un transfert de fonds par cb',
  `cni_passeport` tinyint(1) NOT NULL COMMENT '1 : cni | 2 : passeport',
  `fichier_cni_passeport` varchar(255) NOT NULL,
  `fichier_justificatif_domicile` varchar(255) NOT NULL,
  `fichier_rib` varchar(255) NOT NULL,
  `fichier_cni_passeport_dirigent` varchar(255) NOT NULL,
  `fichier_extrait_kbis` varchar(255) NOT NULL,
  `fichier_delegation_pouvoir` varchar(255) NOT NULL,
  `fichier_statuts` varchar(255) NOT NULL,
  `fichier_autre` varchar(255) NOT NULL,
  `fichier_document_fiscal` varchar(255) NOT NULL,
  `added` datetime NOT NULL,
  `updated` datetime NOT NULL,
  PRIMARY KEY (`id_lender_account`),
  KEY `id_company_owner` (`id_company_owner`),
  KEY `id_client_owner` (`id_client_owner`)
) ENGINE=InnoDB AUTO_INCREMENT=19391 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `lenders_imposition_history`
--

DROP TABLE IF EXISTS `lenders_imposition_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `lenders_imposition_history` (
  `id_lenders_imposition_history` int(11) NOT NULL AUTO_INCREMENT,
  `id_lender` int(11) NOT NULL,
  `exonere` tinyint(1) NOT NULL COMMENT '0 : non / 1 : oui',
  `resident_etranger` tinyint(1) NOT NULL COMMENT '0 : non | 1 : fr/etranger | 2 : non fr/etranger',
  `id_pays` int(11) NOT NULL COMMENT 'id pays fiscal',
  `id_user` int(11) NOT NULL,
  `added` datetime NOT NULL,
  `updated` datetime NOT NULL,
  PRIMARY KEY (`id_lenders_imposition_history`),
  KEY `id_lender` (`id_lender`)
) ENGINE=InnoDB AUTO_INCREMENT=8176 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `loans`
--

DROP TABLE IF EXISTS `loans`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `loans` (
  `id_loan` int(11) NOT NULL AUTO_INCREMENT,
  `id_bid` int(11) NOT NULL,
  `id_lender` int(11) NOT NULL,
  `id_project` int(11) NOT NULL,
  `id_partenaire` int(11) NOT NULL,
  `id_partenaire_subcode` int(11) NOT NULL,
  `id_country_juridiction` int(11) NOT NULL,
  `type` varchar(45) NOT NULL,
  `number_of_terms` int(11) NOT NULL,
  `amount` float NOT NULL,
  `rate` float NOT NULL,
  `status` tinyint(1) NOT NULL COMMENT '0 : ok | 1 : argent redonné car l''emprunteur a refusé le prêt',
  `en_attente_mail_rejet_envoye` int(11) NOT NULL COMMENT '0:non / 1: oui - tout est à 0 donc non, et quand on veut envoyer un mail on passe le loan en 1 et en 0 une fois le mail envoyé',
  `fichier_declarationContratPret` varchar(255) NOT NULL,
  `added` datetime NOT NULL,
  `updated` datetime NOT NULL,
  PRIMARY KEY (`id_loan`),
  KEY `id_bid` (`id_bid`,`id_project`),
  KEY `id_lender` (`id_lender`),
  KEY `id_project` (`id_project`)
) ENGINE=InnoDB AUTO_INCREMENT=66680 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `loggin_connection_admin`
--

DROP TABLE IF EXISTS `loggin_connection_admin`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `loggin_connection_admin` (
  `id_loggin_connection_admin` int(11) NOT NULL AUTO_INCREMENT,
  `id_user` int(11) NOT NULL,
  `nom_user` varchar(255) NOT NULL COMMENT 'conservé dans cette table au cas ou un user est supp après',
  `email` varchar(255) NOT NULL,
  `date_connexion` datetime NOT NULL,
  `ip` varchar(50) NOT NULL,
  `pays` varchar(255) NOT NULL,
  `updated` datetime NOT NULL,
  `added` datetime NOT NULL,
  PRIMARY KEY (`id_loggin_connection_admin`),
  KEY `id_user` (`id_user`)
) ENGINE=InnoDB AUTO_INCREMENT=11267 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `login_log`
--

DROP TABLE IF EXISTS `login_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `login_log` (
  `id_log_login` int(11) NOT NULL AUTO_INCREMENT,
  `pseudo` varchar(255) NOT NULL,
  `IP` varchar(255) NOT NULL,
  `date_action` datetime NOT NULL,
  `statut` tinyint(1) NOT NULL,
  `retour` varchar(255) NOT NULL,
  `added` datetime NOT NULL,
  `updated` datetime NOT NULL,
  PRIMARY KEY (`id_log_login`)
) ENGINE=InnoDB AUTO_INCREMENT=45576 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `mails_filer`
--

DROP TABLE IF EXISTS `mails_filer`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `mails_filer` (
  `id_filermails` int(11) NOT NULL AUTO_INCREMENT,
  `id_textemail` int(11) NOT NULL,
  `desabo` varchar(255) NOT NULL,
  `email_nmp` varchar(255) NOT NULL,
  `from` varchar(255) NOT NULL,
  `to` varchar(255) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `headers` text NOT NULL,
  `added` datetime NOT NULL,
  `updated` datetime NOT NULL,
  PRIMARY KEY (`id_filermails`)
) ENGINE=InnoDB AUTO_INCREMENT=1005710 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `mails_filer_backup`
--

DROP TABLE IF EXISTS `mails_filer_backup`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `mails_filer_backup` (
  `id_filermails` int(11) NOT NULL,
  `id_textemail` int(11) NOT NULL,
  `desabo` varchar(255) NOT NULL,
  `email_nmp` varchar(255) NOT NULL,
  `from` varchar(255) NOT NULL,
  `to` varchar(255) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `headers` text NOT NULL,
  `added` datetime NOT NULL,
  `updated` datetime NOT NULL,
  PRIMARY KEY (`id_filermails`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `mails_text`
--

DROP TABLE IF EXISTS `mails_text`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `mails_text` (
  `id_textemail` int(11) NOT NULL AUTO_INCREMENT,
  `type` varchar(255) NOT NULL,
  `lang` varchar(2) DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `exp_name` varchar(255) NOT NULL,
  `exp_email` varchar(255) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `content` longtext,
  `id_nmp` varchar(255) NOT NULL,
  `nmp_unique` varchar(255) NOT NULL,
  `nmp_secure` varchar(255) NOT NULL,
  `mode` tinyint(1) NOT NULL COMMENT '0 transac 1 Market',
  `added` datetime NOT NULL,
  `updated` datetime NOT NULL,
  PRIMARY KEY (`id_textemail`),
  UNIQUE KEY `type` (`type`,`lang`)
) ENGINE=InnoDB AUTO_INCREMENT=86 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `menus`
--

DROP TABLE IF EXISTS `menus`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `menus` (
  `id_menu` int(11) NOT NULL AUTO_INCREMENT,
  `nom` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `status` tinyint(5) NOT NULL COMMENT '0 : Hors ligne 1: En ligne',
  `added` datetime NOT NULL,
  `updated` datetime NOT NULL,
  PRIMARY KEY (`id_menu`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `nationalites`
--

DROP TABLE IF EXISTS `nationalites`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `nationalites` (
  `id_nationalite` int(11) NOT NULL,
  `code_pays` varchar(9) NOT NULL DEFAULT '',
  `etat` varchar(52) DEFAULT NULL,
  `fr_m` varchar(50) DEFAULT NULL,
  `fr_f` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id_nationalite`),
  UNIQUE KEY `code_pays` (`code_pays`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `nationalites_v2`
--

DROP TABLE IF EXISTS `nationalites_v2`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `nationalites_v2` (
  `id_nationalite` int(2) NOT NULL DEFAULT '0',
  `fr_f` varchar(255) DEFAULT NULL,
  `ordre` int(11) NOT NULL,
  PRIMARY KEY (`id_nationalite`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `nationalites_v2_old`
--

DROP TABLE IF EXISTS `nationalites_v2_old`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `nationalites_v2_old` (
  `id_nationalite` int(2) NOT NULL DEFAULT '0',
  `fr_f` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id_nationalite`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `newsletters`
--

DROP TABLE IF EXISTS `newsletters`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `newsletters` (
  `id_newsletter` int(11) NOT NULL AUTO_INCREMENT,
  `id_langue` varchar(50) NOT NULL,
  `email` varchar(255) NOT NULL,
  `nom` varchar(255) NOT NULL,
  `prenom` varchar(255) NOT NULL,
  `status` tinyint(1) NOT NULL COMMENT '0: Désabonné | 1: Abonné',
  `added` datetime NOT NULL,
  `updated` datetime NOT NULL,
  PRIMARY KEY (`id_newsletter`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `nmp`
--

DROP TABLE IF EXISTS `nmp`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `nmp` (
  `id_nmp` int(11) NOT NULL AUTO_INCREMENT,
  `serialize_content` text NOT NULL,
  `date` date NOT NULL,
  `mailto` varchar(255) NOT NULL,
  `reponse` text NOT NULL,
  `erreur` text NOT NULL,
  `status` tinyint(1) NOT NULL COMMENT '0 pending 1 send 2 error 3 obiwan',
  `date_sent` varchar(255) NOT NULL,
  `added` datetime NOT NULL,
  `updated` datetime NOT NULL,
  PRIMARY KEY (`id_nmp`),
  KEY `status` (`status`)
) ENGINE=InnoDB AUTO_INCREMENT=982498 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `nmp_backup`
--

DROP TABLE IF EXISTS `nmp_backup`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `nmp_backup` (
  `id_nmp` int(11) NOT NULL,
  `serialize_content` text NOT NULL,
  `date` date NOT NULL,
  `mailto` varchar(255) NOT NULL,
  `reponse` text NOT NULL,
  `erreur` text NOT NULL,
  `status` tinyint(1) NOT NULL COMMENT '0 pending 1 send 2 error 3 obiwan',
  `date_sent` varchar(255) NOT NULL,
  `added` datetime NOT NULL,
  `updated` datetime NOT NULL,
  PRIMARY KEY (`id_nmp`),
  KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `nmp_desabo`
--

DROP TABLE IF EXISTS `nmp_desabo`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `nmp_desabo` (
  `id_desabo` int(11) NOT NULL AUTO_INCREMENT,
  `id_client` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `id_textemail` int(11) NOT NULL,
  `raison` varchar(255) NOT NULL,
  `commentaire` text NOT NULL,
  `added` datetime NOT NULL,
  `updated` datetime NOT NULL,
  PRIMARY KEY (`id_desabo`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `notifications`
--

DROP TABLE IF EXISTS `notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `notifications` (
  `id_notification` int(11) NOT NULL AUTO_INCREMENT,
  `id_lender` int(11) NOT NULL,
  `type` int(11) NOT NULL COMMENT '1 : rejet | 2 : remb | 3 : offres placées | 4 : acceptée | 5 : conf alim virement | 6 : conf alim CB | 7 : retrait | 8 : new projet',
  `id_project` int(11) NOT NULL,
  `id_bid` int(11) NOT NULL,
  `amount` int(11) NOT NULL COMMENT '*100',
  `status` tinyint(1) NOT NULL,
  `added` datetime NOT NULL,
  `updated` datetime NOT NULL,
  PRIMARY KEY (`id_notification`),
  KEY `id_lender` (`id_lender`)
) ENGINE=InnoDB AUTO_INCREMENT=1020124 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `offres_bienvenues`
--

DROP TABLE IF EXISTS `offres_bienvenues`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `offres_bienvenues` (
  `id_offre_bienvenue` int(11) NOT NULL AUTO_INCREMENT,
  `montant` int(11) NOT NULL COMMENT '*100',
  `montant_limit` int(11) NOT NULL COMMENT '*100',
  `debut` date NOT NULL,
  `fin` date NOT NULL,
  `id_user` int(11) NOT NULL,
  `status` tinyint(1) NOT NULL COMMENT '0 online | 1 offline',
  `added` datetime NOT NULL,
  `updated` datetime NOT NULL,
  PRIMARY KEY (`id_offre_bienvenue`),
  KEY `id_user` (`id_user`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `offres_bienvenues_details`
--

DROP TABLE IF EXISTS `offres_bienvenues_details`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `offres_bienvenues_details` (
  `id_offre_bienvenue_detail` int(11) NOT NULL AUTO_INCREMENT,
  `id_offre_bienvenue` int(11) NOT NULL,
  `motif` varchar(255) NOT NULL,
  `id_client` int(11) NOT NULL,
  `id_bid` int(11) NOT NULL,
  `id_bid_remb` int(11) NOT NULL,
  `montant` int(11) NOT NULL COMMENT '*100',
  `status` tinyint(1) NOT NULL COMMENT '0 : Non utilisé | 1 : Prété | 2 : Annulé',
  `type` int(11) NOT NULL COMMENT '0 : offres | 1 : decoupe | 2 : remb',
  `added` datetime NOT NULL,
  `updated` datetime NOT NULL,
  PRIMARY KEY (`id_offre_bienvenue_detail`),
  KEY `id_client` (`id_client`),
  KEY `id_offre_bienvenue` (`id_offre_bienvenue`)
) ENGINE=InnoDB AUTO_INCREMENT=1951 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `offres_parrains_filleuls`
--

DROP TABLE IF EXISTS `offres_parrains_filleuls`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `offres_parrains_filleuls` (
  `id_offre_parrain_filleul` int(11) NOT NULL AUTO_INCREMENT,
  `montant_parrain` int(11) NOT NULL COMMENT '*100',
  `montant_filleul` int(11) NOT NULL COMMENT '*100',
  `limite_montant_gains_parrains` int(11) NOT NULL COMMENT 'montant limite des gains des parrains(*100)',
  `parrain_limit_filleul` int(11) NOT NULL COMMENT 'nb max de filleul par parrain',
  `debut` date NOT NULL,
  `fin` date NOT NULL,
  `id_user` int(11) NOT NULL,
  `status` tinyint(1) NOT NULL COMMENT '0 online | 1 offline',
  `added` datetime NOT NULL,
  `updated` datetime NOT NULL,
  PRIMARY KEY (`id_offre_parrain_filleul`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `parrainages`
--

DROP TABLE IF EXISTS `parrainages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `parrainages` (
  `id_parrainage` int(11) NOT NULL AUTO_INCREMENT,
  `id_client` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `status` tinyint(1) NOT NULL COMMENT '0 En attente | 1 Validé | 2 mail envoyé (car traitement ds le cron)',
  `added` datetime NOT NULL,
  `updated` datetime NOT NULL,
  PRIMARY KEY (`id_parrainage`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `parrains_filleuls`
--

DROP TABLE IF EXISTS `parrains_filleuls`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `parrains_filleuls` (
  `id_parrain_filleul` int(11) NOT NULL AUTO_INCREMENT,
  `id_parrain` int(11) NOT NULL COMMENT 'id_client',
  `gains_parrain` int(11) NOT NULL COMMENT '*100',
  `id_filleul` int(11) NOT NULL COMMENT 'id_client',
  `gains_filleul` int(11) NOT NULL COMMENT '*100',
  `status` tinyint(1) NOT NULL COMMENT ' 1: bid accepté | 0 : non',
  `etat` tinyint(1) NOT NULL COMMENT '0 : non attribué | 1 : attribué | 2 : rejeté',
  `added` datetime NOT NULL,
  `updated` datetime NOT NULL,
  PRIMARY KEY (`id_parrain_filleul`),
  UNIQUE KEY `id_filleul` (`id_filleul`),
  KEY `id_parrain` (`id_parrain`,`id_filleul`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `parrains_filleuls_mouvements`
--

DROP TABLE IF EXISTS `parrains_filleuls_mouvements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `parrains_filleuls_mouvements` (
  `id_parrain_filleul_mouvement` int(11) NOT NULL AUTO_INCREMENT,
  `id_parrain_filleul` int(11) NOT NULL,
  `id_client` int(11) NOT NULL,
  `type_preteur` int(11) NOT NULL COMMENT '1 : parrain | 2 : filleul',
  `montant` int(11) NOT NULL COMMENT '*100',
  `id_bid` int(11) NOT NULL,
  `id_bid_remb` int(11) NOT NULL,
  `status` tinyint(1) NOT NULL COMMENT '0 : Non utilisé | 1 : Prété',
  `type` tinyint(1) NOT NULL COMMENT '0 : offres | 1 : decoupe | 2 : remb',
  `added` datetime NOT NULL,
  `updated` datetime NOT NULL,
  PRIMARY KEY (`id_parrain_filleul_mouvement`),
  KEY `id_client` (`id_client`),
  KEY `id_parrain_filleul` (`id_parrain_filleul`)
) ENGINE=MyISAM AUTO_INCREMENT=15 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `partenaires`
--

DROP TABLE IF EXISTS `partenaires`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `partenaires` (
  `id_partenaire` int(11) NOT NULL AUTO_INCREMENT,
  `nom` varchar(255) NOT NULL,
  `id_user` int(11) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `hash` varchar(255) NOT NULL,
  `id_type` int(11) NOT NULL COMMENT 'table partenaires_types',
  `id_media` int(11) NOT NULL COMMENT 'table partenaires_medias',
  `status` tinyint(1) NOT NULL COMMENT '0 : Hors ligne 1: En ligne',
  `css` varchar(255) NOT NULL,
  `sql` varchar(255) NOT NULL,
  `domain` varchar(255) NOT NULL,
  `added` datetime NOT NULL,
  `updated` datetime NOT NULL,
  PRIMARY KEY (`id_partenaire`)
) ENGINE=InnoDB AUTO_INCREMENT=159 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `partenaires_clics`
--

DROP TABLE IF EXISTS `partenaires_clics`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `partenaires_clics` (
  `id_partenaire` int(11) NOT NULL,
  `date` date NOT NULL,
  `ip_adress` varchar(45) NOT NULL,
  `nb_clics` int(11) NOT NULL,
  `added` datetime NOT NULL,
  `updated` datetime NOT NULL,
  PRIMARY KEY (`id_partenaire`,`date`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `partenaires_medias`
--

DROP TABLE IF EXISTS `partenaires_medias`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `partenaires_medias` (
  `id_media` int(11) NOT NULL AUTO_INCREMENT,
  `nom` varchar(255) NOT NULL,
  `status` tinyint(1) NOT NULL COMMENT '0 : Hors ligne 1: En ligne',
  `added` datetime NOT NULL,
  `updated` datetime NOT NULL,
  PRIMARY KEY (`id_media`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `partenaires_subcodes`
--

DROP TABLE IF EXISTS `partenaires_subcodes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `partenaires_subcodes` (
  `id_partenaire_subcode` int(11) NOT NULL AUTO_INCREMENT,
  `id_partenaire` int(11) NOT NULL,
  `partenaire_subcode` varchar(255) NOT NULL,
  `added` datetime NOT NULL,
  `updated` datetime NOT NULL,
  PRIMARY KEY (`id_partenaire_subcode`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `partenaires_types`
--

DROP TABLE IF EXISTS `partenaires_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `partenaires_types` (
  `id_type` int(11) NOT NULL AUTO_INCREMENT,
  `nom` varchar(255) NOT NULL,
  `status` tinyint(1) NOT NULL COMMENT '0 : Hors ligne 1: En ligne',
  `added` datetime NOT NULL,
  `updated` datetime NOT NULL,
  PRIMARY KEY (`id_type`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `pays`
--

DROP TABLE IF EXISTS `pays`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pays` (
  `id_pays` int(11) NOT NULL AUTO_INCREMENT,
  `id_langue` varchar(50) NOT NULL,
  `fr` varchar(255) NOT NULL,
  `en` varchar(255) NOT NULL,
  `id_zone` int(11) NOT NULL,
  `added` datetime NOT NULL,
  `updated` datetime NOT NULL,
  PRIMARY KEY (`id_pays`),
  UNIQUE KEY `id_pays` (`id_pays`,`id_zone`)
) ENGINE=InnoDB AUTO_INCREMENT=249 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `pays_v2`
--

DROP TABLE IF EXISTS `pays_v2`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pays_v2` (
  `id_pays` int(11) NOT NULL AUTO_INCREMENT,
  `fr` varchar(255) DEFAULT NULL,
  `iso` varchar(2) NOT NULL,
  `ordre` int(11) NOT NULL,
  PRIMARY KEY (`id_pays`)
) ENGINE=InnoDB AUTO_INCREMENT=199 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `prelevements`
--

DROP TABLE IF EXISTS `prelevements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `prelevements` (
  `id_prelevement` int(11) NOT NULL AUTO_INCREMENT,
  `id_client` int(11) NOT NULL,
  `id_transaction` int(11) NOT NULL,
  `id_project` int(11) NOT NULL,
  `motif` varchar(50) NOT NULL,
  `montant` int(11) NOT NULL COMMENT '*100',
  `bic` varchar(100) NOT NULL,
  `iban` varchar(28) NOT NULL,
  `type_prelevement` tinyint(1) NOT NULL COMMENT '1 : Permanent | 2 : Ponctuel',
  `jour_prelevement` int(11) NOT NULL,
  `type` tinyint(1) NOT NULL COMMENT '1 : preteur | 2 : emprunteur',
  `num_prelevement` int(11) NOT NULL COMMENT 'pour remb emprunteur',
  `status` tinyint(1) NOT NULL COMMENT '0 :en cours | 1 : envoyé | 2 : validé | 3 : terminé | 4 : temporairement bloqué',
  `date_execution_demande_prelevement` date NOT NULL COMMENT 'date J-n avant date de prelevemnt (pour les remb emprunteur)',
  `date_echeance_emprunteur` date NOT NULL,
  `added_xml` datetime NOT NULL,
  `added` datetime NOT NULL,
  `updated` datetime NOT NULL,
  PRIMARY KEY (`id_prelevement`)
) ENGINE=InnoDB AUTO_INCREMENT=7273 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `prescripteurs`
--

DROP TABLE IF EXISTS `prescripteurs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `prescripteurs` (
  `id_prescritpeur` int(11) NOT NULL AUTO_INCREMENT,
  `id_client` int(11) NOT NULL,
  `id_enseigne` int(11) NOT NULL,
  `id_entite` int(11) NOT NULL,
  `type_depot_dossier` int(11) NOT NULL,
  `updated` datetime NOT NULL,
  `added` datetime NOT NULL,
  PRIMARY KEY (`id_prescritpeur`),
  KEY `id_client` (`id_client`,`id_entite`),
  KEY `id_enseigne` (`id_enseigne`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `prices_items`
--

DROP TABLE IF EXISTS `prices_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `prices_items` (
  `id_price_item` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(45) NOT NULL,
  `price` varchar(45) NOT NULL,
  `percentage` varchar(45) NOT NULL,
  `text_explanation` varchar(45) NOT NULL,
  PRIMARY KEY (`id_price_item`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `projects`
--

DROP TABLE IF EXISTS `projects`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `projects` (
  `id_project` int(11) NOT NULL AUTO_INCREMENT,
  `slug` varchar(255) NOT NULL,
  `id_company` int(11) NOT NULL,
  `id_partenaire` int(11) NOT NULL,
  `id_partenaire_subcode` int(11) NOT NULL,
  `amount` float NOT NULL,
  `status_solde` tinyint(1) NOT NULL COMMENT '0 : nok | 1 : ok',
  `period` int(11) NOT NULL COMMENT 'nombre de mois (si 1000000 = je ne sais pas)',
  `title` varchar(255) NOT NULL,
  `title_bo` varchar(255) NOT NULL,
  `photo_projet` varchar(255) NOT NULL,
  `lien_video` varchar(255) NOT NULL,
  `comments` text NOT NULL COMMENT 'commentaires de l''emprunteur',
  `nature_project` text NOT NULL,
  `objectif_loan` text NOT NULL COMMENT 'Objectif du crédit',
  `presentation_company` text NOT NULL,
  `means_repayment` text NOT NULL COMMENT 'moyens de remboursement prevu',
  `type` tinyint(4) NOT NULL,
  `target_rate` varchar(5) NOT NULL,
  `stand_by` tinyint(1) NOT NULL COMMENT '1 : stand by | 0 : non stand by',
  `id_analyste` int(11) NOT NULL,
  `date_publication` date NOT NULL,
  `date_publication_full` datetime NOT NULL,
  `date_retrait` date NOT NULL,
  `date_retrait_full` datetime NOT NULL,
  `date_fin` datetime NOT NULL,
  `create_bo` tinyint(1) NOT NULL COMMENT '0 : fo | 1 : bo',
  `risk` varchar(2) NOT NULL,
  `remb_auto` tinyint(4) NOT NULL COMMENT '0 : oui | 1 : non',
  `status` tinyint(1) NOT NULL COMMENT '0 : en ligne | 1 : hors ligne',
  `display` tinyint(1) NOT NULL COMMENT '0 : on affiche | 1 on affiche pas',
  `added` datetime NOT NULL,
  `updated` datetime NOT NULL,
  PRIMARY KEY (`id_project`),
  KEY `id_company` (`id_company`),
  KEY `slug` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=14407 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `projects_check`
--

DROP TABLE IF EXISTS `projects_check`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `projects_check` (
  `id_project_check` int(11) NOT NULL AUTO_INCREMENT,
  `id_project` int(11) NOT NULL,
  `status` tinyint(1) NOT NULL COMMENT '1 : ok | 2 : ko',
  `added` datetime NOT NULL,
  `updated` datetime NOT NULL,
  PRIMARY KEY (`id_project_check`)
) ENGINE=InnoDB AUTO_INCREMENT=126 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `projects_comments`
--

DROP TABLE IF EXISTS `projects_comments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `projects_comments` (
  `id_project_comment` int(11) NOT NULL AUTO_INCREMENT,
  `id_project` int(11) NOT NULL,
  `content` text NOT NULL,
  `status` tinyint(4) NOT NULL,
  `added` datetime NOT NULL,
  `updated` datetime NOT NULL,
  PRIMARY KEY (`id_project_comment`)
) ENGINE=InnoDB AUTO_INCREMENT=1757 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `projects_details`
--

DROP TABLE IF EXISTS `projects_details`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `projects_details` (
  `id_project_detail` int(11) NOT NULL AUTO_INCREMENT,
  `id_project` int(11) NOT NULL,
  `id_lang` int(11) NOT NULL,
  `type` tinyint(4) NOT NULL,
  `content` text NOT NULL,
  `added` datetime NOT NULL,
  `updated` datetime NOT NULL,
  PRIMARY KEY (`id_project_detail`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `projects_notes`
--

DROP TABLE IF EXISTS `projects_notes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `projects_notes` (
  `id_project_notes` int(11) NOT NULL AUTO_INCREMENT,
  `id_project` int(11) NOT NULL,
  `performance_fianciere` float NOT NULL,
  `structure` float NOT NULL,
  `rentabilite` float NOT NULL,
  `tresorerie` float NOT NULL,
  `marche_opere` float NOT NULL,
  `global` float NOT NULL,
  `individuel` float NOT NULL,
  `qualite_moyen_infos_financieres` float NOT NULL,
  `notation_externe` float NOT NULL,
  `avis` text NOT NULL,
  `note` float NOT NULL,
  `avis_comite` text NOT NULL,
  `added` datetime NOT NULL,
  `updated` datetime NOT NULL,
  PRIMARY KEY (`id_project_notes`),
  UNIQUE KEY `id_project` (`id_project`)
) ENGINE=InnoDB AUTO_INCREMENT=505 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `projects_pouvoir`
--

DROP TABLE IF EXISTS `projects_pouvoir`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `projects_pouvoir` (
  `id_pouvoir` int(11) NOT NULL AUTO_INCREMENT,
  `id_project` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `id_universign` varchar(255) NOT NULL,
  `url_universign` varchar(255) NOT NULL,
  `url_pdf` varchar(255) NOT NULL,
  `status` tinyint(1) NOT NULL,
  `status_remb` tinyint(1) NOT NULL COMMENT '0 : no valid pour remb | 1 : valid',
  `updated` datetime NOT NULL,
  `added` datetime NOT NULL,
  PRIMARY KEY (`id_pouvoir`),
  KEY `id_project` (`id_project`)
) ENGINE=InnoDB AUTO_INCREMENT=217 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `projects_remb`
--

DROP TABLE IF EXISTS `projects_remb`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `projects_remb` (
  `id_project_remb` int(11) NOT NULL AUTO_INCREMENT,
  `id_project` int(11) NOT NULL,
  `ordre` int(11) NOT NULL,
  `date_remb_emprunteur_reel` datetime NOT NULL,
  `date_remb_preteurs` datetime NOT NULL,
  `date_remb_preteurs_reel` datetime NOT NULL,
  `status` tinyint(1) NOT NULL COMMENT '0 : non remb preteurs / 1 : remb aux preteurs / 23 : remb emp rejeté | 4 : remb auto desactivé',
  `added` datetime NOT NULL,
  `updated` datetime NOT NULL,
  PRIMARY KEY (`id_project_remb`),
  KEY `id_project` (`id_project`),
  KEY `ordre` (`ordre`)
) ENGINE=InnoDB AUTO_INCREMENT=917 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `projects_remb_log`
--

DROP TABLE IF EXISTS `projects_remb_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `projects_remb_log` (
  `id_project_remb_log` int(11) NOT NULL AUTO_INCREMENT,
  `id_project` int(11) NOT NULL,
  `ordre` int(11) NOT NULL,
  `debut` datetime NOT NULL,
  `fin` datetime NOT NULL,
  `montant_remb_net` int(11) NOT NULL COMMENT '*100',
  `etat` int(11) NOT NULL COMMENT '*100',
  `nb_pret_remb` int(11) NOT NULL,
  `added` datetime NOT NULL,
  `updated` datetime NOT NULL,
  PRIMARY KEY (`id_project_remb_log`),
  KEY `id_project` (`id_project`),
  KEY `ordre` (`ordre`)
) ENGINE=InnoDB AUTO_INCREMENT=111 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `projects_status`
--

DROP TABLE IF EXISTS `projects_status`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `projects_status` (
  `id_project_status` int(11) NOT NULL AUTO_INCREMENT,
  `label` varchar(255) NOT NULL,
  `status` int(11) NOT NULL,
  PRIMARY KEY (`id_project_status`)
) ENGINE=InnoDB AUTO_INCREMENT=25 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `projects_status_history`
--

DROP TABLE IF EXISTS `projects_status_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `projects_status_history` (
  `id_project_status_history` int(11) NOT NULL AUTO_INCREMENT,
  `id_project` int(11) NOT NULL,
  `id_project_status` int(11) NOT NULL,
  `id_user` int(11) NOT NULL COMMENT '-1 : cron, -2 : fo',
  `added` datetime NOT NULL,
  `updated` datetime NOT NULL,
  PRIMARY KEY (`id_project_status_history`),
  KEY `id_project` (`id_project`),
  KEY `id_project_status` (`id_project_status`),
  KEY `id_user` (`id_user`)
) ENGINE=InnoDB AUTO_INCREMENT=27271 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `promotions`
--

DROP TABLE IF EXISTS `promotions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `promotions` (
  `id_code` int(11) NOT NULL AUTO_INCREMENT,
  `type` enum('Remise','Pourcentage') NOT NULL,
  `code` varchar(50) NOT NULL,
  `from` date NOT NULL,
  `to` date NOT NULL,
  `value` float NOT NULL,
  `seuil` float NOT NULL,
  `fdp` tinyint(1) NOT NULL DEFAULT '0' COMMENT '0: FDP Payant | 1: FDP Gratuit',
  `id_tree` varchar(255) NOT NULL COMMENT 'liste des categories pour avoir le code',
  `id_produit` varchar(255) NOT NULL COMMENT 'liste des produits pour avoir le code',
  `id_tree2` varchar(255) NOT NULL,
  `id_produit2` varchar(255) NOT NULL,
  `nb_minimum2` int(11) NOT NULL,
  `id_groupe` int(11) NOT NULL,
  `id_client` int(11) NOT NULL,
  `id_produit_kdo` int(11) NOT NULL COMMENT 'id du produit offert',
  `nb_utilisations` int(11) NOT NULL,
  `nb_minimum` int(11) NOT NULL,
  `plus_cher` tinyint(1) NOT NULL COMMENT '0 rien | 1 on offre le plus cher des produits',
  `moins_cher` tinyint(1) NOT NULL COMMENT '0 rien | 1 on offre le moins cher',
  `duree` int(11) NOT NULL COMMENT 'Pour les temoins la duree en jour de la promo',
  `id_promo` int(11) NOT NULL COMMENT 'id d''un code promo de type template qui sera envoyé au client',
  `premiere_cmde` tinyint(1) NOT NULL COMMENT '0 non 1 oui pour la 1ere commande du client',
  `status` tinyint(1) NOT NULL COMMENT '0: Offline | 1: Online | 2 Temoins 3 auto',
  `added` datetime NOT NULL,
  `updated` datetime NOT NULL,
  PRIMARY KEY (`id_code`),
  UNIQUE KEY `code` (`code`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `prospects`
--

DROP TABLE IF EXISTS `prospects`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `prospects` (
  `id_prospect` int(11) NOT NULL AUTO_INCREMENT,
  `nom` text NOT NULL,
  `prenom` text NOT NULL,
  `email` varchar(255) NOT NULL,
  `id_langue` varchar(3) NOT NULL,
  `source` varchar(255) NOT NULL,
  `source2` varchar(255) NOT NULL,
  `source3` varchar(255) NOT NULL,
  `slug_origine` varchar(255) NOT NULL,
  `added` datetime NOT NULL,
  `updated` datetime NOT NULL,
  PRIMARY KEY (`id_prospect`)
) ENGINE=InnoDB AUTO_INCREMENT=21921 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `queries`
--

DROP TABLE IF EXISTS `queries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `queries` (
  `id_query` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `sql` text NOT NULL,
  `paging` int(11) NOT NULL,
  `executions` int(11) NOT NULL,
  `executed` datetime NOT NULL,
  `cms` varchar(50) NOT NULL,
  `added` datetime NOT NULL,
  `updated` datetime NOT NULL,
  PRIMARY KEY (`id_query`)
) ENGINE=InnoDB AUTO_INCREMENT=44 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `receptions`
--

DROP TABLE IF EXISTS `receptions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `receptions` (
  `id_reception` int(11) NOT NULL AUTO_INCREMENT,
  `motif` varchar(255) NOT NULL,
  `montant` int(11) NOT NULL COMMENT '*100',
  `type` tinyint(1) NOT NULL COMMENT '1 : prelevement | 2 : virement',
  `remb_anticipe` int(11) NOT NULL COMMENT '0:non / 1: oui',
  `status_virement` int(11) NOT NULL COMMENT '1 : recu | 2 : émis | 3 : rejet',
  `status_prelevement` tinyint(1) NOT NULL COMMENT '2 : émis | 3 : rejete/impaye',
  `status_bo` tinyint(1) NOT NULL COMMENT '0 : recu | 1 : attr manu | 2 : attr auto | 3 : rejeté | 4 : rejet',
  `remb` tinyint(1) NOT NULL COMMENT '0 : non | 1 : oui',
  `id_client` int(11) NOT NULL,
  `id_project` int(11) NOT NULL,
  `ligne` text NOT NULL,
  `added` datetime NOT NULL,
  `updated` datetime NOT NULL,
  PRIMARY KEY (`id_reception`)
) ENGINE=InnoDB AUTO_INCREMENT=7971 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `redirections`
--

DROP TABLE IF EXISTS `redirections`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `redirections` (
  `id_langue` varchar(5) NOT NULL,
  `from_slug` varchar(255) NOT NULL,
  `to_slug` varchar(255) NOT NULL,
  `type` int(11) NOT NULL,
  `status` tinyint(1) NOT NULL,
  `added` datetime NOT NULL,
  `updated` datetime NOT NULL,
  PRIMARY KEY (`id_langue`,`from_slug`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `routages`
--

DROP TABLE IF EXISTS `routages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `routages` (
  `id_routage` int(11) NOT NULL AUTO_INCREMENT,
  `id_langue` varchar(10) NOT NULL,
  `ctrl_url` varchar(255) NOT NULL,
  `fct_url` varchar(255) NOT NULL,
  `ctrl_projet` varchar(255) NOT NULL,
  `fct_projet` varchar(255) NOT NULL,
  `statut` tinyint(4) NOT NULL,
  `added` datetime NOT NULL,
  `updated` datetime NOT NULL,
  PRIMARY KEY (`id_routage`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `se_log`
--

DROP TABLE IF EXISTS `se_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `se_log` (
  `id_log` int(11) NOT NULL AUTO_INCREMENT,
  `keyword` text NOT NULL,
  `ip` varchar(20) NOT NULL,
  `nb_results` int(11) NOT NULL,
  `added` datetime NOT NULL,
  `updated` datetime NOT NULL,
  PRIMARY KEY (`id_log`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `se_matches`
--

DROP TABLE IF EXISTS `se_matches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `se_matches` (
  `id_word` int(11) NOT NULL,
  `id_object` int(11) NOT NULL,
  `object_type` tinyint(1) NOT NULL COMMENT '0 = produit / 1 = page',
  PRIMARY KEY (`id_word`,`id_object`,`object_type`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `se_words`
--

DROP TABLE IF EXISTS `se_words`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `se_words` (
  `id_word` int(11) NOT NULL AUTO_INCREMENT,
  `id_langue` varchar(2) NOT NULL,
  `word` varchar(255) NOT NULL,
  PRIMARY KEY (`word`),
  KEY `id_word` (`id_word`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `settings`
--

DROP TABLE IF EXISTS `settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `settings` (
  `id_setting` int(11) NOT NULL AUTO_INCREMENT,
  `type` varchar(255) NOT NULL COMMENT 'Type du parametre',
  `id_template` int(11) NOT NULL,
  `value` text NOT NULL COMMENT 'Valeur du parametre',
  `status` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'Statut du parametre (0 : offline | 1 : online | 2 : Intouchable)',
  `cms` varchar(50) NOT NULL,
  `added` datetime NOT NULL COMMENT 'Date d''ajout',
  `updated` datetime NOT NULL COMMENT 'Date de modification',
  PRIMARY KEY (`id_setting`),
  UNIQUE KEY `type` (`type`,`id_template`)
) ENGINE=InnoDB AUTO_INCREMENT=130 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `system_logs`
--

DROP TABLE IF EXISTS `system_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `system_logs` (
  `id_system_log` int(11) NOT NULL,
  `id_individual` int(11) NOT NULL,
  `id_lender_account` int(11) NOT NULL,
  `date` datetime NOT NULL,
  `ip` varchar(45) NOT NULL,
  `id_action` int(11) NOT NULL,
  PRIMARY KEY (`id_system_log`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `templates`
--

DROP TABLE IF EXISTS `templates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `templates` (
  `id_template` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL COMMENT 'Nom du template',
  `slug` varchar(255) NOT NULL COMMENT 'Identifiant permanent du template pour appeller le fichier qui sera du type : template_slug',
  `type` tinyint(1) NOT NULL DEFAULT '0' COMMENT '0: Template tree | 1: Template produit',
  `status` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'Statut du template (0 : offline | 1 : online)',
  `affichage` tinyint(4) NOT NULL COMMENT '0 : Affichage Normal 1 page fantome ou popup etc...',
  `added` datetime NOT NULL COMMENT 'Date d''ajout',
  `updated` datetime NOT NULL COMMENT 'Date de modification',
  PRIMARY KEY (`id_template`)
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `terms_for_companies`
--

DROP TABLE IF EXISTS `terms_for_companies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `terms_for_companies` (
  `id_term_for_company` int(11) NOT NULL AUTO_INCREMENT,
  `id_project` int(11) NOT NULL,
  `id_company` int(11) NOT NULL,
  `fees` float NOT NULL,
  `sum_amount` float NOT NULL,
  `sum_capital` float NOT NULL,
  `sum_interests` float NOT NULL,
  `added` datetime NOT NULL,
  `updated` datetime NOT NULL,
  PRIMARY KEY (`id_term_for_company`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `terms_of_loans`
--

DROP TABLE IF EXISTS `terms_of_loans`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `terms_of_loans` (
  `id_term_of_loan` int(11) NOT NULL,
  `id_term_for_company` int(11) NOT NULL,
  `id_loan` int(11) NOT NULL,
  `num` int(11) NOT NULL,
  `amount` float NOT NULL,
  `date` date NOT NULL,
  `status` tinyint(4) NOT NULL,
  `capital` float NOT NULL,
  `interests` float NOT NULL,
  `added` datetime NOT NULL,
  `updated` datetime NOT NULL,
  PRIMARY KEY (`id_term_of_loan`),
  KEY `id_term_for_company` (`id_term_for_company`,`id_loan`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `textes`
--

DROP TABLE IF EXISTS `textes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `textes` (
  `id_texte` int(11) NOT NULL AUTO_INCREMENT,
  `id_langue` varchar(2) NOT NULL,
  `section` varchar(255) NOT NULL,
  `nom` varchar(255) NOT NULL,
  `texte` text NOT NULL,
  `added` datetime NOT NULL,
  `updated` datetime NOT NULL,
  PRIMARY KEY (`id_texte`),
  KEY `section` (`section`)
) ENGINE=InnoDB AUTO_INCREMENT=1073 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `transactions`
--

DROP TABLE IF EXISTS `transactions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `transactions` (
  `id_transaction` int(11) NOT NULL AUTO_INCREMENT,
  `id_panier` int(11) NOT NULL,
  `id_backpayline` int(11) NOT NULL,
  `id_offre_bienvenue_detail` int(11) NOT NULL,
  `id_parrain_filleul` int(11) NOT NULL,
  `id_virement` int(11) NOT NULL,
  `id_prelevement` int(11) NOT NULL,
  `id_echeancier` int(11) NOT NULL COMMENT 'dans le cas d''un remb preteur',
  `id_echeancier_emprunteur` int(11) NOT NULL,
  `id_bid_remb` int(11) NOT NULL,
  `id_loan_remb` int(11) NOT NULL,
  `id_project` int(11) NOT NULL,
  `id_client` int(11) NOT NULL,
  `id_partenaire` int(11) NOT NULL,
  `id_livraison` int(11) NOT NULL,
  `id_facturation` int(11) NOT NULL,
  `id_type` int(11) NOT NULL COMMENT 'Type de FDP',
  `fdp` int(11) NOT NULL COMMENT 'x100',
  `montant` int(11) NOT NULL COMMENT 'FDP et Promo inclus x100',
  `montant_unilend` int(11) NOT NULL COMMENT '*100',
  `montant_etat` int(11) NOT NULL COMMENT '*100',
  `montant_reduc` int(11) NOT NULL COMMENT '*100',
  `id_langue` varchar(50) NOT NULL,
  `date_transaction` datetime NOT NULL,
  `type_paiement` varchar(255) NOT NULL COMMENT '0 : VISA | 3: MASTERCARD | 1 : Auto | 2: AMEX',
  `status` tinyint(1) NOT NULL COMMENT 'Statut du paiement 0: NOK 1: OK',
  `etat` tinyint(1) NOT NULL COMMENT '0: En attente 1: Validée 2: Expédiée 3: Annulée',
  `transaction` tinyint(1) NOT NULL COMMENT '1 : physique | 2 : virtuelle',
  `type_transaction` tinyint(2) NOT NULL COMMENT '1: inscrip pretr | 2: ench | 3: alim cb | 4:alim vir | 5: remb pretr | 6: remb empr |7 : Alim prelevt | 8: retrait pretr | 9: vir empr | 10: remb Unilend | 11: vir Unilend | 12: vir fiscale | 13: R com | 14: R pretr',
  `display` tinyint(1) NOT NULL COMMENT '0 affiché en FO | 1 Non affiché en FO',
  `ip_client` varchar(255) NOT NULL,
  `serialize_paniers` text NOT NULL,
  `serialize_paniers_produits` text NOT NULL,
  `serialize_paniers_promos` text NOT NULL,
  `serialize_paniers_cadeaux` text NOT NULL,
  `serialize_payline` text NOT NULL,
  `added` datetime NOT NULL,
  `updated` datetime NOT NULL,
  `civilite_liv` enum('M.','Mme','Mlle') NOT NULL,
  `nom_liv` varchar(255) NOT NULL,
  `prenom_liv` varchar(255) NOT NULL,
  `societe_liv` varchar(255) NOT NULL,
  `adresse1_liv` varchar(255) NOT NULL,
  `adresse2_liv` varchar(255) NOT NULL,
  `adresse3_liv` varchar(255) NOT NULL,
  `cp_liv` varchar(255) NOT NULL,
  `ville_liv` varchar(255) NOT NULL,
  `id_pays_liv` int(11) NOT NULL,
  `civilite_fac` enum('M.','Mme','Mlle') NOT NULL,
  `nom_fac` varchar(255) NOT NULL,
  `prenom_fac` varchar(255) NOT NULL,
  `societe_fac` varchar(255) NOT NULL,
  `adresse1_fac` varchar(255) NOT NULL,
  `adresse2_fac` varchar(255) NOT NULL,
  `adresse3_fac` varchar(255) NOT NULL,
  `cp_fac` varchar(255) NOT NULL,
  `ville_fac` varchar(255) NOT NULL,
  `id_pays_fac` int(11) NOT NULL,
  `colis` text NOT NULL COMMENT 'numero de suivi',
  PRIMARY KEY (`id_transaction`),
  KEY `id_client` (`id_client`),
  KEY `id_partenaire` (`id_partenaire`),
  KEY `status` (`status`),
  KEY `etat` (`etat`),
  KEY `id_echeancier` (`id_echeancier`),
  KEY `type_transaction` (`type_transaction`)
) ENGINE=InnoDB AUTO_INCREMENT=733318 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `transactions_types`
--

DROP TABLE IF EXISTS `transactions_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `transactions_types` (
  `id_transaction_type` int(11) NOT NULL AUTO_INCREMENT,
  `nom` varchar(255) NOT NULL,
  PRIMARY KEY (`id_transaction_type`)
) ENGINE=InnoDB AUTO_INCREMENT=24 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tree`
--

DROP TABLE IF EXISTS `tree`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tree` (
  `id_tree` int(11) NOT NULL,
  `id_langue` varchar(2) NOT NULL,
  `id_parent` int(11) NOT NULL COMMENT 'ID de la rubrique parente : 0 pour la Home et à partir de 1 pour le reste',
  `id_template` int(11) NOT NULL COMMENT 'ID du template lié à la page',
  `id_user` int(11) NOT NULL COMMENT 'ID de l''utilisateur qui a rédigé l''article',
  `arbo` tinyint(1) NOT NULL COMMENT '0 : izicom | 1 : preteur | 2 : emprunteur',
  `title` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL COMMENT 'Permalink de la page que l''on va rendre modifiable',
  `img_menu` varchar(255) NOT NULL COMMENT 'Image pour le menu',
  `video` varchar(255) NOT NULL,
  `menu_title` varchar(255) NOT NULL COMMENT 'Titre pour les menus',
  `meta_title` varchar(255) NOT NULL COMMENT 'Title de la balise META',
  `meta_description` text NOT NULL COMMENT 'Description pour la balise META',
  `meta_keywords` text NOT NULL COMMENT 'Mots clés pour la balise META',
  `ordre` int(11) NOT NULL DEFAULT '0' COMMENT 'Ordre de la page dans le Tree, on part de 0',
  `status` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'Statut de la page (0 : offline | 1 : online)',
  `status_menu` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'Statut de la page dans la navigation principale du site (0 : invisible | 1 : visible)',
  `prive` tinyint(1) NOT NULL DEFAULT '0' COMMENT '0: Public | 1: Private',
  `indexation` tinyint(1) NOT NULL DEFAULT '1' COMMENT '0 pas d''indexation dans les moteurs 1 : oui',
  `added` datetime NOT NULL COMMENT 'Date d''ajout',
  `updated` datetime NOT NULL COMMENT 'Date de modification',
  `canceled` datetime NOT NULL COMMENT 'Date de suppression',
  PRIMARY KEY (`id_tree`,`id_langue`),
  KEY `id_parent` (`id_parent`),
  KEY `id_template` (`id_template`),
  KEY `id_tree` (`id_tree`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tree_elements`
--

DROP TABLE IF EXISTS `tree_elements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tree_elements` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_tree` int(11) NOT NULL COMMENT 'ID de la page',
  `id_element` int(11) NOT NULL COMMENT 'ID de l''element',
  `id_langue` varchar(2) NOT NULL,
  `value` longtext NOT NULL COMMENT 'Valeur de l''élément pour cette page',
  `complement` text NOT NULL,
  `status` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'Statut de l''élément sur la page (0 : offline | 1 : Online)',
  `added` datetime NOT NULL COMMENT 'Date d''ajout',
  `updated` datetime NOT NULL COMMENT 'Date de modification',
  PRIMARY KEY (`id`),
  UNIQUE KEY `id_tree_2` (`id_tree`,`id_element`,`id_langue`),
  KEY `id_element` (`id_element`),
  KEY `id_tree_3` (`id_tree`)
) ENGINE=InnoDB AUTO_INCREMENT=88967 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tree_menu`
--

DROP TABLE IF EXISTS `tree_menu`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tree_menu` (
  `id` int(11) NOT NULL,
  `id_langue` varchar(2) NOT NULL,
  `id_menu` int(11) NOT NULL,
  `nom` varchar(255) NOT NULL,
  `value` varchar(255) NOT NULL,
  `complement` varchar(255) NOT NULL,
  `target` enum('_self','_blank','_top','_parent') NOT NULL,
  `ordre` int(11) NOT NULL DEFAULT '0' COMMENT 'Ordre de la page dans le menu, on commence de 0',
  `status` tinyint(1) NOT NULL COMMENT '0 : Hors ligne 1: En ligne',
  `added` datetime NOT NULL COMMENT 'Date d''ajout',
  `updated` datetime NOT NULL COMMENT 'Date de modification',
  PRIMARY KEY (`id`,`id_langue`),
  UNIQUE KEY `id_langue` (`id_langue`,`id_menu`,`nom`,`value`,`complement`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `type_financial_operations`
--

DROP TABLE IF EXISTS `type_financial_operations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `type_financial_operations` (
  `id_type_financial_operation` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(45) NOT NULL,
  `status` int(11) NOT NULL,
  PRIMARY KEY (`id_type_financial_operation`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `id_user` int(11) NOT NULL AUTO_INCREMENT,
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
  `lastlogin` datetime NOT NULL COMMENT 'Date de la dernière connexion',
  PRIMARY KEY (`id_user`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=30 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `users_history`
--

DROP TABLE IF EXISTS `users_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users_history` (
  `id_user_history` int(11) NOT NULL AUTO_INCREMENT,
  `id_form` int(11) NOT NULL,
  `nom_form` varchar(255) NOT NULL,
  `id_user` int(11) NOT NULL,
  `serialize` text NOT NULL,
  `added` datetime NOT NULL,
  `updated` datetime NOT NULL,
  PRIMARY KEY (`id_user_history`)
) ENGINE=InnoDB AUTO_INCREMENT=39186 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `users_types`
--

DROP TABLE IF EXISTS `users_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users_types` (
  `id_user_type` int(11) NOT NULL AUTO_INCREMENT,
  `label` varchar(252) NOT NULL,
  `added` datetime NOT NULL,
  `updated` datetime NOT NULL,
  PRIMARY KEY (`id_user_type`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `users_types_zones`
--

DROP TABLE IF EXISTS `users_types_zones`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users_types_zones` (
  `id_user_type_zone` int(11) NOT NULL AUTO_INCREMENT,
  `id_user_type` int(11) NOT NULL,
  `id_zone` int(11) NOT NULL,
  `added` datetime NOT NULL,
  `updated` datetime NOT NULL,
  PRIMARY KEY (`id_user_type_zone`),
  KEY `id_user_type` (`id_user_type`),
  KEY `id_zone` (`id_zone`)
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `users_zones`
--

DROP TABLE IF EXISTS `users_zones`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users_zones` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_user` int(11) NOT NULL COMMENT 'ID de l''utilisateur',
  `id_zone` int(11) NOT NULL COMMENT 'ID de la zone autorisée',
  `added` datetime NOT NULL COMMENT 'Date d''ajout',
  `updated` datetime NOT NULL COMMENT 'Date de modification',
  PRIMARY KEY (`id`),
  UNIQUE KEY `id_user` (`id_user`,`id_zone`)
) ENGINE=InnoDB AUTO_INCREMENT=419 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `villes`
--

DROP TABLE IF EXISTS `villes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `villes` (
  `id_ville` int(11) NOT NULL AUTO_INCREMENT,
  `ville` varchar(255) NOT NULL,
  `insee` int(11) NOT NULL,
  `cp` int(11) NOT NULL,
  `num_departement` int(11) NOT NULL,
  `departement` varchar(255) NOT NULL,
  `added` datetime NOT NULL,
  `updated` datetime NOT NULL,
  PRIMARY KEY (`id_ville`)
) ENGINE=InnoDB AUTO_INCREMENT=38950 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `virements`
--

DROP TABLE IF EXISTS `virements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `virements` (
  `id_virement` int(11) NOT NULL AUTO_INCREMENT,
  `id_client` int(11) NOT NULL,
  `id_project` int(11) NOT NULL,
  `id_transaction` int(11) NOT NULL,
  `montant` int(11) NOT NULL COMMENT 'x100',
  `motif` varchar(150) NOT NULL,
  `type` tinyint(1) NOT NULL COMMENT '1 : preteur | 2 : emprunteur | 4 : Unilend',
  `status` tinyint(1) NOT NULL COMMENT '0 : en cours | 1 : envoyé | 2 : validé',
  `added_xml` datetime NOT NULL,
  `added` datetime NOT NULL,
  `updated` datetime NOT NULL,
  PRIMARY KEY (`id_virement`)
) ENGINE=InnoDB AUTO_INCREMENT=5777 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `wallets_lines`
--

DROP TABLE IF EXISTS `wallets_lines`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `wallets_lines` (
  `id_wallet_line` int(11) NOT NULL AUTO_INCREMENT,
  `id_lender` int(11) NOT NULL,
  `id_company` int(11) NOT NULL,
  `type_financial_operation` int(11) NOT NULL,
  `id_transaction` int(11) NOT NULL,
  `id_bid_remb` int(11) NOT NULL,
  `id_term_of_loan` int(11) NOT NULL,
  `id_loan` int(11) NOT NULL,
  `id_project` int(11) NOT NULL,
  `id_term_for_company` int(11) NOT NULL,
  `type` tinyint(4) NOT NULL COMMENT '1 : physique | 2 : virtuelle',
  `amount` int(11) NOT NULL COMMENT 'x100',
  `display` tinyint(1) NOT NULL COMMENT '0 on affiche | 1 on affiche pas',
  `status` tinyint(4) NOT NULL,
  `added` datetime NOT NULL,
  `updated` datetime NOT NULL,
  PRIMARY KEY (`id_wallet_line`),
  KEY `id_lender` (`id_lender`),
  KEY `id_transaction` (`id_transaction`)
) ENGINE=InnoDB AUTO_INCREMENT=725084 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `watchlist`
--

DROP TABLE IF EXISTS `watchlist`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `watchlist` (
  `id_watchlist` int(11) NOT NULL AUTO_INCREMENT,
  `id_lender_account` int(11) NOT NULL,
  `id_project` int(11) NOT NULL,
  `added` datetime NOT NULL,
  `updated` datetime NOT NULL,
  PRIMARY KEY (`id_watchlist`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `zones`
--

DROP TABLE IF EXISTS `zones`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `zones` (
  `id_zone` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL COMMENT 'Nom de la zone protégée',
  `slug` varchar(255) NOT NULL COMMENT 'Permalink de la zone pour l''appel du control d''accès',
  `status` tinyint(1) NOT NULL COMMENT 'Statut de la zone (0 : offline | 1 : online)',
  `cms` varchar(50) NOT NULL,
  `added` datetime NOT NULL COMMENT 'Date d''ajout',
  `updated` datetime NOT NULL COMMENT 'Date de modification',
  PRIMARY KEY (`id_zone`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping routines for database 'unilend'
--
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2015-07-28  3:24:16
