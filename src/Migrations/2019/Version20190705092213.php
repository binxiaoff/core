<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Unilend\Migrations\ContainerAwareMigration;
use Unilend\Migrations\Traits\FlushTranslationCacheTrait;

final class Version20190705092213 extends ContainerAwareMigration
{
    use FlushTranslationCacheTrait;

    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('INSERT INTO tree (id_tree, id_langue, id_parent, id_template, id_user, title, slug, img_menu, menu_title, meta_title, meta_description, meta_keywords, ordre, status, status_menu, prive, indexation, added, updated) VALUES (96, \'fr\', 22, 5, 1, \'Conditions générales d‘utilisation\', \'conditions-generales-d-utilisation-96\', \'\', \'Conditions générales d‘utilisation\', \'Conditions générales d‘utilisation\', \'Conditions générales d‘utilisation\', \'\', 3, 1, 1, 0, 1, NOW(), NOW())');
        $this->addSql('DELETE FROM elements WHERE id_element in (144, 145, 146, 147, 148, 149, 150)');
        $this->addSql('INSERT INTO tree_elements (id_tree, id_element, id_langue, value, complement, status, added, updated) VALUES (96, 142, \'fr\', \'
<p>
	Les pr&eacute;sentes conditions g&eacute;n&eacute;rales SaaS Cr&eacute;dit Agricole Lending Services (ci-apr&egrave;s d&eacute;sign&eacute;es les &laquo;&nbsp;<strong>Conditions G&eacute;n&eacute;rales</strong>&nbsp;&raquo;) r&eacute;gissent les relations entre la soci&eacute;t&eacute; Cr&eacute;dit Agricole Lending Services, soci&eacute;t&eacute; <strong>[&bull;]</strong> au capital de <strong>[&bull;]</strong> euros, immatricul&eacute;e au registre du commerce et des soci&eacute;t&eacute;s de <strong>[&bull;]</strong> sous le num&eacute;ro <strong>[&bull;]</strong>, dont le si&egrave;ge social est situ&eacute; au <strong>[&bull;]</strong> (ci-apr&egrave;s d&eacute;sign&eacute;e &laquo;&nbsp;<strong>Cr&eacute;dit Agricole Lending Services</strong>&nbsp;&raquo; ou &laquo;&nbsp;<strong>CALS</strong>&nbsp;&raquo;) et toute personne morale qui utilise la Plateforme CALS (ci-apr&egrave;s d&eacute;sign&eacute;e le &laquo;&nbsp;<strong>Client</strong>&nbsp;&raquo;).</p>
<p>
	CALS et le Client sont ci-apr&egrave;s d&eacute;nomm&eacute;s individuellement une &laquo;&nbsp;<strong>Partie&nbsp;</strong>&raquo; et collectivement les &laquo;&nbsp;<strong>Parties&nbsp;</strong>&raquo;.</p>
<div>
	<div>
		<h2>
			Article 1 D&eacute;finitions</h2>
	</div>
	<p>
		Les termes et expressions dont la premi&egrave;re lettre de chaque mot est en majuscule ont, au sein du Contrat, la signification qui leur est attribu&eacute;e ci-apr&egrave;s, qu&rsquo;ils soient utilis&eacute;s au singulier ou au pluriel.</p>
	<ul>
		<li>
			&laquo;&nbsp;Agent&nbsp;&raquo; d&eacute;signe l&rsquo;Etablissement Autoris&eacute; ou l&rsquo;Utilisateur Invit&eacute; faisant partie du Pool Bancaire auquel il a &eacute;t&eacute; confi&eacute; un r&ocirc;le de suivi de l&rsquo;Op&eacute;ration. Il est l&rsquo;interlocuteur de l&rsquo;Emprunteur pendant l&rsquo;ex&eacute;cution de la Convention de Cr&eacute;dit.</li>
		<li>
			&laquo;&nbsp;Convention de Sous-Participation&nbsp;&raquo; d&eacute;signe le document contractuel r&eacute;dig&eacute; et sign&eacute; dans le cadre d&rsquo;une op&eacute;ration de Syndication Bancaire sur le March&eacute; Secondaire, entre l&rsquo;Etablissement Autoris&eacute; ou l&rsquo;Utilisateur Invit&eacute; c&eacute;dant et le ou les Etablissement(s) Autoris&eacute;(s) et/ou l(es) Utilisateur(s) Invit&eacute;(s) cessionnaire(s).</li>
		<li>
			&laquo;&nbsp;Arrangeur&nbsp;&raquo; d&eacute;signe l&rsquo;Etablissement Autoris&eacute;, interm&eacute;diaire de l&rsquo;Emprunteur, qui est charg&eacute; d&rsquo;organiser un financement et trouver un groupement d&rsquo;Etablissements&nbsp; Autoris&eacute;s et/ou d&rsquo;Utilisateurs Invit&eacute;s qui accepte de financer l&rsquo;Op&eacute;ration. L&rsquo;Arrangeur est responsable de la constitution du Pool Bancaire, de la collecte des KYC et de la n&eacute;gociation et de l&rsquo;&eacute;tablissement de la documentation contractuelle.</li>
		<li>
			&laquo;&nbsp;Conditions Particuli&egrave;res&nbsp;&raquo; d&eacute;signe le contrat commercial conclu entre le Client et CALS qui d&eacute;termine les conditions particuli&egrave;res d&rsquo;acc&egrave;s &agrave; la Plateforme CALS et fixe le montant de la Redevance.</li>
		<li>
			&laquo;&nbsp;Contenus&nbsp;&raquo; d&eacute;signe l&rsquo;ensemble des &eacute;l&eacute;ments fournis par le Client via la Plateforme CALS (p. ex. marques, &eacute;l&eacute;ments distinctifs, documents commerciaux) prot&eacute;g&eacute;s par des droits et/ou des autorisations dont le Client est titulaire.</li>
		<li>
			&laquo;&nbsp;Contrat&nbsp;&raquo; d&eacute;signe les Conditions G&eacute;n&eacute;rales ainsi que les annexes, avenants &eacute;ventuels et les Conditions Particuli&egrave;res associ&eacute;es.</li>
		<li>
			&laquo;&nbsp;Convention de Cr&eacute;dit&nbsp;&raquo; d&eacute;signe le document contractuel r&eacute;dig&eacute; et sign&eacute; dans le cadre d&rsquo;une op&eacute;ration de Syndication Bancaire sur le March&eacute; Primaire qui est compos&eacute; (i) de la convention de pr&ecirc;t - qui r&eacute;git les relations entre les Etablissements Bancaires et/ou les Utilisateurs Invit&eacute;s composant le Pool Bancaire et l&rsquo;Emprunteur&nbsp;; et (ii) du contrat de syndicat, qui r&eacute;git les relations entre les diff&eacute;rents Etablissements Autoris&eacute;s et/ou les Utilisateurs Invit&eacute;s, membres du Pool Bancaire.</li>
		<li>
			&laquo; Donn&eacute;es Client&raquo; d&eacute;signe l&rsquo;ensemble des donn&eacute;es relatives aux op&eacute;rations de Syndication Bancaire propos&eacute;es et r&eacute;alis&eacute;es par le Client via la Plateforme CALS.</li>
		<li>
			&laquo;&nbsp;Donn&eacute;es March&eacute;&nbsp;&raquo; d&eacute;signe les donn&eacute;es statistiques et anonymes relatives aux op&eacute;rations de Syndication Bancaire r&eacute;alis&eacute;es via la Plateforme CALS.</li>
		<li>
			&laquo;&nbsp;Donn&eacute;es Emprunteur&nbsp;&raquo; d&eacute;signe les donn&eacute;es &agrave; caract&egrave;re personnel au sens de l&rsquo;article 4 du R&egrave;glement 2016/679 relatif &agrave; la protection des personnes physiques &agrave; l&rsquo;&eacute;gard du traitement des donn&eacute;es &agrave; caract&egrave;re personnel et &agrave; la libre circulation de ces donn&eacute;es (ci-apr&egrave;s d&eacute;sign&eacute; le &laquo; RGPD&raquo;) qui peuvent &ecirc;tre contenues dans les documents fournis ou re&ccedil;us par le Client dans le cadre d&rsquo;une op&eacute;ration de Syndication Bancaire telles que les nom et pr&eacute;nom(s) du mandataire social de l&rsquo;Emprunteur.&nbsp;</li>
		<li>
			&laquo;&nbsp;Emprunteur&nbsp;&raquo; d&eacute;signe la personne morale de droit priv&eacute; ou de droit public, cliente du Client ou d&rsquo;un Etablissement Autoris&eacute; ou d&rsquo;un Utilisateur Invit&eacute;, qui est concern&eacute; par une op&eacute;ration de Syndication Bancaire sur le March&eacute; Primaire ou sur le March&eacute; secondaire r&eacute;alis&eacute;e via la Plateforme CALS.</li>
		<li>
			&laquo;&nbsp;Etablissement Autoris&eacute;&nbsp;&raquo; d&eacute;signe un &eacute;tablissementde cr&eacute;dit au sens des articles L.&nbsp;511-9 et suivants du code mon&eacute;taire et financier ou un fonds d&rsquo;investissement tel que d&eacute;fini aux articles R. 214-202 et suivants du code mon&eacute;taire et financier, qui d&eacute;tient un compte utilisateur sur la Plateforme CALS et qui est habilit&eacute; &ndash; par le biais de cette derni&egrave;re - &agrave; (i)&nbsp;proposer une Op&eacute;ration &agrave; un Pool Bancaire en qualit&eacute; d&rsquo;Arrangeur&nbsp;; (ii)&nbsp;faire partie d&rsquo;un Pool Bancaire pour financer une Op&eacute;ration&nbsp;; et/ou (iii)&nbsp;c&eacute;der une convention de pr&ecirc;t &agrave; un(des) autre(s) Etablissement(s) Autoris&eacute;(s). Le Client est un Etablissement Autoris&eacute;.</li>
		<li>
			&laquo;&nbsp;Force Majeure&nbsp;&raquo; d&eacute;signe un &eacute;v&egrave;nement ext&eacute;rieur aux Parties, impr&eacute;visible et irr&eacute;sistible, tel que d&eacute;fini par la jurisprudence des tribunaux fran&ccedil;aise, en ce compris&nbsp;: guerre (d&eacute;clar&eacute;e ou non)&nbsp;; acte terroriste&nbsp;; invasion&nbsp;; r&eacute;bellion&nbsp;; blocus&nbsp;; sabotage ou acte de vandalisme&nbsp;; gr&egrave;ve ou conflit social, total ou partiel, externe &agrave; chacune des Parties&nbsp;; intemp&eacute;rie (notamment inondations, orages et temp&ecirc;tes)&nbsp;; &eacute;v&egrave;nement d&eacute;clar&eacute; &laquo;&nbsp;catastrophe naturelle&nbsp;&raquo;&nbsp;; incendie&nbsp;; &eacute;pid&eacute;mie&nbsp;; blocage des moyens de transport ou d&rsquo;approvisionnement (notamment en &eacute;nergie)&nbsp;; d&eacute;faillance dans la fourniture de l&rsquo;&eacute;nergie &eacute;lectrique, du chauffage, de l&rsquo;air conditionn&eacute;, des r&eacute;seaux de t&eacute;l&eacute;communications, du transport des donn&eacute;es&nbsp;; d&eacute;faillance de satellites.</li>
		<li>
			&laquo;&nbsp;Information Confidentielle&nbsp;&raquo; d&eacute;signe toute information communiqu&eacute;e (que ce soit par &eacute;crit, oralement ou par un autre moyen et que ce soit directement ou indirectement) par une Partie &agrave; l&rsquo;autre Partie avant ou apr&egrave;s la date d&rsquo;entr&eacute;e en vigueur du Contrat, y compris, sans limitation, les proc&eacute;d&eacute;s, plans, savoir-faire, secrets commerciaux, inventions, techniques, opportunit&eacute;s commerciales et activit&eacute;s de l&rsquo;une des Parties.</li>
		<li>
			&laquo;&nbsp;KYB&nbsp;&raquo; d&eacute;signe les renseignements qu&rsquo;un Etablissement Autoris&eacute; ou un Utilisateur Invit&eacute; d&eacute;tient sur la situation financi&egrave;re d&rsquo;un Emprunteur, trait&eacute;s via la Plateforme CALS, qui permettent aux Etablissements Autoris&eacute;s et/ou aux Utilisateurs Invit&eacute;s de respecter leurs obligations l&eacute;gales, r&eacute;glementaires et prudentielles de lutte contre le blanchiment de capitaux et de financement du terrorisme.</li>
		<li>
			&laquo;&nbsp;KYC&nbsp;&raquo; d&eacute;signe les documents relatifs &agrave; un Emprunteur et/ou &agrave; ses repr&eacute;sentants, trait&eacute;s via la Plateforme CALS qui permettent aux Etablissements Autoris&eacute;s et/ou aux Utilisateurs Invit&eacute;s de respecter leurs obligations l&eacute;gales, r&eacute;glementaires et prudentielles de lutte contre le blanchiment de capitaux et de financement du terrorisme. Les KYC sont susceptibles de contenir des donn&eacute;es &agrave; caract&egrave;re personnel au sens de l&rsquo;article 4 du RGPD.</li>
		<li>
			&laquo;&nbsp;March&eacute; Primaire&nbsp;&raquo; d&eacute;signe le fait pour un Arrangeur de trouver un Pool Bancaire qui accepte de financer l&rsquo;Op&eacute;ration. Dans le cadre du March&eacute; Primaire, le risque de cr&eacute;dit est partag&eacute; d&egrave;s l&rsquo;origine entre les Etablissements Autoris&eacute;s et/ou les Utilisateurs Invit&eacute;s composant le Pool Bancaire.</li>
		<li>
			&laquo;&nbsp;March&eacute; Secondaire&nbsp;&raquo; d&eacute;signe le fait pour un Etablissement Autoris&eacute; ou un Utilisateur Invit&eacute; qui a conclu une convention de pr&ecirc;t bilat&eacute;rale ou qui est partie &agrave; une convention de pr&ecirc;t syndiqu&eacute;e de transf&eacute;rer ult&eacute;rieurement le risque et/ou la tr&eacute;sorerie, via la Plateforme CALS, de tout ou partie dudit cr&eacute;dit &agrave; un ou plusieurs autres Etablissements Autoris&eacute;s et/ou Utilisateurs Invit&eacute;s.</li>
		<li>
			&laquo;&nbsp;Op&eacute;ration&nbsp;&raquo; d&eacute;signe le cr&eacute;dit bancaire que d&eacute;sire obtenir l&rsquo;Emprunteur gr&acirc;ce &agrave; une op&eacute;ration de syndication de cr&eacute;dit sur le March&eacute; Primaire.</li>
		<li>
			&laquo;&nbsp;P&eacute;riode Initiale&nbsp;&raquo; d&eacute;signe la premi&egrave;re p&eacute;riode contractuelle d&rsquo;une dur&eacute;e d&rsquo;un (1) an.</li>
		<li>
			&laquo; Plateforme CALS &raquo; d&eacute;signe la solution logicielle &eacute;dit&eacute;e par CALS, laquelle permet de mettre en relation des Etablissements Autoris&eacute;s afin qu&rsquo;ils r&eacute;alisent entre eux des op&eacute;rations de Syndication Bancaires sur le March&eacute; Primaire ainsi que sur le March&eacute; Secondaire.</li>
		<li>
			&laquo;&nbsp;Pool Bancaire&nbsp;&raquo; d&eacute;signe l&rsquo;association de plusieurs Etablissements Autoris&eacute;s et/ou des Utilisateurs Invit&eacute;s r&eacute;unis dans un syndicat financier d&eacute;pourvu de la personnalit&eacute; juridique pour financer l&rsquo;Op&eacute;ration.</li>
		<li>
			&laquo;&nbsp;Redevance&nbsp;&raquo; d&eacute;signe les sommes dues par le Client &agrave; CALS en contrepartie de l&rsquo;utilisation de la Plateforme CALS. Ces sommes sont pr&eacute;cis&eacute;es aux Conditions Particuli&egrave;res.</li>
		<li>
			&laquo;&nbsp;Responsable&nbsp;&raquo; d&eacute;signe un Utilisateur Autoris&eacute; &ndash; salari&eacute; du Client &ndash; qui est habilit&eacute; par le Client &agrave; g&eacute;rer les habilitations d&rsquo;acc&egrave;s &agrave; la Plateforme CALS et &agrave; communiquer les identifiant et mot de passe du Client aux Utilisateurs Autoris&eacute;s.</li>
		<li>
			&laquo;&nbsp;Services&nbsp;&raquo; d&eacute;signe l&rsquo;ensemble des prestations fournies par CALS au Client aux termes du Contrat.</li>
		<li>
			&laquo;&nbsp;Syndication Bancaire&nbsp;&raquo; d&eacute;signe la r&eacute;union de deux ou plusieurs Etablissements Autoris&eacute;s et/ou Utilisateurs Invit&eacute;s, ayant pour objet la r&eacute;partition de la charge d&rsquo;un cr&eacute;dit octroy&eacute; &agrave; un Emprunteur.</li>
		<li>
			&laquo;&nbsp;Utilisateur Autoris&eacute;&nbsp;&raquo; d&eacute;signe l&rsquo;utilisateur identifi&eacute;, personne physique, habilit&eacute; par le Client &agrave; acc&eacute;der &agrave; la Plateforme CALS et &agrave; utiliser les Services conform&eacute;ment aux stipulations des Conditions G&eacute;n&eacute;rales. Un Utilisateur Autoris&eacute; peut avoir le statut de Responsable, d&rsquo;Utilisateur Salari&eacute; ou d&rsquo;Utilisateur Invit&eacute;.</li>
		<li>
			&laquo;&nbsp;Utilisateur Invit&eacute;&nbsp;&raquo; d&eacute;signe un Utilisateur Autoris&eacute;, salari&eacute; d&rsquo;un &eacute;tablissement de cr&eacute;dit ou d&rsquo;un fonds d&rsquo;investissement qui ne dispose pas d&rsquo;un compte utilisateur sur la Plateforme CALS et qui est invit&eacute; par un Etablissement Autoris&eacute; &agrave; participer &agrave; une op&eacute;ration de Syndication Bancaire r&eacute;alis&eacute;e via la Plateforme CALS. L&rsquo;Utilisateur Invit&eacute; s&rsquo;engage express&eacute;ment &agrave; &ecirc;tre habilit&eacute; par l&rsquo;&eacute;tablissement de cr&eacute;dit ou le fonds d&rsquo;investissement dans lequel il est salari&eacute; &agrave; r&eacute;aliser des op&eacute;rations de Syndication Bancaire en son nom et pour son compte.&nbsp;&nbsp;&nbsp;</li>
		<li>
			&laquo;&nbsp;Utilisateur Salari&eacute;&nbsp;&raquo; d&eacute;signe l&rsquo;utilisateur identifi&eacute;, personne physique, salari&eacute; par le Client et habilit&eacute; par le Responsable &agrave; acc&eacute;der &agrave; la Plateforme CALS.</li>
	</ul>
	<div>
		<h2>
			Article 2 Objet</h2>
	</div>
	<p>
		Les Conditions G&eacute;n&eacute;rales d&eacute;finissent les conditions de mise disposition de la Plateforme CALS et les conditions de son utilisation par le Client et les Utilisateurs Invit&eacute;s.</p>
	<div>
		<h2>
			Article 3 Entr&eacute;e en vigueur &ndash; Dur&eacute;e</h2>
	</div>
	<p>
		Le Contrat prend effet &agrave; compter de la signature du Contrat par l&rsquo;ensemble des Parties, pour la P&eacute;riode Initiale.</p>
	<p>
		A l&rsquo;expiration de la P&eacute;riode Initiale, sauf en cas (i)&nbsp;de r&eacute;siliation anticip&eacute;e dans les conditions d&eacute;finies &agrave; l&rsquo;Article 14 &ndash; &laquo;&nbsp;<em>R&eacute;siliation</em>&nbsp;&raquo;&nbsp;; ou (ii)&nbsp;d&eacute;nonciation par l&rsquo;une ou l&rsquo;autre des Parties, par lettre recommand&eacute;e avec demande d&rsquo;avis de r&eacute;ception, adress&eacute;e au minimum quatre-vingt-dix (90) jours avant l&rsquo;expiration de la p&eacute;riode contractuelle en cours, le Contrat sera tacitement renouvel&eacute; pour une nouvelle p&eacute;riode contractuelle de m&ecirc;me dur&eacute;e que la P&eacute;riode Initiale.</p>
	<div>
		<h2>
			Article 4 Description des Services</h2>
	</div>
	<h3>
		4.1 Conditions pr&eacute;alables</h3>
	<p>
		Afin de pouvoir utiliser la Plateforme CALS, le Client s&rsquo;engage &agrave; &ecirc;tre titulaire de l&rsquo;ensemble des autorisations et agr&eacute;ments n&eacute;cessaires &agrave; la r&eacute;alisation d&rsquo;op&eacute;rations de Syndication Bancaire.</p>
	<p>
		Le Client reconna&icirc;t et accepte que les op&eacute;rations de Syndication Bancaire r&eacute;alis&eacute;es via la Plateforme CALS doivent concerner uniquement les cr&eacute;dits bancaires qui ont &eacute;t&eacute; consentis &agrave; des personnes morales.</p>
	<p>
		Les op&eacute;rations de Syndication Bancaire r&eacute;alis&eacute;es via la Plateforme CALS ne peuvent &agrave; aucun moment porter sur un cr&eacute;dit bancaire octroy&eacute; &agrave; une personne physique.</p>
	<p>
		Le Client se porte fort du respect de ces obligations par ses Utilisateurs Invit&eacute;s.</p>
	<h3>
		4.2 Syndication Bancaire sur le March&eacute; Primaire</h3>
	<h4>
		4.2.1 Appel d&rsquo;offre</h4>
	<p>
		Lorsque qu&rsquo;un Etablissement Autoris&eacute; est d&eacute;sign&eacute; par un Emprunteur pour mener &agrave; bien une Op&eacute;ration, il doit - via son compte utilisateur - cr&eacute;er un appel d&rsquo;offre de cr&eacute;dit en cliquant sur le lien &laquo;&nbsp;Arrangement de dette&nbsp;&raquo;.</p>
	<p>
		L&rsquo;appel d&rsquo;offre de cr&eacute;dit doit mentionner&nbsp;:</p>
	<ol>
		<li>
			l&rsquo;identit&eacute; de l&rsquo;Emprunteur&nbsp;ainsi que son secteur d&rsquo;activit&eacute;&nbsp;;</li>
		<li>
			la dur&eacute;e de validit&eacute; de l&rsquo;offre&nbsp;;</li>
		<li>
			une description du projet envisag&eacute;&nbsp;;</li>
		<li>
			le montant du cr&eacute;dit souhait&eacute; et les modalit&eacute;s de remboursement y aff&eacute;rentes.</li>
	</ol>
	<p>
		L&rsquo;Arrangeur a la possibilit&eacute; de t&eacute;l&eacute;charger les KYC et les KYB qu&rsquo;il a collect&eacute;s sur l&rsquo;Emprunteur et de les communiquer aux Etablissements Autoris&eacute;s et/ou aux Utilisateurs Invit&eacute;s, destinataires de l&rsquo;appel d&rsquo;offre de cr&eacute;dit.</p>
	<p>
		L&rsquo;Etablissement Autoris&eacute;, qui agit en qualit&eacute; d&rsquo;Arrangeur, peut choisir de communiquer l&rsquo;appel d&rsquo;offre de cr&eacute;dit ainsi compl&eacute;t&eacute; &agrave; l&rsquo;ensemble des Etablissements Autoris&eacute;s, s&eacute;lectionner les Etablissements Autoris&eacute;s auxquels il souhaite transmettre l&rsquo;appel d&rsquo;offre de cr&eacute;dit et/ou inviter un ou des Utilisateurs Invit&eacute;s.</p>
	<h4>
		4.2.2 Offres de cr&eacute;dit</h4>
	<h4>
		Chacun des Etablissements Autoris&eacute;s et/ou des Utilisateurs Invit&eacute;s destinataires de l&rsquo;appel d&rsquo;offre de cr&eacute;dit peut &eacute;mettre une offre de cr&eacute;dit afin de participer au financement de l&rsquo;Op&eacute;ration.</h4>
	<p>
		Pour ce faire, l&rsquo;Etablissement Autoris&eacute; ou l&rsquo;Utilisateur Invit&eacute; concern&eacute; doit r&eacute;pondre &agrave; l&rsquo;Appel d&rsquo;Offre via son compte utilisateur en pr&eacute;cisant&nbsp;:</p>
	<ol>
		<li>
			le montant du pr&ecirc;t qu&rsquo;il souhaite consentir&nbsp;;</li>
		<li>
			la dur&eacute;e dudit pr&ecirc;t&nbsp;;</li>
		<li>
			les conditions financi&egrave;res y aff&eacute;rentes (p. ex. taux d&rsquo;int&eacute;r&ecirc;t, marge, commission bancaire).</li>
	</ol>
	<p>
		L&rsquo;offre de cr&eacute;dit ainsi valid&eacute;e sera communiqu&eacute;e &agrave; l&rsquo;Arrangeur, sur son compte utilisateur.</p>
	<p>
		L&rsquo;Arrangeur s&eacute;lectionnera les diff&eacute;rentes offres de cr&eacute;dit &eacute;mises par les diff&eacute;rents Etablissements Autoris&eacute;s et/ou les Utilisateurs Invit&eacute;s.</p>
	<p>
		Les Etablissements Autoris&eacute;s et/ou les Utilisateurs Invit&eacute;s, exp&eacute;diteurs des offres de cr&eacute;dit retenus, constituent le Pool Bancaire.</p>
	<h4>
		4.2.3 R&eacute;daction des documents contractuels</h4>
	<p>
		L&rsquo;Arrangeur r&eacute;dige l&rsquo;ensemble des documents contractuels relatifs &agrave; l&rsquo;Op&eacute;ration &agrave; savoir les diff&eacute;rents accords de confidentialit&eacute; entre les parties &agrave; l&rsquo;Op&eacute;ration concern&eacute;e ainsi que la Convention de Cr&eacute;dit.</p>
	<p>
		L&rsquo;Arrangeur doit communiquer les documents contractuels qu&rsquo;il a r&eacute;dig&eacute;s en les t&eacute;l&eacute;chargeant sur la Plateforme CALS afin de permettre aux Etablissements Autoris&eacute;s et/ou Utilisateurs Invit&eacute;s concern&eacute;s de les signer.</p>
	<p>
		L&rsquo;Arrangeur est seul responsable de conformit&eacute; des documents contractuels &agrave; la l&eacute;gislation applicable. La responsabilit&eacute; de CALS ne pourra en aucun cas &ecirc;tre engag&eacute;e sur ce fondement.</p>
	<h4>
		4.2.4 Signature des documents contractuels</h4>
	<p>
		Les accords de confidentialit&eacute;s et la Convention de Cr&eacute;dit sont sign&eacute;s par l&rsquo;Arrangeur et les diff&eacute;rents membres du Pool Bancaire sur la Plateforme CALS via DocuSign, prestataire de signature &eacute;lectronique.</p>
	<p>
		Afin de pouvoir utiliser les services de signature &eacute;lectronique propos&eacute;s via la Plateforme CALS, le Client doit accepter les conditions g&eacute;n&eacute;rales de DocuSign, disponibles &agrave; l&rsquo;adresse URL <strong>[&bull;]</strong>.</p>
	<h4>
		4.2.5 Suivi de l&rsquo;Op&eacute;ration</h4>
	<p>
		L&rsquo;Agent est responsable du suivi de l&rsquo;Op&eacute;ration et de la bonne ex&eacute;cution de la Convention de Cr&eacute;dit.</p>
	<p>
		Les Etablissements Autoris&eacute;s <a>et/ou les Utilisateurs Invit&eacute;s </a><a href="#_msocom_1" id="_anchor_1" name="_msoanchor_1" uage="JavaScript">[MM1]</a>&nbsp;membres du Pool Bancaire pourront, via la Plateforme CALS, &ecirc;tre assist&eacute;s dans la r&eacute;daction et l&rsquo;envoi des rappels et communications relatifs &agrave; la Convention de Cr&eacute;dit aux parties dudit contrat.</p>
	<p>
		L&rsquo;Arrangeur, <a>les Utilisateurs Invit&eacute;s </a><a href="#_msocom_2" id="_anchor_2" name="_msoanchor_2" uage="JavaScript">[MM2]</a>&nbsp;et les Etablissements Autoris&eacute;s membres du Pool Bancaire pourront suivre l&rsquo;&eacute;volution de l&rsquo;Op&eacute;ration via leur compte utilisateur.</p>
	<h3>
		4.3 Syndication Bancaire sur le March&eacute; Secondaire</h3>
	<h4>
		4.3.1Appel d&rsquo;offre de rachat</h4>
	<p>
		L&rsquo;Etablissement Autoris&eacute; qui a conclu une convention de pr&ecirc;t bilat&eacute;rale ou syndiqu&eacute;e peut transf&eacute;rer tout ou partie du risque de ce cr&eacute;dit &agrave; un ou plusieurs autres Etablissements Autoris&eacute;s et/ou des Utilisateurs Invit&eacute;s.</p>
	<p>
		Pour ce faire, l&rsquo;Etablissement Autoris&eacute; doit renseigner un appel d&rsquo;offre de rachat via son compte utilisateur qui pr&eacute;cise&nbsp;:</p>
	<ol>
		<li>
			l&rsquo;identit&eacute; de l&rsquo;Emprunteur&nbsp;ainsi que son secteur d&rsquo;activit&eacute; et l&rsquo;&eacute;valuation du risque qui lui est associ&eacute;&nbsp;;</li>
		<li>
			la dur&eacute;e de validit&eacute; de l&rsquo;offre&nbsp;de rachat&nbsp;;</li>
		<li>
			une description du projet concern&eacute;&nbsp;;</li>
		<li>
			le montant du cr&eacute;dit c&eacute;d&eacute; et les modalit&eacute;s de remboursement y aff&eacute;rentes.</li>
	</ol>
	<p>
		L&rsquo;Etablissement Autoris&eacute; c&eacute;dant a la possibilit&eacute; de t&eacute;l&eacute;charger les KYC et les KYB qu&rsquo;il a collect&eacute;s sur l&rsquo;Emprunteur et de les communiquer aux Etablissements Autoris&eacute;s et/ou Utilisateurs Invit&eacute;s, destinataires de l&rsquo;appel d&rsquo;offre de rachat.</p>
	<p>
		L&rsquo;Etablissement Autoris&eacute; concern&eacute; peut choisir de communiquer l&rsquo;appel d&rsquo;offre de rachat soit &agrave; l&rsquo;ensemble des Etablissements Autoris&eacute;s, soit aux seuls Etablissements Autoris&eacute;s et/ou Utilisateurs Invit&eacute;s qu&rsquo;il aura s&eacute;lectionn&eacute;s.</p>
	<h4>
		<a>4.3.2 Offre de rachat</a><a href="#_msocom_3" id="_anchor_3" name="_msoanchor_3" uage="JavaScript">[MM3]</a></h4>
	<p>
		Chacun des Etablissements Autoris&eacute;s et/ou Utilisateurs Autoris&eacute;s destinataires de l&rsquo;appel d&rsquo;offre de rachat peut &eacute;mettre une offre de rachat du cr&eacute;dit c&eacute;d&eacute; par l&rsquo;Etablissement Autoris&eacute; concern&eacute;.</p>
	<p>
		Pour ce faire, les Etablissements Autoris&eacute;s et/ou les Utilisateurs Invit&eacute;s int&eacute;ress&eacute;s doivent r&eacute;pondre &agrave; l&rsquo;appel d&rsquo;offre de cr&eacute;dit via leur compte utilisateur en pr&eacute;cisant&nbsp;:</p>
	<ol>
		<li>
			le montant du pr&ecirc;t qu&rsquo;elle souhaite consentir&nbsp;;</li>
		<li>
			la dur&eacute;e dudit pr&ecirc;t&nbsp;;</li>
		<li>
			les conditions financi&egrave;res y aff&eacute;rentes (p. ex. taux d&rsquo;int&eacute;r&ecirc;t, marge, commissions bancaires).</li>
	</ol>
	<p>
		L&rsquo;offre de rachat ainsi valid&eacute;e sera communiqu&eacute;e &agrave; l&rsquo;Etablissement Autoris&eacute; c&eacute;dant, sur son compte utilisateur.</p>
	<p>
		L&rsquo;Etablissement Autoris&eacute; c&eacute;dant s&eacute;lectionnera la ou les diff&eacute;rentes offres de rachat &eacute;mis par les diff&eacute;rents Etablissements Autoris&eacute;s et/ou Utilisateurs Invit&eacute;s.</p>
	<h4>
		4.3.3 R&eacute;daction des documents contractuels</h4>
	<p>
		L&rsquo;Etablissement Autoris&eacute; c&eacute;dant r&eacute;dige l&rsquo;ensemble des documents contractuels relatifs &agrave; l&rsquo;op&eacute;ration de syndication &agrave; savoir les diff&eacute;rents accords de confidentialit&eacute; entre les parties &agrave; ladite op&eacute;ration ainsi que la Convention de Sous-Participation.</p>
	<p>
		L&rsquo;Etablissement Autoris&eacute; c&eacute;dant doit communiquer les documents contractuels qu&rsquo;il a r&eacute;dig&eacute;s en les t&eacute;l&eacute;chargeant sur la Plateforme CALS afin de permettre aux Etablissements Autoris&eacute;s et/ou aux Utilisateurs Invit&eacute;s cessionnaires de les signer.</p>
	<p>
		L&rsquo;Etablissement Autoris&eacute; c&eacute;dant est seul responsable de conformit&eacute; des documents contractuels &agrave; la l&eacute;gislation applicable. La responsabilit&eacute; de CALS ne pourra en aucun cas &ecirc;tre engag&eacute;e sur ce fondement.</p>
	<h4>
		4.3.4 Signature des documents contractuels</h4>
	<p>
		Les accords de confidentialit&eacute;s ainsi que la Convention de Sous-Participation sont sign&eacute;s par l&rsquo;Etablissement Autoris&eacute; c&eacute;dant et le ou les diff&eacute;rents Etablissements Autoris&eacute;s cessionnaires via DocuSign, prestataire de signature &eacute;lectronique.</p>
	<p>
		Suivi de la Convention de Sous-ParticipationLes Etablissements Autoris&eacute;s et/ou les Utilisateurs Invit&eacute;s cessionnaires pourront, via la Plateforme CALS, &ecirc;tre assist&eacute;s dans la r&eacute;daction et l&rsquo;envoi des rappels et communications relatifs aux parties &agrave; la Convention de Sous-Participation.</p>
	<p>
		Les Etablissements Autoris&eacute;s et/ou les Utilisateurs Invit&eacute;s membres &agrave; ladite op&eacute;ration de Syndication Bancaire pourront suivre son &eacute;volution via leur compte utilisateur.</p>
	<h3>
		4.4 Collecte des KYC et des KYB</h3>
	<p>
		Dans le cadre de la r&eacute;alisation d&rsquo;une op&eacute;ration de Syndication Bancaire, les Etablissements Autoris&eacute;s et/ou Utilisateurs Invit&eacute;s parties &agrave; ladite op&eacute;ration ont l&rsquo;obligation de conna&icirc;tre l&rsquo;Emprunteur conform&eacute;ment aux dispositions l&eacute;gislatives, r&egrave;glementaires et d&eacute;ontologiques relatives &agrave; la lutte contre le blanchiment de capitaux et le financement du terrorisme, y compris les articles L. 561-1 et suivants et R. 561-1 et suivants du code mon&eacute;taire et financier.</p>
	<p>
		Pour ce faire, l&rsquo;Arrangeur ou l&rsquo;Etablissement Autoris&eacute; c&eacute;dant une convention de pr&ecirc;t s&rsquo;engage &agrave; collecter les KYC et les KYB n&eacute;cessaires et, le cas &eacute;ch&eacute;ant, &agrave; les communiquer aux parties &agrave; l&rsquo;op&eacute;ration de Syndication Bancaire concern&eacute;es. CALS ne pourra en aucun cas voir sa responsabilit&eacute; engag&eacute;e si les Etablissements Autoris&eacute;s ne respectent pas leurs obligations li&eacute;es &agrave; la collecte des KYC et des KYB.</p>
	<p>
		L&rsquo;Arrangeur et l&rsquo;Etablissement Autoris&eacute; c&eacute;dant une convention de pr&ecirc;t peuvent t&eacute;l&eacute;charger les KYC et les KYB sur la Plateforme CALS.</p>
	<div>
		<h2>
			Article 5 Utilisation de la Plateforme CALS</h2>
	</div>
	<h3>
		5.1 Abonnement &agrave; la Plateforme CALS</h3>
	<p>
		Le Client souscrit un abonnement personnel aupr&egrave;s de CALS, afin d&rsquo;utiliser la Plateforme CALS pour ses besoins professionnels dans les conditions et limites sp&eacute;cifi&eacute;es aux Conditions G&eacute;n&eacute;rales. Ce droit d&rsquo;utilisation est conc&eacute;d&eacute; &agrave; titre non exclusif, non transf&eacute;rable et non cessible, pour le Monde entier et pour la dur&eacute;e des Conditions G&eacute;n&eacute;rales.</p>
	<p>
		Ce m&ecirc;me droit d&rsquo;utilisation est octroy&eacute; &agrave; chacun des Utilisateurs Autoris&eacute;s dans le cadre de leur utilisation professionnelle de la Plateforme CALS.</p>
	<p>
		Le droit d&rsquo;utilisation de la Plateforme CALS conc&eacute;d&eacute; &agrave; un Utilisateur Invit&eacute; par un Etablissement Autoris&eacute; concerne uniquement l&rsquo;op&eacute;ration de Syndication Bancaire &agrave; laquelle il a &eacute;t&eacute; invit&eacute;. Pour pouvoir acc&eacute;der &agrave; l&rsquo;int&eacute;gralit&eacute; des fonctionnalit&eacute;s de la Plateforme CALS, l&rsquo;Utilisateur Invit&eacute; devra souscrire un abonnement &agrave; la Plateforme CALS.</p>
	<p>
		Dans le cadre de son droit d&rsquo;usage de la Plateforme CALS, le Client s&rsquo;engage sans r&eacute;serve &agrave; ne pas&nbsp;:</p>
	<ol>
		<li>
			effectuer une copie de la Plateforme CALS ou d&rsquo;&eacute;l&eacute;ments de la Plateforme CALS, de quelque fa&ccedil;on que ce soit&nbsp;;</li>
		<li>
			analyser, ou faire analyser par un tiers, au sens d&rsquo;observer, &eacute;tudier et tester, le fonctionnement de la Plateforme CALS en vue de d&eacute;terminer les id&eacute;es et principes sur lesquels les &eacute;l&eacute;ments du programme se basent lorsque la Plateforme CALS ex&eacute;cute les op&eacute;rations de chargement, d&rsquo;affichage, d&rsquo;ex&eacute;cution, de transmission ou de stockage&nbsp;;</li>
		<li>
			d&eacute;compiler, d&eacute;sassembler la Plateforme CALS, pratiquer l&rsquo;ing&eacute;nierie inverse de cr&eacute;er des &oelig;uvres d&eacute;riv&eacute;es &agrave; partir de la Plateforme CALS ou tenter de d&eacute;couvrir ou reconstituer le code source, les id&eacute;es qui en sont la base, les algorithmes, les formats des fichiers ou les interfaces de programmation ou d&rsquo;interop&eacute;rabilit&eacute; de la Plateforme CALS sauf dans la limite du droit accord&eacute; par l&rsquo;article&nbsp;L.&nbsp;122-6-1 du Code de la propri&eacute;t&eacute; intellectuelle, de quelque mani&egrave;re que ce soit. Au cas o&ugrave; le Client souhaiterait obtenir les informations permettant de mettre en &oelig;uvre l&rsquo;interop&eacute;rabilit&eacute; de la Plateforme CALS avec un autre logiciel, le Client s&rsquo;engage &agrave; demander ces informations &agrave; CALS, qui pourra fournir les informations n&eacute;cessaires au Client, sous r&eacute;serve du paiement par ce dernier des co&ucirc;ts associ&eacute;s&nbsp;;</li>
		<li>
			modifier, am&eacute;liorer, traduire la Plateforme CALS, y compris pour corriger les bugs et les erreurs, CALS se r&eacute;servant exclusivement ce droit conform&eacute;ment &agrave; l&rsquo;article L.&nbsp;122-6-1 I 2&deg; du Code de la propri&eacute;t&eacute; intellectuelle&nbsp;;</li>
		<li>
			fournir &agrave; des tiers des prestations, &agrave; titre gratuit ou on&eacute;reux, qui soient bas&eacute;es sur la Plateforme CALS. En particulier, le Client s&rsquo;interdit d&rsquo;int&eacute;grer, traiter et/ou utiliser les donn&eacute;es d&rsquo;un tiers&nbsp;; et/ou octroyer un acc&egrave;s, total ou partiel, &agrave; la Plateforme CALS, notamment sous forme de service bureau, en ASP, en PaaS ou en SaaS&nbsp;;</li>
		<li>
			transf&eacute;rer, louer, sous-licencier, c&eacute;der, nantir, ou transf&eacute;rer tout ou partie de la propri&eacute;t&eacute; de la Plateforme CALS de quelque mani&egrave;re que ce soit.</li>
	</ol>
	<p>
		La Plateforme CALS peut int&eacute;grer des logiciels tiers qui seront utilis&eacute;s par le Client uniquement en relation avec la Plateforme CALS et ne seront jamais utilis&eacute;s d&rsquo;une quelconque autre mani&egrave;re sans l&rsquo;accord pr&eacute;alable et &eacute;crit de CALS.</p>
	<p>
		Le Client se porte fort du respect des stipulations du pr&eacute;sent Article 5.1 par les Utilisateurs Autoris&eacute;s.</p>
	<h3>
		5.2 Acc&egrave;s &agrave; la Plateforme CALS &ndash; Utilisateurs Autoris&eacute;s</h3>
	<p>
		L&rsquo;acc&egrave;s &agrave; la Plateforme CALS est limit&eacute; au Client et le cas &eacute;ch&eacute;ant, au Responsable et aux seuls Utilisateurs Autoris&eacute;s auxquels le Responsable aura communiqu&eacute; les identifiant et mot de passe pour acc&eacute;der &agrave; la Plateforme CALS.</p>
	<p>
		Le Client s&rsquo;engage &agrave; communiquer ses identifiant et mot de passe uniquement&nbsp;:</p>
	<ol>
		<li>
			s&rsquo;agissant des Responsable et Utilisateurs Salari&eacute;s, &agrave; son seul personnel comp&eacute;tent et habilit&eacute; &agrave; r&eacute;aliser des op&eacute;rations de Syndication Bancaire&nbsp;;</li>
		<li>
			s&rsquo;agissant des Utilisateurs Invit&eacute;s, uniquement &agrave; des personnes salari&eacute;es d&rsquo;&eacute;tablissements de cr&eacute;dit ou de fonds d&rsquo;investissement disposant de la capacit&eacute; de participer &agrave; des op&eacute;rations de Syndication Bancaire en leur nom et pour leur compte. &nbsp;</li>
	</ol>
	<p>
		A r&eacute;ception de l&rsquo;invitation &eacute;mise par un Etablissement Autoris&eacute;, l&rsquo;Utilisateur Invit&eacute; doit accepter les Conditions G&eacute;n&eacute;rales pour acc&eacute;der &agrave; la Plateforme CALS.</p>
	<p>
		Il s&rsquo;engage &agrave; &ecirc;tre pleinement habilit&eacute; par l&rsquo;&eacute;tablissement de cr&eacute;dit ou le fonds d&rsquo;investissement qui l&rsquo;emploie &agrave; r&eacute;aliser des op&eacute;rations de Syndication Bancaire en son nom et pour son compte.</p>
	<p>
		La responsabilit&eacute; de CALS ne pourra en aucun cas &ecirc;tre engag&eacute;e si une op&eacute;ration de Syndication Bancaire a &eacute;t&eacute; r&eacute;alis&eacute;e par une personne ne disposant pas des comp&eacute;tences et pouvoirs n&eacute;cessaires pour ce faire.</p>
	<p>
		Le Client est seul responsable de la s&eacute;curit&eacute; des login et mot de passe. Le Client s&rsquo;engage &agrave; informer promptement CALS de tout acc&egrave;s non autoris&eacute;, qu&rsquo;il soit effectif ou suppos&eacute;, au login, au mot de passe et/ou &agrave; la Plateforme CALS.</p>
	<p>
		Toute action r&eacute;alis&eacute;e via le login appartenant au Client sera r&eacute;put&eacute;e come ayant &eacute;t&eacute; r&eacute;alis&eacute;e par le Client, sauf &agrave; ce qu&rsquo;elle ait pr&eacute;alablement d&eacute;clar&eacute; le login concern&eacute; comme ayant &eacute;t&eacute; perdu ou vol&eacute;, allouant ainsi un d&eacute;lai raisonnable &agrave; CALS pour d&eacute;sactiver ledit login.</p>
	<p>
		Dans ce cadre, le Client se porte fort du respect des termes des Conditions G&eacute;n&eacute;rales par le Responsable et chacun des Utilisateurs Autoris&eacute;s.</p>
	<h3>
		5.3 Contenus</h3>
	<p>
		Le Client garantit qu&rsquo;il est pleinement titulaire de tous les droits et autorisations relatifs aux Contenus qu&rsquo;il communique &agrave; un autre Etablissement Autoris&eacute; via la Plateforme CALS et fournit &agrave; CALS.</p>
	<p>
		Le Client s&rsquo;engage &agrave; d&eacute;tenir toutes les autorisations requises pour transmettre le contenu &agrave; un tiers ou &agrave; CALS.</p>
	<p>
		A ce titre, le Client garantit et rel&egrave;ve CALS de tout dommage, condamnation, frais ou co&ucirc;t relatif &agrave; toute demande, action et/ou r&eacute;clamation formul&eacute;e &agrave; l&rsquo;encontre de CALS et fond&eacute;e sur l&rsquo;atteinte par le Client &agrave; un quelconque droit d&rsquo;un tiers.</p>
	<p>
		CALS est autoris&eacute;e &agrave; faire usage de l&rsquo;ensemble des Contenus, lorsque cela est n&eacute;cessaire &agrave; l&rsquo;ex&eacute;cution de ses obligations au titre du Contrat. Dans ce cadre, CALS s&rsquo;engage &agrave; respecter toute ligne directrice qui lui serait communiqu&eacute;e pr&eacute;alablement &agrave; son utilisation des Contenus.</p>
	<p>
		De la m&ecirc;me fa&ccedil;on, le Client garantit que les Contenus ne rev&ecirc;tent aucun caract&egrave;re illicite, mena&ccedil;ant, humiliant, diffamatoire, obsc&egrave;ne, haineux, pornographique ou blasph&eacute;matoire, ou tout autre message qui pourrait constituer un crime ou un d&eacute;lit, engager la responsabilit&eacute; civile, porter atteinte &agrave; la l&eacute;gislation ou inciter &agrave; le faire, ou encore des contenus qui pourraient &ecirc;tre utilis&eacute;s &agrave; toute fin contraire &agrave; la loi ou au Contrat.</p>
	<div>
		<h2>
			Article 6 Conditions financi&egrave;res</h2>
	</div>
	<h3>
		6.1 Paiement des Services</h3>
	<p>
		La Redevance que le Client s&rsquo;engage &agrave; verser &agrave; CALS est d&eacute;finie aux Conditions Particuli&egrave;res.</p>
	<p>
		Les prestations et/ou interventions suppl&eacute;mentaires r&eacute;alis&eacute;es par CALS dans les conditions d&eacute;finies au Contrat seront factur&eacute;es par CALS, au taux horaire en vigueur au moment de la r&eacute;alisation de la prestation ou intervention en cause.</p>
	<h3>
		6.2 Modalit&eacute;s de paiement</h3>
	<p>
		La p&eacute;riodicit&eacute; de facturation et les d&eacute;lais de paiement sont vis&eacute;s au(x) Conditions Particuli&egrave;res correspondante(s).</p>
	<p>
		En cas de non-paiement de toute somme dans les d&eacute;lais contractuels&nbsp;:</p>
	<ol>
		<li>
			toute somme impay&eacute;e portera automatiquement int&eacute;r&ecirc;t au jour le jour jusqu&rsquo;&agrave; la date de son paiement int&eacute;gral en principal, int&eacute;r&ecirc;ts, frais et accessoires, &agrave; un taux &eacute;gal &agrave; trois (3) fois le taux d&rsquo;int&eacute;r&ecirc;t l&eacute;gal en vigueur, et ce, sans aucune formalit&eacute; pr&eacute;alable, et sans pr&eacute;judice des dommages-int&eacute;r&ecirc;ts que CALS se r&eacute;serve le droit de solliciter de mani&egrave;re judiciaire&nbsp;;</li>
		<li>
			CALS se r&eacute;serve le droit, &agrave; sa seule discr&eacute;tion avec ou sans pr&eacute;avis, de suspendre l&rsquo;ex&eacute;cution de tout ou partie des Services en cours ou future, et ce jusqu&rsquo;&agrave; complet paiement des sommes dues&nbsp;;</li>
		<li>
			tous les frais engag&eacute;s par CALS pour le recouvrement des sommes dues seront &agrave; la charge du Client, en ce compris les frais d&rsquo;huissier, frais de justice et honoraires d&rsquo;avocat, lesdits frais ne pouvant en tout &eacute;tat de cause &ecirc;tre inf&eacute;rieurs &agrave; l&rsquo;indemnit&eacute; forfaitaire vis&eacute;e par l&rsquo;article L. 441-6 I 12e du Code de commerce, d&rsquo;un montant de quarante (40) euros&nbsp;;</li>
		<li>
			toutes les sommes restant dues &agrave; CALS par le Client au titre du Contrat deviennent imm&eacute;diatement exigibles.</li>
	</ol>
	<p>
		Les sommes vers&eacute;es par le Client &agrave; CALS dans le cadre du Contrat restent acquises &agrave; CALS et ne sont donc pas remboursables, et ce, m&ecirc;me en cas de r&eacute;siliation du Contrat ou de tout autre contrat conclu entre CALS et le Client.</p>
	<ul>
		<li>
			<strong>R&eacute;vision tarifaire</strong></li>
	</ul>
	<p>
		&Agrave; l&rsquo;issue de chaque p&eacute;riode contractuelle, la Redevance pourra &ecirc;tre r&eacute;&eacute;valu&eacute;e, selon la formule suivante&nbsp;:</p>
	<p>
		&nbsp;</p>
	<p align="center">
		<img chromakey="white" src="file:////Users/binxiao/Library/Group%20Containers/UBF8T346G9.Office/TemporaryItems/msohtmlclip/clip_image001.emz" /></p>
	<p>
		Dans laquelle&nbsp;:</p>
	<p align="left" style="margin-left:35.45pt;">
		S = Dernier indice SYNTEC publi&eacute; &agrave; la date de r&eacute;vision,<br />
		So = Indice SYNTEC douze mois avant S,<br />
		Po = Montant de la Redevance pour la p&eacute;riode contractuelle pr&eacute;c&eacute;dente,<br />
		P = Montant r&eacute;vis&eacute; de la Redevance.</p>
	<div>
		<h2>
			Article 7 Engagements de CALS</h2>
	</div>
	<h3>
		7.1 Conformit&eacute; des Services</h3>
	<p>
		Les Services que le Client souhaite voir r&eacute;alis&eacute;s seront fournis selon les termes des Conditions G&eacute;n&eacute;rales par CALS qui s&rsquo;engage, sauf stipulations expresses contraires, au titre d&rsquo;une obligation de moyen.</p>
	<p>
		CALS s&rsquo;engage &agrave; ce que tous ses personnels mettent tout leur savoir-faire et leurs connaissances au service de la bonne ex&eacute;cution des Services. En cas de difficult&eacute;s dans la fourniture des Services, CALS s&rsquo;oblige &agrave; en informer aussit&ocirc;t le Client.</p>
	<p>
		CALS se r&eacute;serve le droit de modifier &agrave; tout moment les caract&eacute;ristiques de ses infrastructures techniques, le choix de ses fournisseurs techniques et la composition de ses &eacute;quipes.</p>
	<h3>
		7.2 Obligations en mati&egrave;res fiscale et sociale</h3>
	<p>
		CALS d&eacute;clare, en tant que de besoin, &ecirc;tre immatricul&eacute;e aupr&egrave;s du Registre du Commerce et des Soci&eacute;t&eacute;s, aupr&egrave;s des URSSAF et/ou aupr&egrave;s de toutes administrations ou organismes (en ce compris les administrations ou organismes d&rsquo;assurance sociale) requis pour l&rsquo;ex&eacute;cution du Contrat. Les immatriculations faites conform&eacute;ment &agrave; cet article, ainsi que les immatriculations effectu&eacute;es pr&eacute;alablement &agrave; la conclusion du Contrat doivent couvrir express&eacute;ment toutes les activit&eacute;s du Client pour l&rsquo;ex&eacute;cution des prestations en application du Contrat. Conform&eacute;ment aux dispositions des articles L.&nbsp;8221-1 et suivants et D.&nbsp;8222-5 du Code du travail, CALS s&rsquo;engage &agrave; remettre au Client tout document justificatif relatif &agrave; son immatriculation, le paiement de ses cotisations sociales et fiscales ainsi que l&rsquo;emploi de ses pr&eacute;pos&eacute;s.</p>
	<h3>
		7.3 Mises &agrave; jour</h3>
	<p>
		Pendant toute la dur&eacute;e du Contrat, le Client b&eacute;n&eacute;ficie des mises &agrave; jour de la plateforme CALS qui sont d&eacute;velopp&eacute;es et diffus&eacute;es par CALS, &agrave; l&rsquo;exclusion de toute nouvelle version (c.-&agrave;-d. &eacute;volution majeure) de la Plateforme CALS.</p>
	<p>
		Le Client accepte, en cons&eacute;quence, que CALS puisse, sans pr&eacute;avis et &agrave; tout moment, modifier une ou plusieurs fonctionnalit&eacute;s de la plateforme CALS.</p>
	<div>
		<h2>
			Article 8 Engagements du Client</h2>
	</div>
	<p>
		Le Client s&rsquo;engage &agrave;&nbsp;:</p>
	<ol>
		<li>
			disposer des agr&eacute;ments et autorisations n&eacute;cessaires pour r&eacute;aliser des op&eacute;rations de Syndication Bancaire&nbsp;;</li>
		<li>
			respecter l&rsquo;ensemble des dispositions l&eacute;gislatives, r&egrave;glementaires et d&eacute;ontologiques relatives &agrave; la lutte contre le blanchiment de capitaux et le financement du terrorisme. Le Client se porte fort du respect de l&rsquo;ensemble de ces dispositions par les Utilisateurs Autoris&eacute;s&nbsp;;</li>
		<li>
			se conformer aux codes de bonne conduite applicables &agrave; la Syndication Bancaire&nbsp;sur le March&eacute; Primaire et sur le March&eacute; Secondaire&nbsp;;</li>
		<li>
			ne pas transmettre par le biais de la Plateforme CALS des contenus &agrave; caract&egrave;re illicite, mena&ccedil;ant, humiliant, diffamatoire, obsc&egrave;ne, haineux, pornographique ou blasph&eacute;matoire, ou tout autre message qui pourrait constituer un crime ou un d&eacute;lit, engager la responsabilit&eacute; civile, porter atteinte &agrave; la l&eacute;gislation ou inciter au faire, ou encore des contenus qui pourraient &ecirc;tre utilis&eacute;s &agrave; toute fin contraire &agrave; la loi ou aux pr&eacute;sentes Conditions G&eacute;n&eacute;rales&nbsp;;</li>
		<li>
			ne pas r&eacute;aliser par le biais de la Plateforme CALS des op&eacute;rations illicites qui pourraient constituer un crime ou un d&eacute;lit, engager la responsabilit&eacute; civile, porter atteinte &agrave; la l&eacute;gislation ou inciter au faire, ou encore des contenus qui pourraient &ecirc;tre utilis&eacute;s &agrave; toute fin contraire &agrave; la loi ou aux pr&eacute;sentes Conditions G&eacute;n&eacute;rales&nbsp;;</li>
		<li>
			respecter scrupuleusement l&rsquo;ensemble des obligations et dispositions l&eacute;gislatives applicables aux &eacute;tablissements de cr&eacute;dit&nbsp;;</li>
		<li>
			coop&eacute;rer en toute bonne foi pour faciliter les interventions de CALS, notamment en lui communiquant toutes les informations pertinentes ou demand&eacute;es dans un d&eacute;lai permettant &agrave; CALS de remplir ses obligations&nbsp;;</li>
		<li>
			assister CALS dans le cadre de la fourniture des Services, par le biais de ses personnels qualifi&eacute;s et comp&eacute;tents&nbsp;;</li>
		<li>
			ne pas utiliser la Plateforme CALS de mani&egrave;re &agrave; ce que, du point de vue de CALS, les performances ou les fonctionnalit&eacute;s de la Plateforme CALS, ou de tout autre syst&egrave;me informatique ou r&eacute;seau utilis&eacute; par CALS ou par un quelconque tiers, soient impact&eacute;s n&eacute;gativement ou que les utilisateurs de la Plateforme CALS soient n&eacute;gativement affect&eacute;s&nbsp;;</li>
		<li>
			de charger ou transmettre sur la Plateforme CALS ou utiliser tout &eacute;quipement, logiciel ou routine qui contienne des virus, chevaux de Troie, vers, bombes &agrave; retardement ou autres programmes et proc&eacute;d&eacute;s destin&eacute;s &agrave; endommager, interf&eacute;rer ou tenter d&rsquo;interf&eacute;rer avec le fonctionnement normal de la Plateforme CALS, ou s&rsquo;approprier la Plateforme CALS, ou encore recourir &agrave; n&rsquo;importe quel moyen pour causer une saturation de nos syst&egrave;mes ou porter atteinte aux droits de tiers&nbsp;;</li>
		<li>
			disposer d&rsquo;un navigateur Internet &agrave; jour ainsi que d&rsquo;une connexion Internet haut d&eacute;bit dont les frais restent &agrave; sa charge.</li>
	</ol>
	<p>
		CALS ne pourra voir sa responsabilit&eacute; engag&eacute;e par un manquement par le Client ou les Utilisateurs Autoris&eacute;s &agrave; la l&eacute;gislation applicable.</p>
	<div>
		<h2>
			Article 9 Propri&eacute;t&eacute; intellectuelle</h2>
	</div>
	<h3>
		9.1 Plateforme CALS</h3>
	<p>
		Le Client reconna&icirc;t que la Plateforme CALS, en ce compris tous correctifs, solutions de contournement, mises &agrave; jour, mises &agrave; niveau, am&eacute;liorations et modifications mis &agrave; la disposition du Client, ainsi que tous les secrets commerciaux, droits d&rsquo;auteur, brevets, marques, noms commerciaux et autre droits de propri&eacute;t&eacute; intellectuelle y aff&eacute;rents restent &agrave; tout moment la propri&eacute;t&eacute; enti&egrave;re et exclusive de CALS et qu&rsquo;aucune des stipulations du Contrat ne saurait &ecirc;tre interpr&eacute;t&eacute;e comme un quelconque transfert de l&rsquo;un de ces droits au profit du Client.</p>
	<h3>
		9.2 Savoir-faire</h3>
	<p>
		Toute id&eacute;e, savoir-faire ou technique qui a pu &ecirc;tre d&eacute;velopp&eacute; par CALS sont la propri&eacute;t&eacute; exclusive de CALS. CALS peut, &agrave; sa seule discr&eacute;tion, d&eacute;velopper, utiliser, commercialiser et licencier tout &eacute;l&eacute;ment similaire ou en relation avec les d&eacute;veloppements r&eacute;alis&eacute;s par CALS pour le Client. CALS n&rsquo;a aucune obligation de r&eacute;v&eacute;ler toute id&eacute;e, savoir-faire ou technique qui a pu &ecirc;tre d&eacute;velopp&eacute; par CALS et que CALS consid&egrave;re comme &eacute;tant confidentiel et &eacute;tant sa propri&eacute;t&eacute;.</p>
	<p>
		Toutefois, CALS reconna&icirc;t le savoir-faire des Arrangeurs en mati&egrave;re d&rsquo;op&eacute;rations de Syndication Bancaire et ne consid&egrave;re pas que cette comp&eacute;tence lui appartienne.</p>
	<div>
		<ol>
			<li>
				<strong>Disponibilit&eacute; des Services</strong></li>
		</ol>
	</div>
	<p>
		Les services propos&eacute;s par CALS sont accessibles &agrave; distance, par le r&eacute;seau Internet.</p>
	<p>
		Le Client fait son affaire personnelle de la mise en place des moyens informatiques et de t&eacute;l&eacute;communications permettant l&rsquo;acc&egrave;s &agrave; la Plateforme CALS. Ils conservent &agrave; leur charge les frais de t&eacute;l&eacute;communication lors de l&rsquo;acc&egrave;s &agrave; Internet lors de l&rsquo;utilisation de la Plateforme CALS.</p>
	<p>
		Le Client reconna&icirc;t express&eacute;ment qu&rsquo;il est averti des al&eacute;as techniques qui peuvent affecter le r&eacute;seau Internet et entra&icirc;ner des ralentissements ou des indisponibilit&eacute;s rendant la connexion impossible. CALS ne peut &ecirc;tre tenue responsable des difficult&eacute;s d&rsquo;acc&egrave;s aux services dus &agrave; des perturbations du r&eacute;seau Internet.</p>
	<p>
		CALS se r&eacute;serve le droit, sans pr&eacute;avis ni indemnit&eacute;, de suspendre temporairement l&rsquo;acc&egrave;s &agrave; la Plateforme CALS lors de la survenance de pannes &eacute;ventuelles ou de toute op&eacute;ration de maintenance n&eacute;cessaire &agrave; son bon fonctionnement.</p>
	<p>
		CALS peut apporter &agrave; la Plateforme CALS toutes les modifications et am&eacute;liorations qu&rsquo;elle jugera n&eacute;cessaires.</p>
	<div>
		<h2>
			Article 11&nbsp;Protection des donn&eacute;es &agrave; caract&egrave;re personnel</h2>
	</div>
	<h3>
		11.1 Traitement des Donn&eacute;es Client</h3>
	<p>
		Le Client, dans le cadre de ses activit&eacute;s de Syndication Bancaire, met en &oelig;uvre des traitements automatis&eacute;s de donn&eacute;es &agrave; caract&egrave;re personnel au sens de la loi n&deg; 78-17 du 6 janvier 1978 relative &agrave; l&rsquo;informatique, aux fichiers et aux libert&eacute;s et du RGPD. Il a souhait&eacute; confier certains aspects techniques de ces traitements &agrave; CALS dans les conditions d&eacute;finies au Contrat.</p>
	<p>
		CALS traite les Donn&eacute;es Clients pour le compte du Client afin de lui fournir les Services.</p>
	<h4>
		11.1.1 Engagements du Client</h4>
	<p>
		Le Client s&rsquo;engage dans le cadre de l&rsquo;ex&eacute;cution du Contrat &agrave;&nbsp;:</p>
	<ol>
		<li>
			n&rsquo;int&eacute;grer dans les Donn&eacute;es Clients que des informations strictement n&eacute;cessaires &agrave; la bonne ex&eacute;cution des Services par CALS&nbsp;;</li>
		<li>
			documenter par &eacute;crit toute instruction concernant le traitement des Donn&eacute;es Clients par CALS&nbsp;;</li>
		<li>
			se conformer aux dispositions de la Loi n&deg; 78-17, du RGPD, de la LCENet plus g&eacute;n&eacute;ralement &agrave; la r&eacute;glementation applicable en France&nbsp;;</li>
		<li>
			superviser le traitement des Donn&eacute;es Clients, y compris en r&eacute;alisant des audits selon les modalit&eacute;s pr&eacute;alablement d&eacute;finies avec CALS.</li>
	</ol>
	<p>
		CALS ne pourra voir sa responsabilit&eacute; engag&eacute;e pour un manquement par le Client &agrave; la l&eacute;gislation applicable sauf lorsque la loi prescrit express&eacute;ment le contraire.</p>
	<p>
		Il appartient au Client de fournir toutes informations pertinentes aux personnes concern&eacute;es par les op&eacute;rations de traitement au moment de la collecte des donn&eacute;es et de s&rsquo;assurer que le traitement mis en &oelig;uvre repose sur une base l&eacute;gale.</p>
	<h4>
		11.1.2 Engagements de CALS</h4>
	<p>
		Conform&eacute;ment aux articles 28 et 32 du RGPD, CALS s&rsquo;engage &agrave;&nbsp;:</p>
	<ol>
		<li>
			prendre et &agrave; maintenir toutes mesures utiles, et notamment les mesures techniques et d&rsquo;organisation appropri&eacute;es, pour pr&eacute;server la s&eacute;curit&eacute; et la confidentialit&eacute; des donn&eacute;es personnelles qui lui sont confi&eacute;es par le Client pour la fourniture des Services, afin d&rsquo;emp&ecirc;cher qu&rsquo;elles ne soient d&eacute;form&eacute;es, alt&eacute;r&eacute;es, endommag&eacute;es, diffus&eacute;es ou que des personnes non autoris&eacute;es y aient acc&egrave;s&nbsp;;</li>
		<li>
			veiller &agrave; ce que les personnes autoris&eacute;es &agrave; traiter les donn&eacute;es &agrave; caract&egrave;re personnel pour son compte, en plus d&rsquo;avoir re&ccedil;u la formation n&eacute;cessaire en mati&egrave;re de protection des donn&eacute;es &agrave; caract&egrave;re personnel, respectent la confidentialit&eacute; ou soient soumises &agrave; une obligation l&eacute;gale appropri&eacute;e de confidentialit&eacute;&nbsp;;</li>
		<li>
			respecter les dispositions l&eacute;gales applicables et relatives aux conditions de traitement et/ou &agrave; la destination des donn&eacute;es qui lui ont &eacute;t&eacute; communiqu&eacute;es par le Client ou auxquelles il aura acc&egrave;s dans le cadre de la fourniture des Services&nbsp;;</li>
		<li>
			n&rsquo;agir que sur la seule instruction document&eacute;e du Client pour la r&eacute;alisation du traitement des donn&eacute;es personnelles concern&eacute;es&nbsp;;</li>
		<li>
			exploiter les informations nominatives collect&eacute;es ou auxquelles il aura pu avoir acc&egrave;s pour les seuls besoins de la fourniture au Client des Services&nbsp;;</li>
		<li>
			ne pas exploiter pour des finalit&eacute;s contraires aux Conditions G&eacute;n&eacute;rales les informations nominatives collect&eacute;es ou auxquelles il aura pu avoir acc&egrave;s dans le cadre de l&rsquo;ex&eacute;cution des Conditions G&eacute;n&eacute;rales conform&eacute;ment aux dispositions l&eacute;gales applicables, et &agrave; ne les transf&eacute;rer qu&rsquo;&agrave; un tiers indiqu&eacute; ou autoris&eacute; par le Client&nbsp;;</li>
		<li>
			ne pas revendre ou c&eacute;der de donn&eacute;es qui ont un caract&egrave;re strictement confidentiel sauf &agrave; ce que les donn&eacute;es utilis&eacute;es par CALS ne puissent permettre &agrave; aucun moment d&rsquo;identifier un Utilisateur Autoris&eacute; du Client et d&egrave;s lors que ces donn&eacute;es soient utilis&eacute;es afin de r&eacute;aliser des statistiques anonymes&nbsp;;</li>
		<li>
			le Client, dans la mesure du possible, par la mise en place de mesures techniques et organisationnelles appropri&eacute;es, ainsi qu&rsquo;&agrave; s&rsquo;acquitter de son obligation de donner suite aux demandes dont les personnes concern&eacute;es le saisissent en vue d&rsquo;exercer leurs droits d&rsquo;acc&egrave;s, de rectification, d&rsquo;effacement, d&rsquo;opposition, de limitation et &agrave; la portabilit&eacute; des donn&eacute;es&nbsp;;</li>
		<li>
			aider le Client, dans la mesure du possible et compte tenu des informations qui lui ont &eacute;t&eacute; communiqu&eacute;es par ce dernier, &agrave; respecter son obligation de&nbsp;: (a)&nbsp;notifier &agrave; l&rsquo;autorit&eacute; de contr&ocirc;le une violation de donn&eacute;es &agrave; caract&egrave;re personnel&nbsp;; (b)&nbsp;communiquer &agrave; la personne concern&eacute;e une violation de donn&eacute;es &agrave; caract&egrave;re personnel&nbsp;; (c)&nbsp;r&eacute;aliser une &eacute;tude d&rsquo;impact relative &agrave; la protection des donn&eacute;es.</li>
	</ol>
	<p>
		CALS se r&eacute;serve la possibilit&eacute; de confier l&rsquo;ex&eacute;cution de tout ou partie des prestations du Contrat &agrave; un ou des sous-traitant(s) ult&eacute;rieur(s) &agrave; condition qu&rsquo;ils aient &eacute;t&eacute; valid&eacute;s &ndash; pr&eacute;alablement et par &eacute;crit &ndash; par le Client et dans ce cadre &agrave; leur faire souscrire des engagements &eacute;quivalents aux stipulations du pr&eacute;sent article 11.1.</p>
	<p>
		Le Client autorise express&eacute;ment CALS &agrave; faire appel&nbsp;:</p>
	<ol>
		<li>
			&agrave; la soci&eacute;t&eacute; DocuSign &ndash; prestataire de signature &eacute;lectronique &ndash; afin de mettre en &oelig;uvre la signature &eacute;lectronique des Contrats de Pr&ecirc;t, des Conventions de Sous-Participation et les accords de confidentialit&eacute; par les Utilisateurs Autoris&eacute;s&nbsp;;</li>
		<li>
			&agrave; la soci&eacute;t&eacute; Amazon Web Services afin d&rsquo;h&eacute;berger la Plateforme CALS.</li>
		<li>
			<strong>Traitement de donn&eacute;es par</strong><strong>CALS</strong></li>
	</ol>
	<p>
		Par ailleurs, CALS, dans ses relations avec le Client, est amen&eacute;e &agrave; traiter, pour son propre compte, des donn&eacute;es &agrave; caract&egrave;re personnel de pr&eacute;pos&eacute;s, dirigeants, sous-traitants, Utilisateurs invit&eacute;s, agents et/ou prestataires du Client.</p>
	<p>
		Dans ce cadre, les personnels et Utilisateurs Invit&eacute;s du Client b&eacute;n&eacute;ficient d&rsquo;un droit d&rsquo;acc&egrave;s et, le cas &eacute;ch&eacute;ant, de rectification, de suppression ou de portabilit&eacute; des donn&eacute;es les concernant. Ils disposent, aussi, du droit de d&eacute;finir des directives relatives au sort de leurs donn&eacute;es &agrave; caract&egrave;re personnel apr&egrave;s leur mort.</p>
	<p>
		Par ailleurs, les personnels et Utilisateurs Invit&eacute;s du Client pourront s&rsquo;opposer pour des raisons l&eacute;gitimes au traitement des donn&eacute;es personnelles les concernant ou encore, le limiter.</p>
	<p>
		L&rsquo;exercice de ces droits s&rsquo;effectue &agrave; tout moment en &eacute;crivant &agrave; CALS par email &agrave; l&rsquo;adresse <strong>[&bull;]</strong>.</p>
	<p>
		En sus, les personnels et Utilisateurs Invit&eacute;s du Client disposent de la possibilit&eacute; d&rsquo;introduire une r&eacute;clamation aupr&egrave;s d&#39;une autorit&eacute; de contr&ocirc;le.</p>
	<p>
		Le Client s&rsquo;engage &agrave; informer ses pr&eacute;pos&eacute;s, dirigeants, sous-traitants, agents, Utilisateurs Invit&eacute;s et/ou prestataires desdits droits et de leur communiquer l&rsquo;ensemble des informations impos&eacute;es par les articles 13 et 14 du RGPD.</p>
	<div>
		<h2>
			Article 12 Garantie</h2>
	</div>
	<p>
		CALS n&rsquo;accorde aucune garantie qui ne soit express&eacute;ment vis&eacute;e au Contrat.</p>
	<p>
		CALS d&eacute;clare &ecirc;tre titulaire de l&rsquo;ensemble des droits de propri&eacute;t&eacute; intellectuelle relatifs &agrave; la Plateforme CALS et que la Plateforme CALS ne constitue pas une contrefa&ccedil;on d&rsquo;une &oelig;uvre pr&eacute;existante.</p>
	<p>
		En cons&eacute;quence, CALS garantit le Client contre toute action, r&eacute;clamation, revendication ou opposition de la part de toute personne invoquant un droit de propri&eacute;t&eacute; intellectuelle ou un acte de concurrence d&eacute;loyale et/ou parasitaire en France, sous r&eacute;serve que CALS soit notifi&eacute;e par le Client d&rsquo;une telle action.</p>
	<p>
		CALS sera seule autoris&eacute;e &agrave; avoir le contr&ocirc;le de toute d&eacute;fense et/ou de toute transaction dans le cadre d&rsquo;une telle action. &Agrave; ce titre, CALS s&rsquo;engage &agrave; intervenir dans toutes les proc&eacute;dures et/ou les actions qui seraient initi&eacute;es &agrave; l&rsquo;encontre du Client sur fondement d&rsquo;une violation d&rsquo;un droit de propri&eacute;t&eacute; intellectuelle par la Plateforme CALSet/ou d&rsquo;un acte de concurrence d&eacute;loyale et/ou parasitisme commis par CALS en relation avec la Plateforme CALS. Le Client s&rsquo;engage &agrave; fournir &agrave; CALS toute information ou assistance raisonnable dans le cadre de cette d&eacute;fense.</p>
	<p>
		Dans l&rsquo;hypoth&egrave;se o&ugrave;, &agrave; l&rsquo;issue de cette action ou proc&eacute;dure, la Plateforme CALS serait consid&eacute;r&eacute;e, par une d&eacute;cision de justice insusceptible de recours, comme constituant une contrefa&ccedil;on, CALS s&rsquo;engage, &agrave; ses frais et sa discr&eacute;tion, &agrave;&nbsp;:</p>
	<ol>
		<li>
			obtenir pour le Client le droit de continuer &agrave; utiliser la Plateforme CALS&nbsp;; ou</li>
		<li>
			remplacer la Plateforme CALS par un logiciel &eacute;quivalent et non contrefaisant&nbsp;; ou</li>
		<li>
			modifier tout ou partie de la Plateforme CALS contrefaisant de sorte qu&rsquo;elle ne soit plus contrefaisante&nbsp;; ou</li>
		<li>
			r&eacute;silier le Contrat.</li>
	</ol>
	<p>
		Cependant, CALS ne sera pas tenue d&rsquo;indemniser le Client si l&rsquo;action, la r&eacute;clamation, la revendication ou l&rsquo;opposition est due &agrave;&nbsp;:</p>
	<ol>
		<li>
			une utilisation non-conforme, une modification ou une adaptation de la plateforme CALS par le Client&nbsp;;</li>
		<li>
			le d&eacute;faut de mise en &oelig;uvre par le Client d&rsquo;un correctif, d&rsquo;une mise &agrave; jour, d&rsquo;une nouvelle version et/ou de toute autre forme de correction ou d&rsquo;am&eacute;lioration de la Plateforme CALS&nbsp;;</li>
		<li>
			l&rsquo;utilisation par le Client de la Plateforme CALS en combinaison avec des produits, mat&eacute;riels, logiciels qui ne sont pas la propri&eacute;t&eacute; de CALS ou qui n&rsquo;ont pas &eacute;t&eacute; d&eacute;velopp&eacute;s par CALS&nbsp;;</li>
		<li>
			l&rsquo;utilisation, la commercialisation ou la mise &agrave; disposition de la Plateforme CALS au b&eacute;n&eacute;fice d&rsquo;un tiers&nbsp;;</li>
		<li>
			des informations, des instructions, des sp&eacute;cifications ou des mat&eacute;riels fournis par le Client ou un tiers.</li>
	</ol>
	<div>
		<h2>
			Article 13 Responsabilit&eacute;</h2>
	</div>
	<p>
		Il est express&eacute;ment convenu entre les Parties que les stipulations du pr&eacute;sent Article 13ont &eacute;t&eacute; convenues entre les Parties dans le cadre d&rsquo;une n&eacute;gociation globale, de sorte que chacune des Parties les consid&egrave;re comme justifi&eacute;es et proportionn&eacute;es au regard de ses autres engagements aux termes du Contrat.</p>
	<p>
		CALS se limite &agrave; fournir des services de mise en relation d&rsquo;&eacute;tablissements bancaires ou de fonds d&rsquo;investissement afin de leur permettre de r&eacute;aliser des op&eacute;rations de Syndication Bancaire.</p>
	<p>
		La responsabilit&eacute; de CALS ne saurait &ecirc;tre engag&eacute;e du fait du non-respect par le Client de ses obligations l&eacute;gales et r&eacute;glementaires.</p>
	<p>
		Le Client reconna&icirc;t que CALS n&rsquo;est ni partie, ni garant de la bonne ex&eacute;cution de tout contrat qu&rsquo;il sera amen&eacute; &agrave; conclure par le biais de la Plateforme CALS avec un autre Etablissement Autoris&eacute; ou un Utilisateur Invit&eacute;.</p>
	<p>
		Le Client est seul responsable de la conclusion et de l&rsquo;ex&eacute;cution des contrats relatifs &agrave; de la Syndication Bancaire qu&rsquo;il conclut avec des autres Etablissements Autoris&eacute;s et/ou Utilisateurs Invit&eacute;s par l&rsquo;interm&eacute;diaire de la Plateforme CALS, CALS n&rsquo;intervenant que pour les mettre en relation. La conclusion et l&rsquo;ex&eacute;cution de ces contrats, qui interviennent directement entre le Client et les Etablissements Autoris&eacute;s et/ou les Utilisateurs Invit&eacute;s s&rsquo;op&egrave;rent &agrave; l&rsquo;initiative et sous la responsabilit&eacute; exclusive de ces derniers.</p>
	<p>
		A ce titre CALS ne saurait assumer une quelconque responsabilit&eacute; au titre du non-respect par (i) le Client de ses engagements contractuels vis-&agrave;-vis d&rsquo;un Emprunteur, d&rsquo;un Etablissement Autoris&eacute; ou d&rsquo;un Utilisateur Invit&eacute; ou (ii) un autre Etablissement Autoris&eacute; ou un Utilisateur Invit&eacute; &agrave; ses obligations contractuelles.</p>
	<p>
		CALS ne saurait assumer une quelconque responsabilit&eacute; au titre des relations entre le Client et les autres Etablissements Autoris&eacute;s ou les Utilisateurs Invit&eacute;s intervenant suite &agrave; leur mise en relation. A cet &eacute;gard, le Client est r&eacute;put&eacute; accepter et assumer pleinement les risques r&eacute;sultant de ses interactions avec d&rsquo;autres Etablissements Autoris&eacute;s et/ou Utilisateurs Invit&eacute;s ou li&eacute;s &agrave; la Syndication Bancaire.</p>
	<p>
		CALS ne saurait &ecirc;tre tenue responsable que des dommages directs et pr&eacute;visibles au sens des articles 1231-3 et 1231-4 du Code civil engendr&eacute;s par un manquement de CALS &agrave; ses obligations aux termes du Contrat.</p>
	<p>
		Il est express&eacute;ment convenu entre les Parties que CALS ne saurait &ecirc;tre responsable de tout gain manqu&eacute;&nbsp;; perte de chiffre d&rsquo;affaires ou de b&eacute;n&eacute;fice&nbsp;; perte de client&egrave;le&nbsp;; perte d&rsquo;une chance&nbsp;; perte en termes d&rsquo;images ou de renomm&eacute;e&nbsp;; de tout co&ucirc;t en vue de l&rsquo;obtention d&rsquo;un produit, d&rsquo;un logiciel, d&rsquo;un service ou d&rsquo;une technologie de substitution&nbsp;; ou de toute difficult&eacute; technique dans l&rsquo;acheminement d&rsquo;un message via Internet.</p>
	<p>
		La responsabilit&eacute; de CALS ne pourra &ecirc;tre recherch&eacute;e en cas de pr&eacute;judice r&eacute;sultant d&rsquo;une destruction de fichiers ou de donn&eacute;es provenant de l&rsquo;utilisation par le Client d&rsquo;un ou plusieurs &eacute;l&eacute;ments fournis dans le cadre des Services.</p>
	<p>
		La responsabilit&eacute; totale cumul&eacute;e de CALS, tous dommages confondus et pour quelque raison que ce soit, ne pourra &ecirc;tre d&rsquo;un montant sup&eacute;rieur aux sommes effectivement per&ccedil;ues par CALS au titre du Contrat pendant les douze (12) mois pr&eacute;c&eacute;dant la survenance du dernier &eacute;v&egrave;nement dommageable.</p>
	<p>
		En tout &eacute;tat de cause, le Client ne pourra mettre en jeu la responsabilit&eacute; de CALS, du fait d&rsquo;un manquement au titre du Contrat, que pendant un d&eacute;lai de douze (12) mois &agrave; compter de la survenance du manquement en cause, ce que reconna&icirc;t et accepte express&eacute;ment le Client.</p>
	<p>
		CALS ne sera en aucun cas responsable des dommages qui d&eacute;couleraient du non-respect par le Client de ses obligations.</p>
	<div>
		<h2>
			Article 14 R&eacute;siliation</h2>
	</div>
	<p>
		Chaque Partie pourra de plein droit, sans pr&eacute;judice de tous dommages-int&eacute;r&ecirc;ts qu&rsquo;elle se r&eacute;serve le droit de solliciter judiciairement, r&eacute;silier le Contrat avec effet imm&eacute;diat en cas de manquement par l&rsquo;autre Partie &agrave; l&rsquo;une de ses obligations essentielles au titre du Contrat, et notamment en cas de d&eacute;faut de paiement des factures de Redevances dues par le Client &agrave; CALS, s&rsquo;il n&rsquo;a pas &eacute;t&eacute; rem&eacute;di&eacute; &agrave; ce manquement par la partie d&eacute;faillante dans un d&eacute;lai de trente (30) jours ouvrables &agrave; compter de la notification de ce manquement faite par l&rsquo;autre Partie, par lettre recommand&eacute;e avec demande d&rsquo;avis de r&eacute;ception.</p>
	<p>
		En cas de cessation du Contrat, quel qu&rsquo;en soit le motif, le Client devra imm&eacute;diatement cesser d&rsquo;utiliser tout &eacute;l&eacute;ment fourni dans le cadre des Services.</p>
	<p>
		En cas de r&eacute;siliation pour quelque raison que ce soit, l&rsquo;ensemble des Services r&eacute;alis&eacute;s et non encore factur&eacute;s seront dus &agrave; CALS.</p>
	<p>
		Nonobstant l&rsquo;expiration ou la r&eacute;siliation du Contrat, il est express&eacute;ment convenu entre les Parties que les articles 6, 12, 13, 15, et 18 resteront pleinement applicables entre les Parties.</p>
	<div>
		<h2>
			Article 15 R&eacute;versibilit&eacute;</h2>
	</div>
	<p>
		Dans un d&eacute;lai de quarante-cinq (45) jours &agrave; compter de l&rsquo;expiration ou de la r&eacute;siliation du Contrat, CALS s&rsquo;engage &agrave; remettre au Client une copie de l&rsquo;ensemble des Donn&eacute;es Emprunteurs disponibles sur la Plateforme CALS.</p>
	<p>
		Ces donn&eacute;es seront mises &agrave; la disposition du Client pour leur t&eacute;l&eacute;chargement et/ou remises sur un support physique, au choix de CALS.</p>
	<p>
		CALS s&rsquo;engage &agrave; fournir un export complet des Donn&eacute;es Emprunteurs sur la Plateforme CALS dans un format conforme &agrave; l&rsquo;&eacute;tat de l&rsquo;art.</p>
	<p>
		Toute fourniture d&rsquo;un export complet des Donn&eacute;es Emprunteurs, au-del&agrave; d&rsquo;un export unique post&eacute;rieurement &agrave; l&rsquo;expiration ou &agrave; la r&eacute;siliation du Contrat, sera factur&eacute;e au Client conform&eacute;ment au devis qui sera pr&eacute;alablement &eacute;tabli par CALS.</p>
	<p>
		A l&rsquo;issue de la p&eacute;riode de r&eacute;versibilit&eacute;, CALS proc&egrave;dera &agrave; l&rsquo;effacement complet des Donn&eacute;es Emprunteurs.</p>
	<p>
		Le Client reconna&icirc;t et accepte que les Donn&eacute;es March&eacute; seront conserv&eacute;es par CALS uniquement &agrave; des fins d&rsquo;am&eacute;lioration de la Plateforme CALS.</p>
	<div>
		<h2>
			Article 16 Confidentialit&eacute;</h2>
	</div>
	<h3>
		16.1 Notion d&rsquo;Information Confidentielle</h3>
	<p>
		Ne constituent pas des Informations Confidentielles&nbsp;:</p>
	<ol>
		<li>
			les informations actuellement accessibles ou devenant accessibles au public sans manquement aux termes du Contrat de la part d&rsquo;une Partie&nbsp;;</li>
		<li>
			les informations l&eacute;galement d&eacute;tenues par une Partie avant leur divulgation par l&rsquo;autre&nbsp;;</li>
		<li>
			les informations ne r&eacute;sultant ni directement ni indirectement de l&rsquo;utilisation de tout ou partie des Informations Confidentielles&nbsp;;</li>
		<li>
			les informations valablement obtenues aupr&egrave;s d&rsquo;un tiers autoris&eacute; &agrave; transf&eacute;rer ou &agrave; divulguer lesdites informations.</li>
	</ol>
	<h3>
		16.2 Engagement de Confidentialit&eacute;</h3>
	<p>
		Chaque Partie s&rsquo;engage en son nom et au nom de ses pr&eacute;pos&eacute;s, agents, sous-traitants et partenaires, pendant la dur&eacute;e du Contrat et pendant une p&eacute;riode de cinq (5) ans apr&egrave;s sa cessation, &agrave;&nbsp;:</p>
	<ol>
		<li>
			ne pas utiliser les Informations Confidentielles &agrave; des fins autres que l&rsquo;ex&eacute;cution de ses obligations conform&eacute;ment au Contrat&nbsp;;</li>
		<li>
			prendre toute pr&eacute;caution qu&rsquo;il utilise pour prot&eacute;ger ses propres informations confidentielles d&rsquo;une valeur importante, &eacute;tant pr&eacute;cis&eacute; que ces pr&eacute;cautions ne sauraient &ecirc;tre &agrave; inf&eacute;rieures &agrave; celles d&rsquo;un professionnel diligent&nbsp;;</li>
		<li>
			ne divulguer les Informations Confidentielles &agrave; quiconque, par quelque moyen que ce soit, sauf &agrave; ses pr&eacute;pos&eacute;s, agents, prestataires de service ou sous-traitants auxquels ces informations sont n&eacute;cessaires pour le respect de ses obligations par chacune des Parties.</li>
	</ol>
	<p>
		Au terme du Contrat, en raison de la survenance de son terme ou de sa r&eacute;siliation, chaque Partie devra sans d&eacute;lai remettre &agrave; l&rsquo;autre Partie toutes les Informations Confidentielles, quel que soit leur support, obtenues dans le cadre du Contrat. Chaque Partie s&rsquo;interdit d&rsquo;en conserver copie sous quelque forme que ce soit, sauf accord expr&egrave;s pr&eacute;alable et &eacute;crit de l&rsquo;autre Partie.</p>
	<div>
		<h2>
			Article 17 Stipulations diverses</h2>
	</div>
	<h3>
		17.1 Documents contractuels</h3>
	<p>
		Les documents contractuels sont, par ordre de priorit&eacute; d&eacute;croissante&nbsp;:</p>
	<ol>
		<li>
			le corps du Contrat&nbsp;;</li>
		<li>
			les Conditions Particuli&egrave;res&nbsp;;</li>
	</ol>
	<p>
		En cas de contradiction entre diff&eacute;rents documents, les stipulations du document de rang sup&eacute;rieur pr&eacute;vaudront.</p>
	<h3>
		17.2 Communication &ndash; Publicit&eacute;</h3>
	<p>
		Le Client accepte de figurer parmi r&eacute;f&eacute;rences-client de CALS et notamment que le Contrat puisse servir d&rsquo;exemple de collaboration r&eacute;ciproquement fructueuse. A cette fin, CALS est autoris&eacute;e &agrave; utiliser le nom et le logo du Client sur son site Internet et sur des brochures commerciales.</p>
	<h3>
		17.3 Cession/transfert du Contrat</h3>
	<p>
		CALS aura la possibilit&eacute; de transf&eacute;rer tout ou partie des droits et obligations r&eacute;sultant pour elle du Contrat &agrave; toute filiale &agrave; constituer, ainsi que par suite notamment de fusion, scission, apport partiel d&rsquo;actif ou cession totale ou partielle de son fonds de commerce.</p>
	<p>
		Il est express&eacute;ment convenu entre les Parties que toute modification dans la structure capitalistique de CALS, en ce compris un changement de contr&ocirc;le, sera sans effet sur l&rsquo;ex&eacute;cution du Contrat.</p>
	<p>
		Le Client n&rsquo;est pas autoris&eacute; &agrave; transf&eacute;rer tout ou partie de ses obligations aux termes du Contrat, de quelque mani&egrave;re que ce soit, sans l&rsquo;accord pr&eacute;alable, &eacute;crit et expr&egrave;s de CALS.</p>
	<h3>
		17.4 Notification &ndash; Computation des d&eacute;lais</h3>
	<p>
		Toute notification requise ou n&eacute;cessaire en application des stipulations du Contrat devra &ecirc;tre faite par &eacute;crit et sera r&eacute;put&eacute;e valablement donn&eacute;e si remise en main propre ou adress&eacute;e par lettre recommand&eacute;e avec demande d&rsquo;avis de r&eacute;ception &agrave; l&rsquo;adresse de l&rsquo;autre Partie figurant sur les Conditions Particuli&egrave;res ou &agrave; toute autre adresse notifi&eacute;e &agrave; l&rsquo;autre Partie dans les formes d&eacute;finies au pr&eacute;sent article 17.4.</p>
	<p>
		Sauf disposition particuli&egrave;re dans un article du Contrat, les d&eacute;lais sont calcul&eacute;s par jour calendaire. Tout d&eacute;lai calcul&eacute; &agrave; partir d&rsquo;une notification courra &agrave; compter de la premi&egrave;re tentative de remise au destinataire, le cachet de la Poste faisant foi.</p>
	<h3>
		17.5 Force Majeure</h3>
	<p>
		Chacune des Parties ne saurait voir sa responsabilit&eacute; engag&eacute;e pour le cas o&ugrave; l&rsquo;ex&eacute;cution de ses obligations serait retard&eacute;e, restreinte ou rendue impossible du fait de la survenance d&rsquo;un cas de Force Majeure. Il est express&eacute;ment convenu entre les Parties que les stipulations du pr&eacute;sent article 17.5ne sont pas applicables aux obligations de payer.</p>
	<p>
		Dans l&rsquo;hypoth&egrave;se de la survenance d&rsquo;une Force Majeure, l&rsquo;ex&eacute;cution des obligations de chaque Partie est suspendue. Si la Force Majeure se poursuit pendant plus d&rsquo;un (1) mois, le Contrat pourra &ecirc;tre r&eacute;sili&eacute; &agrave; la demande de la Partie la plus diligente sans pour autant que la responsabilit&eacute; d&rsquo;une Partie puisse &ecirc;tre engag&eacute;e &agrave; l&rsquo;&eacute;gard de l&rsquo;autre. Chacune des Parties supporte la charge de tous les frais qui lui incombent et qui r&eacute;sultent de la survenance de la Force Majeure.</p>
	<h3>
		17.6 Fournisseurs &ndash; Prestataires &ndash; Sous-traitants</h3>
	<p>
		Pendant toute la dur&eacute;e du Contrat, CALS sera libre de faire appel &agrave; tout fournisseur, prestataires et/ou sous-traitant de son choix.</p>
	<p>
		Le Client autorise CALS &agrave; sous-traiter en partie ou en totalit&eacute; les Services qui lui ont &eacute;t&eacute; confi&eacute;s. Le sous-traitant pourra traiter des Donn&eacute;es Emprunteur dans les conditions de l&rsquo;Article 11.1.</p>
	<p>
		Dans ce cadre, CALS restera, dans les conditions fix&eacute;es au Contrat, responsable de la fourniture des Services.</p>
	<h3>
		17.7 Convention de preuve</h3>
	<p>
		Les registres informatis&eacute;s seront conserv&eacute;s dans les syst&egrave;mes informatiques de CALS dans des conditions raisonnables de s&eacute;curit&eacute; et seront consid&eacute;r&eacute;s comme les preuves des &eacute;changes et/ou des actions r&eacute;alis&eacute;es par les Utilisateurs Autoris&eacute;s sur la Plateforme CALS, ce que le Client d&eacute;clare accepter.</p>
	<h3>
		17.8 Modification du Contrat</h3>
	<p>
		Le Contrat ne pourra &ecirc;tre modifi&eacute; que d&rsquo;un commun accord entre les Parties, par voie d&rsquo;avenant &eacute;crit, sign&eacute; par un repr&eacute;sentant habilit&eacute; de chacune des Parties.</p>
	<h3>
		17.9 Renonciation</h3>
	<p>
		Le fait que l&rsquo;une ou l&rsquo;autre des Parties n&rsquo;exerce pas l&rsquo;un quelconque de ses droits au titre des pr&eacute;sentes ne saurait emporter renonciation de sa part &agrave; son exercice, une telle renonciation ne pouvant proc&eacute;der que d&rsquo;une d&eacute;claration expresse de la Partie concern&eacute;e.</p>
	<h3>
		17.10 Validit&eacute;</h3>
	<p>
		Dans l&rsquo;hypoth&egrave;se o&ugrave; une ou plusieurs stipulations du Contrat seraient consid&eacute;r&eacute;es comme non valides par une juridiction comp&eacute;tente, les autres clauses conserveront leur port&eacute;e et effet.</p>
	<p>
		La stipulation consid&eacute;r&eacute;e comme invalide sera remplac&eacute;e par une stipulation dont le sens et la port&eacute;e seront le plus proches possibles de la clause ainsi invalid&eacute;e, tout en restant conforme &agrave; la l&eacute;gislation applicable et &agrave; la commune intention des Parties.</p>
	<h3>
		17.11&nbsp;Int&eacute;gralit&eacute;</h3>
	<p>
		Le Contrat constitue l&rsquo;int&eacute;gralit&eacute; de l&rsquo;accord entre les Parties, &agrave; l&rsquo;exclusion de tout autre document, notamment ceux pouvant &ecirc;tre &eacute;mis par le Client avant ou apr&egrave;s la signature du Contrat.</p>
	<div>
		<h2>
			Article 18 Loi applicable - juridiction comp&eacute;tente</h2>
	</div>
	<p>
		Le Contrat est r&eacute;gi par le droit fran&ccedil;ais.</p>
	<p>
		Les Parties acceptent express&eacute;ment de soumettre tout litige relatif au Contrat (en ce compris tout diff&eacute;rend concernant sa n&eacute;gociation, sa conclusion, son ex&eacute;cution, sa r&eacute;siliation et/ou sa cessation) et/ou aux relations commerciales entre les Parties ainsi qu&rsquo;&agrave; leur rupture &eacute;ventuelle, &agrave; la comp&eacute;tence exclusive des Tribunaux de Paris, nonobstant pluralit&eacute; de d&eacute;fendeurs ou appel en garantie, y compris pour les proc&eacute;dures sur requ&ecirc;te ou en r&eacute;f&eacute;r&eacute;.</p>
</div>
<div style="mso-element:comment-list">
	<div style="mso-element:comment">
		<div class="msocomtxt" id="_com_3" language="JavaScript">
<!--[if !supportAnnotations]-->		</div>
<!--[endif]-->	</div>
</div>
<!--EndFragment--><style id="dynCom" type="text/css">
<!--{cke_protected}{C}%3C!%2D%2D%20%2D%2D%3E--></style>
<style type="text/css">
<!--{cke_protected}{C}%3C!%2D%2D%0A%20%2F*%20Font%20Definitions%20*%2F%0A%20%40font-face%0A%09%7Bfont-family%3AWingdings%3B%0A%09panose-1%3A5%200%200%200%200%200%200%200%200%200%3B%0A%09mso-font-charset%3A2%3B%0A%09mso-generic-font-family%3Adecorative%3B%0A%09mso-font-pitch%3Avariable%3B%0A%09mso-font-signature%3A0%20268435456%200%200%20-2147483648%200%3B%7D%0A%40font-face%0A%09%7Bfont-family%3A%22Cambria%20Math%22%3B%0A%09panose-1%3A2%204%205%203%205%204%206%203%202%204%3B%0A%09mso-font-charset%3A0%3B%0A%09mso-generic-font-family%3Aroman%3B%0A%09mso-font-pitch%3Avariable%3B%0A%09mso-font-signature%3A-536869121%201107305727%2033554432%200%20415%200%3B%7D%0A%40font-face%0A%09%7Bfont-family%3ACalibri%3B%0A%09panose-1%3A2%2015%205%202%202%202%204%203%202%204%3B%0A%09mso-font-charset%3A0%3B%0A%09mso-generic-font-family%3Aswiss%3B%0A%09mso-font-pitch%3Avariable%3B%0A%09mso-font-signature%3A-536859905%20-1073732485%209%200%20511%200%3B%7D%0A%40font-face%0A%09%7Bfont-family%3AVerdana%3B%0A%09panose-1%3A2%2011%206%204%203%205%204%204%202%204%3B%0A%09mso-font-charset%3A0%3B%0A%09mso-generic-font-family%3Aswiss%3B%0A%09mso-font-pitch%3Avariable%3B%0A%09mso-font-signature%3A-1593833729%201073750107%2016%200%20415%200%3B%7D%0A%40font-face%0A%09%7Bfont-family%3ATahoma%3B%0A%09panose-1%3A2%2011%206%204%203%205%204%204%202%204%3B%0A%09mso-font-charset%3A0%3B%0A%09mso-generic-font-family%3Aswiss%3B%0A%09mso-font-pitch%3Avariable%3B%0A%09mso-font-signature%3A-520081665%20-1073717157%2041%200%2066047%200%3B%7D%0A%40font-face%0A%09%7Bfont-family%3A%22Lucida%20Grande%22%3B%0A%09panose-1%3A2%2011%206%200%204%205%202%202%202%204%3B%0A%09mso-font-alt%3A%22Segoe%20UI%22%3B%0A%09mso-font-charset%3A0%3B%0A%09mso-generic-font-family%3Aswiss%3B%0A%09mso-font-pitch%3Avariable%3B%0A%09mso-font-signature%3A-520090897%201342218751%200%200%20447%200%3B%7D%0A%40font-face%0A%09%7Bfont-family%3A%22Malgun%20Gothic%22%3B%0A%09panose-1%3A2%2011%205%203%202%200%200%202%200%204%3B%0A%09mso-font-charset%3A129%3B%0A%09mso-generic-font-family%3Aswiss%3B%0A%09mso-font-pitch%3Avariable%3B%0A%09mso-font-signature%3A-1879048145%20701988091%2018%200%20524289%200%3B%7D%0A%40font-face%0A%09%7Bfont-family%3A%22%5C%40Malgun%20Gothic%22%3B%0A%09mso-font-charset%3A129%3B%0A%09mso-generic-font-family%3Aswiss%3B%0A%09mso-font-pitch%3Avariable%3B%0A%09mso-font-signature%3A-1879048145%20701988091%2018%200%20524289%200%3B%7D%0A%20%2F*%20Style%20Definitions%20*%2F%0A%20p.MsoNormal%2C%20li.MsoNormal%2C%20div.MsoNormal%0A%09%7Bmso-style-unhide%3Ano%3B%0A%09mso-style-qformat%3Ayes%3B%0A%09mso-style-parent%3A%22%22%3B%0A%09margin-top%3A0cm%3B%0A%09margin-right%3A0cm%3B%0A%09margin-bottom%3A6.0pt%3B%0A%09margin-left%3A0cm%3B%0A%09text-align%3Ajustify%3B%0A%09mso-pagination%3Awidow-orphan%3B%0A%09font-size%3A8.0pt%3B%0A%09mso-bidi-font-size%3A12.0pt%3B%0A%09font-family%3A%22Tahoma%22%2Csans-serif%3B%0A%09mso-fareast-font-family%3A%22Times%20New%20Roman%22%3B%0A%09mso-bidi-font-family%3A%22Times%20New%20Roman%22%3B%0A%09mso-fareast-language%3AFR%3B%7D%0Ah1%0A%09%7Bmso-style-unhide%3Ano%3B%0A%09mso-style-qformat%3Ayes%3B%0A%09mso-style-link%3A%22Titre%201%20Car%22%3B%0A%09mso-style-next%3ANormal%3B%0A%09margin-top%3A10.0pt%3B%0A%09margin-right%3A0cm%3B%0A%09margin-bottom%3A6.0pt%3B%0A%09margin-left%3A36.0pt%3B%0A%09text-align%3Ajustify%3B%0A%09text-indent%3A-36.0pt%3B%0A%09mso-pagination%3Awidow-orphan%3B%0A%09page-break-after%3Aavoid%3B%0A%09mso-outline-level%3A1%3B%0A%09mso-list%3Al2%20level1%20lfo6%3B%0A%09tab-stops%3Alist%2036.0pt%20left%202.0cm%3B%0A%09border%3Anone%3B%0A%09mso-border-bottom-alt%3Asolid%20%230099CC%201.5pt%3B%0A%09padding%3A0cm%3B%0A%09mso-padding-alt%3A0cm%200cm%201.0pt%200cm%3B%0A%09font-size%3A8.0pt%3B%0A%09mso-bidi-font-size%3A16.0pt%3B%0A%09font-family%3A%22Tahoma%22%2Csans-serif%3B%0A%09mso-fareast-font-family%3A%22Times%20New%20Roman%22%3B%0A%09mso-bidi-font-family%3A%22Times%20New%20Roman%22%3B%0A%09font-variant%3Asmall-caps%3B%0A%09mso-font-kerning%3A16.0pt%3B%0A%09mso-fareast-language%3AFR%3B%0A%09font-weight%3Abold%3B%0A%09mso-bidi-font-weight%3Anormal%3B%7D%0Ah2%0A%09%7Bmso-style-unhide%3Ano%3B%0A%09mso-style-qformat%3Ayes%3B%0A%09mso-style-link%3A%22Titre%202%20Car%22%3B%0A%09mso-style-next%3ANormal%3B%0A%09margin-top%3A0cm%3B%0A%09margin-right%3A0cm%3B%0A%09margin-bottom%3A6.0pt%3B%0A%09margin-left%3A36.85pt%3B%0A%09text-align%3Ajustify%3B%0A%09text-indent%3A-36.85pt%3B%0A%09mso-pagination%3Awidow-orphan%3B%0A%09page-break-after%3Aavoid%3B%0A%09mso-outline-level%3A2%3B%0A%09mso-list%3Al2%20level2%20lfo6%3B%0A%09tab-stops%3Alist%2036.85pt%3B%0A%09font-size%3A8.0pt%3B%0A%09mso-bidi-font-size%3A14.0pt%3B%0A%09font-family%3A%22Tahoma%22%2Csans-serif%3B%0A%09mso-fareast-font-family%3A%22Times%20New%20Roman%22%3B%0A%09mso-bidi-font-family%3A%22Times%20New%20Roman%22%3B%0A%09mso-fareast-language%3AFR%3B%0A%09font-weight%3Abold%3B%0A%09mso-bidi-font-weight%3Anormal%3B%7D%0Ah3%0A%09%7Bmso-style-unhide%3Ano%3B%0A%09mso-style-qformat%3Ayes%3B%0A%09mso-style-parent%3A%22Titre%202%22%3B%0A%09mso-style-link%3A%22Titre%203%20Car%22%3B%0A%09mso-style-next%3ANormal%3B%0A%09margin-top%3A0cm%3B%0A%09margin-right%3A0cm%3B%0A%09margin-bottom%3A6.0pt%3B%0A%09margin-left%3A36.85pt%3B%0A%09text-align%3Ajustify%3B%0A%09text-indent%3A-36.85pt%3B%0A%09mso-pagination%3Awidow-orphan%3B%0A%09page-break-after%3Aavoid%3B%0A%09mso-outline-level%3A3%3B%0A%09mso-list%3Al2%20level3%20lfo6%3B%0A%09tab-stops%3Alist%2036.85pt%3B%0A%09font-size%3A8.0pt%3B%0A%09mso-bidi-font-size%3A14.0pt%3B%0A%09font-family%3A%22Tahoma%22%2Csans-serif%3B%0A%09mso-fareast-font-family%3A%22Times%20New%20Roman%22%3B%0A%09mso-bidi-font-family%3A%22Times%20New%20Roman%22%3B%0A%09mso-fareast-language%3AFR%3B%0A%09font-weight%3Anormal%3B%0A%09font-style%3Aitalic%3B%0A%09mso-bidi-font-style%3Anormal%3B%7D%0Ah4%0A%09%7Bmso-style-unhide%3Ano%3B%0A%09mso-style-qformat%3Ayes%3B%0A%09mso-style-link%3A%22Titre%204%20Car%22%3B%0A%09mso-style-next%3ANormal%3B%0A%09margin-top%3A0cm%3B%0A%09margin-right%3A0cm%3B%0A%09margin-bottom%3A30.0pt%3B%0A%09margin-left%3A0cm%3B%0A%09text-align%3Acenter%3B%0A%09text-indent%3A0cm%3B%0A%09line-height%3A150%25%3B%0A%09page-break-before%3Aalways%3B%0A%09mso-pagination%3Anone%3B%0A%09mso-outline-level%3A4%3B%0A%09mso-list%3Al7%20level1%20lfo7%3B%0A%09border%3Anone%3B%0A%09mso-border-bottom-alt%3Asolid%20%230099CC%201.5pt%3B%0A%09padding%3A0cm%3B%0A%09mso-padding-alt%3A0cm%200cm%201.0pt%200cm%3B%0A%09font-size%3A8.0pt%3B%0A%09mso-bidi-font-size%3A10.0pt%3B%0A%09font-family%3A%22Tahoma%22%2Csans-serif%3B%0A%09mso-fareast-font-family%3A%22Times%20New%20Roman%22%3B%0A%09mso-bidi-font-family%3A%22Times%20New%20Roman%22%3B%0A%09font-variant%3Asmall-caps%3B%0A%09mso-fareast-language%3AFR%3B%0A%09mso-bidi-language%3AFR%3B%0A%09font-weight%3Abold%3B%0A%09mso-bidi-font-weight%3Anormal%3B%7D%0Ah5%0A%09%7Bmso-style-unhide%3Ano%3B%0A%09mso-style-qformat%3Ayes%3B%0A%09mso-style-link%3A%22Titre%205%20Car%22%3B%0A%09mso-style-next%3ANormal%3B%0A%09margin-top%3A18.0pt%3B%0A%09margin-right%3A0cm%3B%0A%09margin-bottom%3A6.0pt%3B%0A%09margin-left%3A1.0cm%3B%0A%09text-align%3Ajustify%3B%0A%09text-indent%3A-1.0cm%3B%0A%09mso-pagination%3Anone%3B%0A%09page-break-after%3Aavoid%3B%0A%09mso-outline-level%3A5%3B%0A%09mso-list%3Al7%20level2%20lfo7%3B%0A%09tab-stops%3A21.3pt%20list%201.0cm%3B%0A%09font-size%3A8.0pt%3B%0A%09mso-bidi-font-size%3A10.0pt%3B%0A%09font-family%3A%22Tahoma%22%2Csans-serif%3B%0A%09mso-fareast-font-family%3A%22Times%20New%20Roman%22%3B%0A%09mso-bidi-font-family%3A%22Times%20New%20Roman%22%3B%0A%09font-variant%3Asmall-caps%3B%0A%09mso-fareast-language%3AFR%3B%0A%09mso-bidi-language%3AFR%3B%0A%09font-weight%3Abold%3B%0A%09mso-bidi-font-weight%3Anormal%3B%7D%0Ah6%0A%09%7Bmso-style-priority%3A9%3B%0A%09mso-style-qformat%3Ayes%3B%0A%09mso-style-link%3A%22Titre%206%20Car%22%3B%0A%09mso-style-next%3ANormal%3B%0A%09margin-top%3A0cm%3B%0A%09margin-right%3A0cm%3B%0A%09margin-bottom%3A6.0pt%3B%0A%09margin-left%3A36.85pt%3B%0A%09text-align%3Ajustify%3B%0A%09text-indent%3A-36.85pt%3B%0A%09mso-pagination%3Anone%3B%0A%09page-break-after%3Aavoid%3B%0A%09mso-outline-level%3A6%3B%0A%09mso-list%3Al7%20level3%20lfo7%3B%0A%09tab-stops%3A21.3pt%20list%2036.85pt%3B%0A%09font-size%3A8.0pt%3B%0A%09mso-bidi-font-size%3A10.0pt%3B%0A%09font-family%3A%22Tahoma%22%2Csans-serif%3B%0A%09mso-fareast-font-family%3A%22Times%20New%20Roman%22%3B%0A%09mso-bidi-font-family%3A%22Times%20New%20Roman%22%3B%0A%09mso-fareast-language%3AFR%3B%0A%09mso-bidi-language%3AFR%3B%0A%09font-weight%3Abold%3B%0A%09mso-bidi-font-weight%3Anormal%3B%7D%0Ap.MsoHeading7%2C%20li.MsoHeading7%2C%20div.MsoHeading7%0A%09%7Bmso-style-priority%3A9%3B%0A%09mso-style-qformat%3Ayes%3B%0A%09mso-style-link%3A%22Titre%207%20Car%22%3B%0A%09mso-style-next%3ANormal%3B%0A%09margin-top%3A0cm%3B%0A%09margin-right%3A0cm%3B%0A%09margin-bottom%3A6.0pt%3B%0A%09margin-left%3A42.55pt%3B%0A%09text-align%3Ajustify%3B%0A%09text-indent%3A-42.55pt%3B%0A%09mso-pagination%3Awidow-orphan%3B%0A%09page-break-after%3Aavoid%3B%0A%09mso-outline-level%3A7%3B%0A%09mso-list%3Al7%20level4%20lfo7%3B%0A%09tab-stops%3Alist%2042.55pt%3B%0A%09font-size%3A8.0pt%3B%0A%09mso-bidi-font-size%3A12.0pt%3B%0A%09font-family%3A%22Tahoma%22%2Csans-serif%3B%0A%09mso-fareast-font-family%3A%22MS%20Gothic%22%3B%0A%09mso-fareast-theme-font%3Amajor-fareast%3B%0A%09mso-bidi-font-family%3A%22Times%20New%20Roman%22%3B%0A%09mso-bidi-theme-font%3Amajor-bidi%3B%0A%09mso-fareast-language%3AFR%3B%0A%09font-style%3Aitalic%3B%7D%0Ap.MsoToc1%2C%20li.MsoToc1%2C%20div.MsoToc1%0A%09%7Bmso-style-update%3Aauto%3B%0A%09mso-style-priority%3A39%3B%0A%09mso-style-next%3ANormal%3B%0A%09margin-top%3A0cm%3B%0A%09margin-right%3A0cm%3B%0A%09margin-bottom%3A5.0pt%3B%0A%09margin-left%3A0cm%3B%0A%09text-align%3Aleft%3B%0A%09line-height%3A107%25%3B%0A%09mso-pagination%3Awidow-orphan%3B%0A%09font-size%3A8.0pt%3B%0A%09mso-bidi-font-size%3A11.0pt%3B%0A%09font-family%3A%22Tahoma%22%2Csans-serif%3B%0A%09mso-fareast-font-family%3A%22MS%20Mincho%22%3B%0A%09mso-fareast-theme-font%3Aminor-fareast%3B%0A%09mso-bidi-font-family%3A%22Times%20New%20Roman%22%3B%0A%09font-variant%3Asmall-caps%3B%0A%09color%3A%230099CC%3B%0A%09mso-ansi-language%3AEN-US%3B%0A%09mso-fareast-language%3AEN-US%3B%0A%09font-weight%3Abold%3B%0A%09mso-bidi-font-weight%3Anormal%3B%7D%0Ap.MsoToc2%2C%20li.MsoToc2%2C%20div.MsoToc2%0A%09%7Bmso-style-update%3Aauto%3B%0A%09mso-style-priority%3A39%3B%0A%09mso-style-next%3ANormal%3B%0A%09margin-top%3A0cm%3B%0A%09margin-right%3A0cm%3B%0A%09margin-bottom%3A5.0pt%3B%0A%09margin-left%3A11.0pt%3B%0A%09text-align%3Aleft%3B%0A%09line-height%3A107%25%3B%0A%09mso-pagination%3Awidow-orphan%3B%0A%09font-size%3A8.0pt%3B%0A%09mso-bidi-font-size%3A11.0pt%3B%0A%09font-family%3A%22Tahoma%22%2Csans-serif%3B%0A%09mso-fareast-font-family%3A%22MS%20Mincho%22%3B%0A%09mso-fareast-theme-font%3Aminor-fareast%3B%0A%09mso-bidi-font-family%3A%22Times%20New%20Roman%22%3B%0A%09mso-ansi-language%3AEN-US%3B%0A%09mso-fareast-language%3AEN-US%3B%0A%09font-weight%3Abold%3B%0A%09mso-bidi-font-weight%3Anormal%3B%7D%0Ap.MsoToc3%2C%20li.MsoToc3%2C%20div.MsoToc3%0A%09%7Bmso-style-update%3Aauto%3B%0A%09mso-style-priority%3A39%3B%0A%09mso-style-next%3ANormal%3B%0A%09margin-top%3A0cm%3B%0A%09margin-right%3A0cm%3B%0A%09margin-bottom%3A5.0pt%3B%0A%09margin-left%3A22.0pt%3B%0A%09text-align%3Aleft%3B%0A%09line-height%3A107%25%3B%0A%09mso-pagination%3Awidow-orphan%3B%0A%09font-size%3A8.0pt%3B%0A%09mso-bidi-font-size%3A11.0pt%3B%0A%09font-family%3A%22Tahoma%22%2Csans-serif%3B%0A%09mso-fareast-font-family%3A%22MS%20Mincho%22%3B%0A%09mso-fareast-theme-font%3Aminor-fareast%3B%0A%09mso-bidi-font-family%3A%22Times%20New%20Roman%22%3B%0A%09mso-ansi-language%3AEN-US%3B%0A%09mso-fareast-language%3AEN-US%3B%0A%09font-weight%3Abold%3B%0A%09mso-bidi-font-weight%3Anormal%3B%0A%09font-style%3Aitalic%3B%0A%09mso-bidi-font-style%3Anormal%3B%7D%0Ap.MsoToc4%2C%20li.MsoToc4%2C%20div.MsoToc4%0A%09%7Bmso-style-update%3Aauto%3B%0A%09mso-style-priority%3A39%3B%0A%09mso-style-next%3ANormal%3B%0A%09margin-top%3A0cm%3B%0A%09margin-right%3A0cm%3B%0A%09margin-bottom%3A5.0pt%3B%0A%09margin-left%3A0cm%3B%0A%09text-align%3Ajustify%3B%0A%09mso-pagination%3Awidow-orphan%3B%0A%09tab-stops%3Aright%20dotted%20549.1pt%3B%0A%09font-size%3A8.0pt%3B%0A%09mso-bidi-font-size%3A11.0pt%3B%0A%09font-family%3A%22Tahoma%22%2Csans-serif%3B%0A%09mso-fareast-font-family%3ACalibri%3B%0A%09mso-bidi-font-family%3A%22Times%20New%20Roman%22%3B%0A%09mso-fareast-language%3AEN-US%3B%0A%09mso-bidi-language%3AFR%3B%0A%09font-weight%3Abold%3B%0A%09mso-bidi-font-weight%3Anormal%3B%0A%09mso-no-proof%3Ayes%3B%7D%0Ap.MsoFootnoteText%2C%20li.MsoFootnoteText%2C%20div.MsoFootnoteText%0A%09%7Bmso-style-noshow%3Ayes%3B%0A%09mso-style-priority%3A99%3B%0A%09mso-style-link%3A%22Note%20de%20bas%20de%20page%20Car%22%3B%0A%09margin%3A0cm%3B%0A%09margin-bottom%3A.0001pt%3B%0A%09text-align%3Ajustify%3B%0A%09mso-pagination%3Awidow-orphan%3B%0A%09font-size%3A8.0pt%3B%0A%09mso-bidi-font-size%3A10.0pt%3B%0A%09font-family%3A%22Tahoma%22%2Csans-serif%3B%0A%09mso-fareast-font-family%3A%22Times%20New%20Roman%22%3B%0A%09mso-bidi-font-family%3A%22Times%20New%20Roman%22%3B%0A%09mso-fareast-language%3AFR%3B%7D%0Ap.MsoCommentText%2C%20li.MsoCommentText%2C%20div.MsoCommentText%0A%09%7Bmso-style-priority%3A99%3B%0A%09mso-style-link%3A%22Commentaire%20Car%22%3B%0A%09margin-top%3A0cm%3B%0A%09margin-right%3A0cm%3B%0A%09margin-bottom%3A6.0pt%3B%0A%09margin-left%3A0cm%3B%0A%09text-align%3Ajustify%3B%0A%09mso-pagination%3Awidow-orphan%3B%0A%09font-size%3A8.0pt%3B%0A%09mso-bidi-font-size%3A10.0pt%3B%0A%09font-family%3A%22Tahoma%22%2Csans-serif%3B%0A%09mso-fareast-font-family%3A%22Times%20New%20Roman%22%3B%0A%09mso-bidi-font-family%3A%22Times%20New%20Roman%22%3B%0A%09mso-fareast-language%3AFR%3B%7D%0Ap.MsoHeader%2C%20li.MsoHeader%2C%20div.MsoHeader%0A%09%7Bmso-style-priority%3A99%3B%0A%09mso-style-link%3A%22En-t%C3%AAte%20Car%22%3B%0A%09margin%3A0cm%3B%0A%09margin-bottom%3A.0001pt%3B%0A%09text-align%3Ajustify%3B%0A%09mso-pagination%3Awidow-orphan%3B%0A%09tab-stops%3Acenter%208.0cm%20right%2016.0cm%3B%0A%09font-size%3A8.0pt%3B%0A%09mso-bidi-font-size%3A12.0pt%3B%0A%09font-family%3A%22Tahoma%22%2Csans-serif%3B%0A%09mso-fareast-font-family%3A%22Times%20New%20Roman%22%3B%0A%09mso-bidi-font-family%3A%22Times%20New%20Roman%22%3B%0A%09mso-fareast-language%3AFR%3B%7D%0Ap.MsoFooter%2C%20li.MsoFooter%2C%20div.MsoFooter%0A%09%7Bmso-style-priority%3A99%3B%0A%09mso-style-link%3A%22Pied%20de%20page%20Car%22%3B%0A%09margin%3A0cm%3B%0A%09margin-bottom%3A.0001pt%3B%0A%09text-align%3Ajustify%3B%0A%09mso-pagination%3Awidow-orphan%3B%0A%09tab-stops%3Acenter%208.0cm%20right%2016.0cm%3B%0A%09font-size%3A8.0pt%3B%0A%09mso-bidi-font-size%3A12.0pt%3B%0A%09font-family%3A%22Tahoma%22%2Csans-serif%3B%0A%09mso-fareast-font-family%3A%22Times%20New%20Roman%22%3B%0A%09mso-bidi-font-family%3A%22Times%20New%20Roman%22%3B%0A%09mso-fareast-language%3AFR%3B%7D%0Aspan.MsoFootnoteReference%0A%09%7Bmso-style-noshow%3Ayes%3B%0A%09mso-style-priority%3A99%3B%0A%09vertical-align%3Asuper%3B%7D%0Aspan.MsoCommentReference%0A%09%7Bmso-style-priority%3A99%3B%0A%09mso-ansi-font-size%3A8.0pt%3B%0A%09mso-bidi-font-size%3A8.0pt%3B%7D%0Ap.MsoTitle%2C%20li.MsoTitle%2C%20div.MsoTitle%0A%09%7Bmso-style-priority%3A10%3B%0A%09mso-style-unhide%3Ano%3B%0A%09mso-style-qformat%3Ayes%3B%0A%09mso-style-link%3A%22Titre%20Car%22%3B%0A%09mso-style-next%3ANormal%3B%0A%09margin%3A0cm%3B%0A%09margin-bottom%3A.0001pt%3B%0A%09mso-add-space%3Aauto%3B%0A%09text-align%3Ajustify%3B%0A%09mso-pagination%3Awidow-orphan%3B%0A%09font-size%3A28.0pt%3B%0A%09font-family%3A%22Cambria%22%2Cserif%3B%0A%09mso-ascii-font-family%3ACambria%3B%0A%09mso-ascii-theme-font%3Amajor-latin%3B%0A%09mso-fareast-font-family%3A%22MS%20Gothic%22%3B%0A%09mso-fareast-theme-font%3Amajor-fareast%3B%0A%09mso-hansi-font-family%3ACambria%3B%0A%09mso-hansi-theme-font%3Amajor-latin%3B%0A%09mso-bidi-font-family%3A%22Times%20New%20Roman%22%3B%0A%09mso-bidi-theme-font%3Amajor-bidi%3B%0A%09letter-spacing%3A-.5pt%3B%0A%09mso-font-kerning%3A14.0pt%3B%0A%09mso-fareast-language%3AFR%3B%7D%0Ap.MsoTitleCxSpFirst%2C%20li.MsoTitleCxSpFirst%2C%20div.MsoTitleCxSpFirst%0A%09%7Bmso-style-priority%3A10%3B%0A%09mso-style-unhide%3Ano%3B%0A%09mso-style-qformat%3Ayes%3B%0A%09mso-style-link%3A%22Titre%20Car%22%3B%0A%09mso-style-next%3ANormal%3B%0A%09mso-style-type%3Aexport-only%3B%0A%09margin%3A0cm%3B%0A%09margin-bottom%3A.0001pt%3B%0A%09mso-add-space%3Aauto%3B%0A%09text-align%3Ajustify%3B%0A%09mso-pagination%3Awidow-orphan%3B%0A%09font-size%3A28.0pt%3B%0A%09font-family%3A%22Cambria%22%2Cserif%3B%0A%09mso-ascii-font-family%3ACambria%3B%0A%09mso-ascii-theme-font%3Amajor-latin%3B%0A%09mso-fareast-font-family%3A%22MS%20Gothic%22%3B%0A%09mso-fareast-theme-font%3Amajor-fareast%3B%0A%09mso-hansi-font-family%3ACambria%3B%0A%09mso-hansi-theme-font%3Amajor-latin%3B%0A%09mso-bidi-font-family%3A%22Times%20New%20Roman%22%3B%0A%09mso-bidi-theme-font%3Amajor-bidi%3B%0A%09letter-spacing%3A-.5pt%3B%0A%09mso-font-kerning%3A14.0pt%3B%0A%09mso-fareast-language%3AFR%3B%7D%0Ap.MsoTitleCxSpMiddle%2C%20li.MsoTitleCxSpMiddle%2C%20div.MsoTitleCxSpMiddle%0A%09%7Bmso-style-priority%3A10%3B%0A%09mso-style-unhide%3Ano%3B%0A%09mso-style-qformat%3Ayes%3B%0A%09mso-style-link%3A%22Titre%20Car%22%3B%0A%09mso-style-next%3ANormal%3B%0A%09mso-style-type%3Aexport-only%3B%0A%09margin%3A0cm%3B%0A%09margin-bottom%3A.0001pt%3B%0A%09mso-add-space%3Aauto%3B%0A%09text-align%3Ajustify%3B%0A%09mso-pagination%3Awidow-orphan%3B%0A%09font-size%3A28.0pt%3B%0A%09font-family%3A%22Cambria%22%2Cserif%3B%0A%09mso-ascii-font-family%3ACambria%3B%0A%09mso-ascii-theme-font%3Amajor-latin%3B%0A%09mso-fareast-font-family%3A%22MS%20Gothic%22%3B%0A%09mso-fareast-theme-font%3Amajor-fareast%3B%0A%09mso-hansi-font-family%3ACambria%3B%0A%09mso-hansi-theme-font%3Amajor-latin%3B%0A%09mso-bidi-font-family%3A%22Times%20New%20Roman%22%3B%0A%09mso-bidi-theme-font%3Amajor-bidi%3B%0A%09letter-spacing%3A-.5pt%3B%0A%09mso-font-kerning%3A14.0pt%3B%0A%09mso-fareast-language%3AFR%3B%7D%0Ap.MsoTitleCxSpLast%2C%20li.MsoTitleCxSpLast%2C%20div.MsoTitleCxSpLast%0A%09%7Bmso-style-priority%3A10%3B%0A%09mso-style-unhide%3Ano%3B%0A%09mso-style-qformat%3Ayes%3B%0A%09mso-style-link%3A%22Titre%20Car%22%3B%0A%09mso-style-next%3ANormal%3B%0A%09mso-style-type%3Aexport-only%3B%0A%09margin%3A0cm%3B%0A%09margin-bottom%3A.0001pt%3B%0A%09mso-add-space%3Aauto%3B%0A%09text-align%3Ajustify%3B%0A%09mso-pagination%3Awidow-orphan%3B%0A%09font-size%3A28.0pt%3B%0A%09font-family%3A%22Cambria%22%2Cserif%3B%0A%09mso-ascii-font-family%3ACambria%3B%0A%09mso-ascii-theme-font%3Amajor-latin%3B%0A%09mso-fareast-font-family%3A%22MS%20Gothic%22%3B%0A%09mso-fareast-theme-font%3Amajor-fareast%3B%0A%09mso-hansi-font-family%3ACambria%3B%0A%09mso-hansi-theme-font%3Amajor-latin%3B%0A%09mso-bidi-font-family%3A%22Times%20New%20Roman%22%3B%0A%09mso-bidi-theme-font%3Amajor-bidi%3B%0A%09letter-spacing%3A-.5pt%3B%0A%09mso-font-kerning%3A14.0pt%3B%0A%09mso-fareast-language%3AFR%3B%7D%0Ap.MsoSubtitle%2C%20li.MsoSubtitle%2C%20div.MsoSubtitle%0A%09%7Bmso-style-priority%3A11%3B%0A%09mso-style-unhide%3Ano%3B%0A%09mso-style-qformat%3Ayes%3B%0A%09mso-style-link%3A%22Sous-titre%20Car%22%3B%0A%09mso-style-next%3ANormal%3B%0A%09margin-top%3A0cm%3B%0A%09margin-right%3A0cm%3B%0A%09margin-bottom%3A6.0pt%3B%0A%09margin-left%3A0cm%3B%0A%09text-align%3Ajustify%3B%0A%09mso-pagination%3Awidow-orphan%3B%0A%09font-size%3A11.0pt%3B%0A%09mso-bidi-font-size%3A12.0pt%3B%0A%09font-family%3A%22Calibri%22%2Csans-serif%3B%0A%09mso-ascii-font-family%3ACalibri%3B%0A%09mso-ascii-theme-font%3Aminor-latin%3B%0A%09mso-fareast-font-family%3A%22MS%20Mincho%22%3B%0A%09mso-fareast-theme-font%3Aminor-fareast%3B%0A%09mso-hansi-font-family%3ACalibri%3B%0A%09mso-hansi-theme-font%3Aminor-latin%3B%0A%09mso-bidi-font-family%3A%22Times%20New%20Roman%22%3B%0A%09color%3A%235A5A5A%3B%0A%09mso-themecolor%3Atext1%3B%0A%09mso-themetint%3A165%3B%0A%09letter-spacing%3A.75pt%3B%0A%09mso-fareast-language%3AFR%3B%7D%0Aa%3Alink%2C%20span.MsoHyperlink%0A%09%7Bmso-style-priority%3A99%3B%0A%09color%3Ablue%3B%0A%09mso-themecolor%3Ahyperlink%3B%0A%09text-decoration%3Aunderline%3B%0A%09text-underline%3Asingle%3B%7D%0Aa%3Avisited%2C%20span.MsoHyperlinkFollowed%0A%09%7Bmso-style-noshow%3Ayes%3B%0A%09mso-style-priority%3A99%3B%0A%09color%3Apurple%3B%0A%09mso-themecolor%3Afollowedhyperlink%3B%0A%09text-decoration%3Aunderline%3B%0A%09text-underline%3Asingle%3B%7D%0Ap.MsoDocumentMap%2C%20li.MsoDocumentMap%2C%20div.MsoDocumentMap%0A%09%7Bmso-style-noshow%3Ayes%3B%0A%09mso-style-priority%3A99%3B%0A%09mso-style-link%3A%22Explorateur%20de%20documents%20Car%22%3B%0A%09margin%3A0cm%3B%0A%09margin-bottom%3A.0001pt%3B%0A%09text-align%3Ajustify%3B%0A%09mso-pagination%3Awidow-orphan%3B%0A%09font-size%3A12.0pt%3B%0A%09font-family%3A%22Lucida%20Grande%22%2Csans-serif%3B%0A%09mso-fareast-font-family%3A%22Times%20New%20Roman%22%3B%0A%09mso-fareast-language%3AFR%3B%7D%0Ap%0A%09%7Bmso-style-noshow%3Ayes%3B%0A%09mso-style-priority%3A99%3B%0A%09margin-top%3A0cm%3B%0A%09margin-right%3A0cm%3B%0A%09margin-bottom%3A6.0pt%3B%0A%09margin-left%3A0cm%3B%0A%09text-align%3Ajustify%3B%0A%09mso-pagination%3Awidow-orphan%3B%0A%09font-size%3A12.0pt%3B%0A%09font-family%3A%22Times%20New%20Roman%22%2Cserif%3B%0A%09mso-fareast-font-family%3A%22Times%20New%20Roman%22%3B%0A%09mso-fareast-language%3AFR%3B%7D%0Ap.MsoCommentSubject%2C%20li.MsoCommentSubject%2C%20div.MsoCommentSubject%0A%09%7Bmso-style-priority%3A99%3B%0A%09mso-style-parent%3ACommentaire%3B%0A%09mso-style-link%3A%22Objet%20du%20commentaire%20Car%22%3B%0A%09mso-style-next%3ACommentaire%3B%0A%09margin-top%3A0cm%3B%0A%09margin-right%3A0cm%3B%0A%09margin-bottom%3A6.0pt%3B%0A%09margin-left%3A0cm%3B%0A%09text-align%3Ajustify%3B%0A%09mso-pagination%3Awidow-orphan%3B%0A%09font-size%3A8.0pt%3B%0A%09mso-bidi-font-size%3A10.0pt%3B%0A%09font-family%3A%22Tahoma%22%2Csans-serif%3B%0A%09mso-fareast-font-family%3A%22Times%20New%20Roman%22%3B%0A%09mso-bidi-font-family%3A%22Times%20New%20Roman%22%3B%0A%09mso-fareast-language%3AFR%3B%0A%09font-weight%3Abold%3B%7D%0Ap.MsoAcetate%2C%20li.MsoAcetate%2C%20div.MsoAcetate%0A%09%7Bmso-style-priority%3A99%3B%0A%09mso-style-link%3A%22Texte%20de%20bulles%20Car%22%3B%0A%09margin%3A0cm%3B%0A%09margin-bottom%3A.0001pt%3B%0A%09text-align%3Ajustify%3B%0A%09mso-pagination%3Awidow-orphan%3B%0A%09font-size%3A8.0pt%3B%0A%09mso-bidi-font-size%3A9.0pt%3B%0A%09font-family%3A%22Lucida%20Grande%22%2Csans-serif%3B%0A%09mso-fareast-font-family%3A%22Times%20New%20Roman%22%3B%0A%09mso-fareast-language%3AFR%3B%7D%0Ap.MsoNoSpacing%2C%20li.MsoNoSpacing%2C%20div.MsoNoSpacing%0A%09%7Bmso-style-priority%3A1%3B%0A%09mso-style-unhide%3Ano%3B%0A%09mso-style-qformat%3Ayes%3B%0A%09mso-style-parent%3A%22%22%3B%0A%09margin%3A0cm%3B%0A%09margin-bottom%3A.0001pt%3B%0A%09text-align%3Ajustify%3B%0A%09mso-pagination%3Awidow-orphan%3B%0A%09font-size%3A10.0pt%3B%0A%09mso-bidi-font-size%3A11.0pt%3B%0A%09font-family%3A%22Tahoma%22%2Csans-serif%3B%0A%09mso-fareast-font-family%3ACalibri%3B%0A%09mso-fareast-theme-font%3Aminor-latin%3B%0A%09mso-bidi-font-family%3A%22Times%20New%20Roman%22%3B%0A%09mso-bidi-theme-font%3Aminor-bidi%3B%0A%09mso-fareast-language%3AEN-US%3B%7D%0Ap.MsoRMPane%2C%20li.MsoRMPane%2C%20div.MsoRMPane%0A%09%7Bmso-style-noshow%3Ayes%3B%0A%09mso-style-priority%3A99%3B%0A%09mso-style-unhide%3Ano%3B%0A%09mso-style-parent%3A%22%22%3B%0A%09margin%3A0cm%3B%0A%09margin-bottom%3A.0001pt%3B%0A%09text-align%3Aleft%3B%0A%09mso-pagination%3Awidow-orphan%3B%0A%09font-size%3A10.0pt%3B%0A%09mso-bidi-font-size%3A12.0pt%3B%0A%09font-family%3A%22Tahoma%22%2Csans-serif%3B%0A%09mso-fareast-font-family%3A%22Times%20New%20Roman%22%3B%0A%09mso-bidi-font-family%3A%22Times%20New%20Roman%22%3B%0A%09mso-fareast-language%3AFR%3B%7D%0Ap.MsoListParagraph%2C%20li.MsoListParagraph%2C%20div.MsoListParagraph%0A%09%7Bmso-style-name%3A%22Paragraphe%20de%20liste%5C%2CD%C3%A9finitions%22%3B%0A%09mso-style-priority%3A34%3B%0A%09mso-style-unhide%3Ano%3B%0A%09mso-style-qformat%3Ayes%3B%0A%09mso-style-link%3A%22Paragraphe%20de%20liste%20Car%5C%2CD%C3%A9finitions%20Car%22%3B%0A%09margin-top%3A0cm%3B%0A%09margin-right%3A0cm%3B%0A%09margin-bottom%3A6.0pt%3B%0A%09margin-left%3A36.0pt%3B%0A%09mso-add-space%3Aauto%3B%0A%09text-align%3Ajustify%3B%0A%09mso-pagination%3Awidow-orphan%3B%0A%09font-size%3A8.0pt%3B%0A%09mso-bidi-font-size%3A12.0pt%3B%0A%09font-family%3A%22Tahoma%22%2Csans-serif%3B%0A%09mso-fareast-font-family%3A%22Times%20New%20Roman%22%3B%0A%09mso-bidi-font-family%3A%22Times%20New%20Roman%22%3B%0A%09mso-fareast-language%3AFR%3B%7D%0Ap.MsoListParagraphCxSpFirst%2C%20li.MsoListParagraphCxSpFirst%2C%20div.MsoListParagraphCxSpFirst%0A%09%7Bmso-style-name%3A%22Paragraphe%20de%20liste%5C%2CD%C3%A9finitionsCxSpFirst%22%3B%0A%09mso-style-priority%3A34%3B%0A%09mso-style-unhide%3Ano%3B%0A%09mso-style-qformat%3Ayes%3B%0A%09mso-style-link%3A%22Paragraphe%20de%20liste%20Car%5C%2CD%C3%A9finitions%20Car%22%3B%0A%09mso-style-type%3Aexport-only%3B%0A%09margin-top%3A0cm%3B%0A%09margin-right%3A0cm%3B%0A%09margin-bottom%3A0cm%3B%0A%09margin-left%3A36.0pt%3B%0A%09margin-bottom%3A.0001pt%3B%0A%09mso-add-space%3Aauto%3B%0A%09text-align%3Ajustify%3B%0A%09mso-pagination%3Awidow-orphan%3B%0A%09font-size%3A8.0pt%3B%0A%09mso-bidi-font-size%3A12.0pt%3B%0A%09font-family%3A%22Tahoma%22%2Csans-serif%3B%0A%09mso-fareast-font-family%3A%22Times%20New%20Roman%22%3B%0A%09mso-bidi-font-family%3A%22Times%20New%20Roman%22%3B%0A%09mso-fareast-language%3AFR%3B%7D%0Ap.MsoListParagraphCxSpMiddle%2C%20li.MsoListParagraphCxSpMiddle%2C%20div.MsoListParagraphCxSpMiddle%0A%09%7Bmso-style-name%3A%22Paragraphe%20de%20liste%5C%2CD%C3%A9finitionsCxSpMiddle%22%3B%0A%09mso-style-priority%3A34%3B%0A%09mso-style-unhide%3Ano%3B%0A%09mso-style-qformat%3Ayes%3B%0A%09mso-style-link%3A%22Paragraphe%20de%20liste%20Car%5C%2CD%C3%A9finitions%20Car%22%3B%0A%09mso-style-type%3Aexport-only%3B%0A%09margin-top%3A0cm%3B%0A%09margin-right%3A0cm%3B%0A%09margin-bottom%3A0cm%3B%0A%09margin-left%3A36.0pt%3B%0A%09margin-bottom%3A.0001pt%3B%0A%09mso-add-space%3Aauto%3B%0A%09text-align%3Ajustify%3B%0A%09mso-pagination%3Awidow-orphan%3B%0A%09font-size%3A8.0pt%3B%0A%09mso-bidi-font-size%3A12.0pt%3B%0A%09font-family%3A%22Tahoma%22%2Csans-serif%3B%0A%09mso-fareast-font-family%3A%22Times%20New%20Roman%22%3B%0A%09mso-bidi-font-family%3A%22Times%20New%20Roman%22%3B%0A%09mso-fareast-language%3AFR%3B%7D%0Ap.MsoListParagraphCxSpLast%2C%20li.MsoListParagraphCxSpLast%2C%20div.MsoListParagraphCxSpLast%0A%09%7Bmso-style-name%3A%22Paragraphe%20de%20liste%5C%2CD%C3%A9finitionsCxSpLast%22%3B%0A%09mso-style-priority%3A34%3B%0A%09mso-style-unhide%3Ano%3B%0A%09mso-style-qformat%3Ayes%3B%0A%09mso-style-link%3A%22Paragraphe%20de%20liste%20Car%5C%2CD%C3%A9finitions%20Car%22%3B%0A%09mso-style-type%3Aexport-only%3B%0A%09margin-top%3A0cm%3B%0A%09margin-right%3A0cm%3B%0A%09margin-bottom%3A6.0pt%3B%0A%09margin-left%3A36.0pt%3B%0A%09mso-add-space%3Aauto%3B%0A%09text-align%3Ajustify%3B%0A%09mso-pagination%3Awidow-orphan%3B%0A%09font-size%3A8.0pt%3B%0A%09mso-bidi-font-size%3A12.0pt%3B%0A%09font-family%3A%22Tahoma%22%2Csans-serif%3B%0A%09mso-fareast-font-family%3A%22Times%20New%20Roman%22%3B%0A%09mso-bidi-font-family%3A%22Times%20New%20Roman%22%3B%0A%09mso-fareast-language%3AFR%3B%7D%0Aspan.Titre1Car%0A%09%7Bmso-style-name%3A%22Titre%201%20Car%22%3B%0A%09mso-style-unhide%3Ano%3B%0A%09mso-style-locked%3Ayes%3B%0A%09mso-style-link%3A%22Titre%201%22%3B%0A%09mso-ansi-font-size%3A8.0pt%3B%0A%09mso-bidi-font-size%3A16.0pt%3B%0A%09font-family%3A%22Tahoma%22%2Csans-serif%3B%0A%09mso-ascii-font-family%3ATahoma%3B%0A%09mso-fareast-font-family%3A%22Times%20New%20Roman%22%3B%0A%09mso-hansi-font-family%3ATahoma%3B%0A%09mso-bidi-font-family%3A%22Times%20New%20Roman%22%3B%0A%09font-variant%3Asmall-caps%3B%0A%09mso-font-kerning%3A16.0pt%3B%0A%09mso-fareast-language%3AFR%3B%0A%09font-weight%3Abold%3B%0A%09mso-bidi-font-weight%3Anormal%3B%7D%0Aspan.Titre2Car%0A%09%7Bmso-style-name%3A%22Titre%202%20Car%22%3B%0A%09mso-style-unhide%3Ano%3B%0A%09mso-style-locked%3Ayes%3B%0A%09mso-style-link%3A%22Titre%202%22%3B%0A%09mso-ansi-font-size%3A8.0pt%3B%0A%09mso-bidi-font-size%3A14.0pt%3B%0A%09font-family%3A%22Tahoma%22%2Csans-serif%3B%0A%09mso-ascii-font-family%3ATahoma%3B%0A%09mso-fareast-font-family%3A%22Times%20New%20Roman%22%3B%0A%09mso-hansi-font-family%3ATahoma%3B%0A%09mso-bidi-font-family%3A%22Times%20New%20Roman%22%3B%0A%09mso-fareast-language%3AFR%3B%0A%09font-weight%3Abold%3B%0A%09mso-bidi-font-weight%3Anormal%3B%7D%0Aspan.Titre3Car%0A%09%7Bmso-style-name%3A%22Titre%203%20Car%22%3B%0A%09mso-style-unhide%3Ano%3B%0A%09mso-style-locked%3Ayes%3B%0A%09mso-style-link%3A%22Titre%203%22%3B%0A%09mso-ansi-font-size%3A8.0pt%3B%0A%09mso-bidi-font-size%3A14.0pt%3B%0A%09font-family%3A%22Tahoma%22%2Csans-serif%3B%0A%09mso-ascii-font-family%3ATahoma%3B%0A%09mso-fareast-font-family%3A%22Times%20New%20Roman%22%3B%0A%09mso-hansi-font-family%3ATahoma%3B%0A%09mso-bidi-font-family%3A%22Times%20New%20Roman%22%3B%0A%09mso-fareast-language%3AFR%3B%0A%09font-style%3Aitalic%3B%0A%09mso-bidi-font-style%3Anormal%3B%7D%0Aspan.Titre4Car%0A%09%7Bmso-style-name%3A%22Titre%204%20Car%22%3B%0A%09mso-style-unhide%3Ano%3B%0A%09mso-style-locked%3Ayes%3B%0A%09mso-style-link%3A%22Titre%204%22%3B%0A%09mso-ansi-font-size%3A8.0pt%3B%0A%09mso-bidi-font-size%3A10.0pt%3B%0A%09font-family%3A%22Tahoma%22%2Csans-serif%3B%0A%09mso-ascii-font-family%3ATahoma%3B%0A%09mso-fareast-font-family%3A%22Times%20New%20Roman%22%3B%0A%09mso-hansi-font-family%3ATahoma%3B%0A%09mso-bidi-font-family%3A%22Times%20New%20Roman%22%3B%0A%09font-variant%3Asmall-caps%3B%0A%09mso-fareast-language%3AFR%3B%0A%09mso-bidi-language%3AFR%3B%0A%09font-weight%3Abold%3B%0A%09mso-bidi-font-weight%3Anormal%3B%7D%0Aspan.Titre5Car%0A%09%7Bmso-style-name%3A%22Titre%205%20Car%22%3B%0A%09mso-style-unhide%3Ano%3B%0A%09mso-style-locked%3Ayes%3B%0A%09mso-style-link%3A%22Titre%205%22%3B%0A%09mso-ansi-font-size%3A8.0pt%3B%0A%09mso-bidi-font-size%3A10.0pt%3B%0A%09font-family%3A%22Tahoma%22%2Csans-serif%3B%0A%09mso-ascii-font-family%3ATahoma%3B%0A%09mso-fareast-font-family%3A%22Times%20New%20Roman%22%3B%0A%09mso-hansi-font-family%3ATahoma%3B%0A%09mso-bidi-font-family%3A%22Times%20New%20Roman%22%3B%0A%09font-variant%3Asmall-caps%3B%0A%09mso-fareast-language%3AFR%3B%0A%09mso-bidi-language%3AFR%3B%0A%09font-weight%3Abold%3B%0A%09mso-bidi-font-weight%3Anormal%3B%7D%0Aspan.Titre6Car%0A%09%7Bmso-style-name%3A%22Titre%206%20Car%22%3B%0A%09mso-style-priority%3A9%3B%0A%09mso-style-unhide%3Ano%3B%0A%09mso-style-locked%3Ayes%3B%0A%09mso-style-link%3A%22Titre%206%22%3B%0A%09mso-ansi-font-size%3A8.0pt%3B%0A%09mso-bidi-font-size%3A10.0pt%3B%0A%09font-family%3A%22Tahoma%22%2Csans-serif%3B%0A%09mso-ascii-font-family%3ATahoma%3B%0A%09mso-fareast-font-family%3A%22Times%20New%20Roman%22%3B%0A%09mso-hansi-font-family%3ATahoma%3B%0A%09mso-bidi-font-family%3A%22Times%20New%20Roman%22%3B%0A%09mso-fareast-language%3AFR%3B%0A%09mso-bidi-language%3AFR%3B%0A%09font-weight%3Abold%3B%0A%09mso-bidi-font-weight%3Anormal%3B%7D%0Aspan.Titre7Car%0A%09%7Bmso-style-name%3A%22Titre%207%20Car%22%3B%0A%09mso-style-priority%3A9%3B%0A%09mso-style-unhide%3Ano%3B%0A%09mso-style-locked%3Ayes%3B%0A%09mso-style-link%3A%22Titre%207%22%3B%0A%09mso-ansi-font-size%3A8.0pt%3B%0A%09mso-bidi-font-size%3A12.0pt%3B%0A%09font-family%3A%22Tahoma%22%2Csans-serif%3B%0A%09mso-ascii-font-family%3ATahoma%3B%0A%09mso-fareast-font-family%3A%22MS%20Gothic%22%3B%0A%09mso-fareast-theme-font%3Amajor-fareast%3B%0A%09mso-hansi-font-family%3ATahoma%3B%0A%09mso-bidi-font-family%3A%22Times%20New%20Roman%22%3B%0A%09mso-bidi-theme-font%3Amajor-bidi%3B%0A%09mso-fareast-language%3AFR%3B%0A%09font-style%3Aitalic%3B%7D%0Aspan.TextedebullesCar%0A%09%7Bmso-style-name%3A%22Texte%20de%20bulles%20Car%22%3B%0A%09mso-style-priority%3A99%3B%0A%09mso-style-unhide%3Ano%3B%0A%09mso-style-locked%3Ayes%3B%0A%09mso-style-link%3A%22Texte%20de%20bulles%22%3B%0A%09mso-ansi-font-size%3A8.0pt%3B%0A%09mso-bidi-font-size%3A9.0pt%3B%0A%09font-family%3A%22Lucida%20Grande%22%2Csans-serif%3B%0A%09mso-ascii-font-family%3A%22Lucida%20Grande%22%3B%0A%09mso-fareast-font-family%3A%22Times%20New%20Roman%22%3B%0A%09mso-hansi-font-family%3A%22Lucida%20Grande%22%3B%0A%09mso-bidi-font-family%3A%22Lucida%20Grande%22%3B%0A%09mso-fareast-language%3AFR%3B%7D%0Ap.Titrecontrat%2C%20li.Titrecontrat%2C%20div.Titrecontrat%0A%09%7Bmso-style-name%3A%22Titre%20contrat%22%3B%0A%09mso-style-unhide%3Ano%3B%0A%09margin-top%3A0cm%3B%0A%09margin-right%3A0cm%3B%0A%09margin-bottom%3A6.0pt%3B%0A%09margin-left%3A0cm%3B%0A%09text-align%3Acenter%3B%0A%09mso-pagination%3Awidow-orphan%3B%0A%09background%3A%23D9D9D9%3B%0A%09border%3Anone%3B%0A%09mso-border-alt%3Asolid%20%230099CC%201.0pt%3B%0A%09padding%3A0cm%3B%0A%09mso-padding-alt%3A1.0pt%204.0pt%201.0pt%204.0pt%3B%0A%09font-size%3A14.0pt%3B%0A%09mso-bidi-font-size%3A12.0pt%3B%0A%09font-family%3A%22Tahoma%22%2Csans-serif%3B%0A%09mso-fareast-font-family%3A%22Times%20New%20Roman%22%3B%0A%09mso-bidi-font-family%3A%22Times%20New%20Roman%22%3B%0A%09font-variant%3Asmall-caps%3B%0A%09mso-fareast-language%3AFR%3B%0A%09font-weight%3Abold%3B%7D%0Aspan.ExplorateurdedocumentsCar%0A%09%7Bmso-style-name%3A%22Explorateur%20de%20documents%20Car%22%3B%0A%09mso-style-noshow%3Ayes%3B%0A%09mso-style-priority%3A99%3B%0A%09mso-style-unhide%3Ano%3B%0A%09mso-style-locked%3Ayes%3B%0A%09mso-style-link%3A%22Explorateur%20de%20documents%22%3B%0A%09mso-ansi-font-size%3A12.0pt%3B%0A%09mso-bidi-font-size%3A12.0pt%3B%0A%09font-family%3A%22Lucida%20Grande%22%2Csans-serif%3B%0A%09mso-ascii-font-family%3A%22Lucida%20Grande%22%3B%0A%09mso-fareast-font-family%3A%22Times%20New%20Roman%22%3B%0A%09mso-hansi-font-family%3A%22Lucida%20Grande%22%3B%0A%09mso-bidi-font-family%3A%22Lucida%20Grande%22%3B%0A%09mso-fareast-language%3AFR%3B%7D%0Aspan.PieddepageCar%0A%09%7Bmso-style-name%3A%22Pied%20de%20page%20Car%22%3B%0A%09mso-style-priority%3A99%3B%0A%09mso-style-unhide%3Ano%3B%0A%09mso-style-locked%3Ayes%3B%0A%09mso-style-link%3A%22Pied%20de%20page%22%3B%0A%09mso-ansi-font-size%3A8.0pt%3B%0A%09mso-bidi-font-size%3A12.0pt%3B%0A%09font-family%3A%22Tahoma%22%2Csans-serif%3B%0A%09mso-ascii-font-family%3ATahoma%3B%0A%09mso-fareast-font-family%3A%22Times%20New%20Roman%22%3B%0A%09mso-hansi-font-family%3ATahoma%3B%0A%09mso-bidi-font-family%3A%22Times%20New%20Roman%22%3B%0A%09mso-fareast-language%3AFR%3B%7D%0Aspan.En-tteCar%0A%09%7Bmso-style-name%3A%22En-t%C3%AAte%20Car%22%3B%0A%09mso-style-priority%3A99%3B%0A%09mso-style-unhide%3Ano%3B%0A%09mso-style-locked%3Ayes%3B%0A%09mso-style-link%3AEn-t%C3%AAte%3B%0A%09mso-ansi-font-size%3A8.0pt%3B%0A%09mso-bidi-font-size%3A12.0pt%3B%0A%09font-family%3A%22Tahoma%22%2Csans-serif%3B%0A%09mso-ascii-font-family%3ATahoma%3B%0A%09mso-fareast-font-family%3A%22Times%20New%20Roman%22%3B%0A%09mso-hansi-font-family%3ATahoma%3B%0A%09mso-bidi-font-family%3A%22Times%20New%20Roman%22%3B%0A%09mso-fareast-language%3AFR%3B%7D%0Aspan.CommentaireCar%0A%09%7Bmso-style-name%3A%22Commentaire%20Car%22%3B%0A%09mso-style-priority%3A99%3B%0A%09mso-style-unhide%3Ano%3B%0A%09mso-style-locked%3Ayes%3B%0A%09mso-style-link%3ACommentaire%3B%0A%09mso-ansi-font-size%3A8.0pt%3B%0A%09mso-bidi-font-size%3A10.0pt%3B%0A%09font-family%3A%22Tahoma%22%2Csans-serif%3B%0A%09mso-ascii-font-family%3ATahoma%3B%0A%09mso-fareast-font-family%3A%22Times%20New%20Roman%22%3B%0A%09mso-hansi-font-family%3ATahoma%3B%0A%09mso-bidi-font-family%3A%22Times%20New%20Roman%22%3B%0A%09mso-fareast-language%3AFR%3B%7D%0Aspan.ObjetducommentaireCar%0A%09%7Bmso-style-name%3A%22Objet%20du%20commentaire%20Car%22%3B%0A%09mso-style-priority%3A99%3B%0A%09mso-style-unhide%3Ano%3B%0A%09mso-style-locked%3Ayes%3B%0A%09mso-style-parent%3A%22Commentaire%20Car%22%3B%0A%09mso-style-link%3A%22Objet%20du%20commentaire%22%3B%0A%09mso-ansi-font-size%3A8.0pt%3B%0A%09mso-bidi-font-size%3A10.0pt%3B%0A%09font-family%3A%22Tahoma%22%2Csans-serif%3B%0A%09mso-ascii-font-family%3ATahoma%3B%0A%09mso-fareast-font-family%3A%22Times%20New%20Roman%22%3B%0A%09mso-hansi-font-family%3ATahoma%3B%0A%09mso-bidi-font-family%3A%22Times%20New%20Roman%22%3B%0A%09mso-fareast-language%3AFR%3B%0A%09font-weight%3Abold%3B%7D%0Aspan.NotedebasdepageCar%0A%09%7Bmso-style-name%3A%22Note%20de%20bas%20de%20page%20Car%22%3B%0A%09mso-style-noshow%3Ayes%3B%0A%09mso-style-priority%3A99%3B%0A%09mso-style-unhide%3Ano%3B%0A%09mso-style-locked%3Ayes%3B%0A%09mso-style-link%3A%22Note%20de%20bas%20de%20page%22%3B%0A%09mso-ansi-font-size%3A8.0pt%3B%0A%09mso-bidi-font-size%3A10.0pt%3B%0A%09font-family%3A%22Tahoma%22%2Csans-serif%3B%0A%09mso-ascii-font-family%3ATahoma%3B%0A%09mso-fareast-font-family%3A%22Times%20New%20Roman%22%3B%0A%09mso-hansi-font-family%3ATahoma%3B%0A%09mso-bidi-font-family%3A%22Times%20New%20Roman%22%3B%0A%09mso-fareast-language%3AFR%3B%7D%0Aspan.Sous-titreCar%0A%09%7Bmso-style-name%3A%22Sous-titre%20Car%22%3B%0A%09mso-style-priority%3A11%3B%0A%09mso-style-unhide%3Ano%3B%0A%09mso-style-locked%3Ayes%3B%0A%09mso-style-link%3ASous-titre%3B%0A%09mso-bidi-font-size%3A12.0pt%3B%0A%09font-family%3A%22MS%20Mincho%22%3B%0A%09mso-fareast-font-family%3A%22MS%20Mincho%22%3B%0A%09mso-fareast-theme-font%3Aminor-fareast%3B%0A%09mso-bidi-font-family%3A%22Times%20New%20Roman%22%3B%0A%09color%3A%235A5A5A%3B%0A%09mso-themecolor%3Atext1%3B%0A%09mso-themetint%3A165%3B%0A%09letter-spacing%3A.75pt%3B%0A%09mso-fareast-language%3AFR%3B%7D%0Aspan.TitreCar%0A%09%7Bmso-style-name%3A%22Titre%20Car%22%3B%0A%09mso-style-priority%3A10%3B%0A%09mso-style-unhide%3Ano%3B%0A%09mso-style-locked%3Ayes%3B%0A%09mso-style-link%3ATitre%3B%0A%09mso-ansi-font-size%3A28.0pt%3B%0A%09mso-bidi-font-size%3A28.0pt%3B%0A%09font-family%3A%22Cambria%22%2Cserif%3B%0A%09mso-ascii-font-family%3ACambria%3B%0A%09mso-ascii-theme-font%3Amajor-latin%3B%0A%09mso-fareast-font-family%3A%22MS%20Gothic%22%3B%0A%09mso-fareast-theme-font%3Amajor-fareast%3B%0A%09mso-hansi-font-family%3ACambria%3B%0A%09mso-hansi-theme-font%3Amajor-latin%3B%0A%09mso-bidi-font-family%3A%22Times%20New%20Roman%22%3B%0A%09mso-bidi-theme-font%3Amajor-bidi%3B%0A%09letter-spacing%3A-.5pt%3B%0A%09mso-font-kerning%3A14.0pt%3B%0A%09mso-fareast-language%3AFR%3B%7D%0Ap.Dfinition%2C%20li.Dfinition%2C%20div.Dfinition%0A%09%7Bmso-style-name%3AD%C3%A9finition%3B%0A%09mso-style-unhide%3Ano%3B%0A%09margin-top%3A0cm%3B%0A%09margin-right%3A0cm%3B%0A%09margin-bottom%3A6.0pt%3B%0A%09margin-left%3A36.0pt%3B%0A%09text-align%3Ajustify%3B%0A%09text-indent%3A-36.0pt%3B%0A%09mso-pagination%3Awidow-orphan%3B%0A%09mso-list%3Al4%20level1%20lfo1%3B%0A%09font-size%3A8.0pt%3B%0A%09mso-bidi-font-size%3A9.0pt%3B%0A%09font-family%3A%22Tahoma%22%2Csans-serif%3B%0A%09mso-fareast-font-family%3A%22Times%20New%20Roman%22%3B%0A%09mso-fareast-language%3AFR%3B%0A%09font-weight%3Abold%3B%0A%09mso-bidi-font-weight%3Anormal%3B%7D%0Ap.Liste-%2C%20li.Liste-%2C%20div.Liste-%0A%09%7Bmso-style-name%3A%22Liste%20-%22%3B%0A%09mso-style-unhide%3Ano%3B%0A%09mso-style-qformat%3Ayes%3B%0A%09margin-top%3A0cm%3B%0A%09margin-right%3A0cm%3B%0A%09margin-bottom%3A6.0pt%3B%0A%09margin-left%3A36.0pt%3B%0A%09text-align%3Ajustify%3B%0A%09text-indent%3A-18.0pt%3B%0A%09mso-pagination%3Awidow-orphan%3B%0A%09mso-list%3Al3%20level1%20lfo2%3B%0A%09tab-stops%3Alist%2018.0pt%3B%0A%09font-size%3A8.0pt%3B%0A%09mso-bidi-font-size%3A12.0pt%3B%0A%09font-family%3A%22Tahoma%22%2Csans-serif%3B%0A%09mso-fareast-font-family%3A%22Times%20New%20Roman%22%3B%0A%09mso-bidi-font-family%3A%22Times%20New%20Roman%22%3B%0A%09mso-fareast-language%3AFR%3B%7D%0Ap.Liste-1%2C%20li.Liste-1%2C%20div.Liste-1%0A%09%7Bmso-style-name%3AListe-1%3B%0A%09mso-style-unhide%3Ano%3B%0A%09margin-top%3A0cm%3B%0A%09margin-right%3A0cm%3B%0A%09margin-bottom%3A6.0pt%3B%0A%09margin-left%3A45.35pt%3B%0A%09text-align%3Ajustify%3B%0A%09text-indent%3A-27.35pt%3B%0A%09mso-pagination%3Awidow-orphan%3B%0A%09mso-list%3Al5%20level1%20lfo4%3B%0A%09tab-stops%3Alist%2045.35pt%3B%0A%09font-size%3A8.0pt%3B%0A%09mso-bidi-font-size%3A10.0pt%3B%0A%09font-family%3A%22Tahoma%22%2Csans-serif%3B%0A%09mso-fareast-font-family%3A%22Times%20New%20Roman%22%3B%0A%09mso-bidi-font-family%3A%22Times%20New%20Roman%22%3B%0A%09mso-fareast-language%3AFR%3B%0A%09mso-bidi-language%3AFR%3B%7D%0Ap.Prambule%2C%20li.Prambule%2C%20div.Prambule%0A%09%7Bmso-style-name%3APr%C3%A9ambule%3B%0A%09mso-style-unhide%3Ano%3B%0A%09margin-top%3A0cm%3B%0A%09margin-right%3A0cm%3B%0A%09margin-bottom%3A6.0pt%3B%0A%09margin-left%3A39.0pt%3B%0A%09text-align%3Ajustify%3B%0A%09text-indent%3A-21.0pt%3B%0A%09mso-pagination%3Awidow-orphan%3B%0A%09mso-list%3Al0%20level1%20lfo5%3B%0A%09tab-stops%3A42.55pt%3B%0A%09font-size%3A8.0pt%3B%0A%09mso-bidi-font-size%3A12.0pt%3B%0A%09font-family%3A%22Tahoma%22%2Csans-serif%3B%0A%09mso-fareast-font-family%3A%22Times%20New%20Roman%22%3B%0A%09mso-bidi-font-family%3A%22Times%20New%20Roman%22%3B%0A%09mso-fareast-language%3AFR%3B%7D%0Aspan.ParagraphedelisteCar%0A%09%7Bmso-style-name%3A%22Paragraphe%20de%20liste%20Car%5C%2CD%C3%A9finitions%20Car%22%3B%0A%09mso-style-priority%3A34%3B%0A%09mso-style-unhide%3Ano%3B%0A%09mso-style-locked%3Ayes%3B%0A%09mso-style-link%3A%22Paragraphe%20de%20liste%5C%2CD%C3%A9finitions%22%3B%0A%09mso-ansi-font-size%3A8.0pt%3B%0A%09mso-bidi-font-size%3A12.0pt%3B%0A%09font-family%3A%22Tahoma%22%2Csans-serif%3B%0A%09mso-ascii-font-family%3ATahoma%3B%0A%09mso-fareast-font-family%3A%22Times%20New%20Roman%22%3B%0A%09mso-hansi-font-family%3ATahoma%3B%0A%09mso-bidi-font-family%3A%22Times%20New%20Roman%22%3B%0A%09mso-fareast-language%3AFR%3B%7D%0A.MsoChpDefault%0A%09%7Bmso-style-type%3Aexport-only%3B%0A%09mso-default-props%3Ayes%3B%0A%09font-size%3A11.0pt%3B%0A%09mso-ansi-font-size%3A11.0pt%3B%0A%09mso-bidi-font-size%3A11.0pt%3B%0A%09font-family%3A%22Calibri%22%2Csans-serif%3B%0A%09mso-ascii-font-family%3ACalibri%3B%0A%09mso-ascii-theme-font%3Aminor-latin%3B%0A%09mso-fareast-font-family%3ACalibri%3B%0A%09mso-fareast-theme-font%3Aminor-latin%3B%0A%09mso-hansi-font-family%3ACalibri%3B%0A%09mso-hansi-theme-font%3Aminor-latin%3B%0A%09mso-bidi-font-family%3A%22Times%20New%20Roman%22%3B%0A%09mso-bidi-theme-font%3Aminor-bidi%3B%0A%09mso-fareast-language%3AEN-US%3B%7D%0A.MsoPapDefault%0A%09%7Bmso-style-type%3Aexport-only%3B%0A%09margin-bottom%3A10.0pt%3B%0A%09line-height%3A115%25%3B%7D%0A%40page%20WordSection1%0A%09%7Bsize%3A612.0pt%20792.0pt%3B%0A%09margin%3A36.0pt%2036.0pt%2036.0pt%2036.0pt%3B%0A%09mso-header-margin%3A36.0pt%3B%0A%09mso-footer-margin%3A36.0pt%3B%0A%09mso-paper-source%3A0%3B%7D%0Adiv.WordSection1%0A%09%7Bpage%3AWordSection1%3B%7D%0A%40page%20WordSection2%0A%09%7Bsize%3A612.0pt%20792.0pt%3B%0A%09margin%3A36.0pt%2036.0pt%2036.0pt%2036.0pt%3B%0A%09mso-header-margin%3A36.0pt%3B%0A%09mso-footer-margin%3A36.0pt%3B%0A%09mso-columns%3A2%20even%201.0cm%3B%0A%09mso-column-separator%3Asolid%3B%0A%09mso-paper-source%3A0%3B%7D%0Adiv.WordSection2%0A%09%7Bpage%3AWordSection2%3B%7D%0A%40page%20WordSection3%0A%09%7Bsize%3A612.0pt%20792.0pt%3B%0A%09margin%3A2.0cm%202.0cm%202.0cm%202.0cm%3B%0A%09mso-header-margin%3A36.0pt%3B%0A%09mso-footer-margin%3A36.0pt%3B%0A%09mso-paper-source%3A0%3B%7D%0Adiv.WordSection3%0A%09%7Bpage%3AWordSection3%3B%7D%0A%20%2F*%20List%20Definitions%20*%2F%0A%20%40list%20l0%0A%09%7Bmso-list-id%3A110443166%3B%0A%09mso-list-type%3Ahybrid%3B%0A%09mso-list-template-ids%3A-1659977420%20640965960%2067895321%2067895323%2067895311%2067895321%2067895323%2067895311%2067895321%2067895323%3B%7D%0A%40list%20l0%3Alevel1%0A%09%7Bmso-level-number-format%3Aalpha-upper%3B%0A%09mso-level-style-link%3APr%C3%A9ambule%3B%0A%09mso-level-text%3A%22%5C(%251%5C)%22%3B%0A%09mso-level-tab-stop%3A39.0pt%3B%0A%09mso-level-number-position%3Aleft%3B%0A%09margin-left%3A39.0pt%3B%0A%09text-indent%3A-21.0pt%3B%0A%09mso-ansi-font-weight%3Anormal%3B%7D%0A%40list%20l0%3Alevel2%0A%09%7Bmso-level-number-format%3Aalpha-lower%3B%0A%09mso-level-tab-stop%3A72.0pt%3B%0A%09mso-level-number-position%3Aleft%3B%0A%09text-indent%3A-18.0pt%3B%7D%0A%40list%20l0%3Alevel3%0A%09%7Bmso-level-number-format%3Aroman-lower%3B%0A%09mso-level-tab-stop%3A108.0pt%3B%0A%09mso-level-number-position%3Aright%3B%0A%09text-indent%3A-9.0pt%3B%7D%0A%40list%20l0%3Alevel5%0A%09%7Bmso-level-number-format%3Aalpha-lower%3B%0A%09mso-level-tab-stop%3A180.0pt%3B%0A%09mso-level-number-position%3Aleft%3B%0A%09text-indent%3A-18.0pt%3B%7D%0A%40list%20l0%3Alevel6%0A%09%7Bmso-level-number-format%3Aroman-lower%3B%0A%09mso-level-tab-stop%3A216.0pt%3B%0A%09mso-level-number-position%3Aright%3B%0A%09text-indent%3A-9.0pt%3B%7D%0A%40list%20l0%3Alevel8%0A%09%7Bmso-level-number-format%3Aalpha-lower%3B%0A%09mso-level-tab-stop%3A288.0pt%3B%0A%09mso-level-number-position%3Aleft%3B%0A%09text-indent%3A-18.0pt%3B%7D%0A%40list%20l0%3Alevel9%0A%09%7Bmso-level-number-format%3Aroman-lower%3B%0A%09mso-level-tab-stop%3A324.0pt%3B%0A%09mso-level-number-position%3Aright%3B%0A%09text-indent%3A-9.0pt%3B%7D%0A%40list%20l1%0A%09%7Bmso-list-id%3A128205798%3B%0A%09mso-list-template-ids%3A-658443948%3B%0A%09mso-list-style-name%3AListe1%3B%7D%0A%40list%20l1%3Alevel1%0A%09%7Bmso-level-start-at%3A0%3B%0A%09mso-level-number-format%3Abullet%3B%0A%09mso-level-text%3A-%3B%0A%09mso-level-tab-stop%3Anone%3B%0A%09mso-level-number-position%3Aleft%3B%0A%09margin-left%3A53.5pt%3B%0A%09text-indent%3A-35.5pt%3B%0A%09mso-ansi-font-size%3A12.0pt%3B%0A%09mso-ascii-font-family%3ATahoma%3B%0A%09mso-hansi-font-family%3ATahoma%3B%7D%0A%40list%20l1%3Alevel2%0A%09%7Bmso-level-number-format%3Abullet%3B%0A%09mso-level-text%3Ao%3B%0A%09mso-level-tab-stop%3Anone%3B%0A%09mso-level-number-position%3Aleft%3B%0A%09text-indent%3A-18.0pt%3B%0A%09font-family%3A%22Courier%20New%22%3B%7D%0A%40list%20l1%3Alevel3%0A%09%7Bmso-level-number-format%3Abullet%3B%0A%09mso-level-text%3A%EF%82%A7%3B%0A%09mso-level-tab-stop%3Anone%3B%0A%09mso-level-number-position%3Aleft%3B%0A%09text-indent%3A-18.0pt%3B%0A%09font-family%3AWingdings%3B%7D%0A%40list%20l1%3Alevel4%0A%09%7Bmso-level-number-format%3Abullet%3B%0A%09mso-level-text%3A%EF%82%B7%3B%0A%09mso-level-tab-stop%3Anone%3B%0A%09mso-level-number-position%3Aleft%3B%0A%09text-indent%3A-18.0pt%3B%0A%09font-family%3ASymbol%3B%7D%0A%40list%20l1%3Alevel5%0A%09%7Bmso-level-number-format%3Abullet%3B%0A%09mso-level-text%3Ao%3B%0A%09mso-level-tab-stop%3Anone%3B%0A%09mso-level-number-position%3Aleft%3B%0A%09text-indent%3A-18.0pt%3B%0A%09font-family%3A%22Courier%20New%22%3B%7D%0A%40list%20l1%3Alevel6%0A%09%7Bmso-level-number-format%3Abullet%3B%0A%09mso-level-text%3A%EF%82%A7%3B%0A%09mso-level-tab-stop%3Anone%3B%0A%09mso-level-number-position%3Aleft%3B%0A%09text-indent%3A-18.0pt%3B%0A%09font-family%3AWingdings%3B%7D%0A%40list%20l1%3Alevel7%0A%09%7Bmso-level-number-format%3Abullet%3B%0A%09mso-level-text%3A%EF%82%B7%3B%0A%09mso-level-tab-stop%3Anone%3B%0A%09mso-level-number-position%3Aleft%3B%0A%09text-indent%3A-18.0pt%3B%0A%09font-family%3ASymbol%3B%7D%0A%40list%20l1%3Alevel8%0A%09%7Bmso-level-number-format%3Abullet%3B%0A%09mso-level-text%3Ao%3B%0A%09mso-level-tab-stop%3Anone%3B%0A%09mso-level-number-position%3Aleft%3B%0A%09text-indent%3A-18.0pt%3B%0A%09font-family%3A%22Courier%20New%22%3B%7D%0A%40list%20l1%3Alevel9%0A%09%7Bmso-level-number-format%3Abullet%3B%0A%09mso-level-text%3A%EF%82%A7%3B%0A%09mso-level-tab-stop%3Anone%3B%0A%09mso-level-number-position%3Aleft%3B%0A%09text-indent%3A-18.0pt%3B%0A%09font-family%3AWingdings%3B%7D%0A%40list%20l2%0A%09%7Bmso-list-id%3A356004662%3B%0A%09mso-list-template-ids%3A486293926%3B%7D%0A%40list%20l2%3Alevel1%0A%09%7Bmso-level-style-link%3A%22Titre%201%22%3B%0A%09mso-level-text%3A%22Article%20%251%22%3B%0A%09mso-level-tab-stop%3A36.0pt%3B%0A%09mso-level-number-position%3Aleft%3B%0A%09text-indent%3A-36.0pt%3B%0A%09letter-spacing%3A0pt%3B%0A%09mso-ansi-font-weight%3Abold%3B%0A%09mso-ansi-font-style%3Anormal%3B%7D%0A%40list%20l2%3Alevel2%0A%09%7Bmso-level-style-link%3A%22Titre%202%22%3B%0A%09mso-level-text%3A%22%251%5C.%252%22%3B%0A%09mso-level-tab-stop%3A36.85pt%3B%0A%09mso-level-number-position%3Aleft%3B%0A%09margin-left%3A36.85pt%3B%0A%09text-indent%3A-36.85pt%3B%0A%09letter-spacing%3A0pt%3B%0A%09mso-ansi-font-weight%3Abold%3B%0A%09mso-ansi-font-style%3Anormal%3B%7D%0A%40list%20l2%3Alevel3%0A%09%7Bmso-level-style-link%3A%22Titre%203%22%3B%0A%09mso-level-text%3A%22%251%5C.%252%5C.%253%22%3B%0A%09mso-level-tab-stop%3A36.85pt%3B%0A%09mso-level-number-position%3Aleft%3B%0A%09margin-left%3A36.85pt%3B%0A%09text-indent%3A-36.85pt%3B%0A%09letter-spacing%3A0pt%3B%0A%09mso-ansi-font-weight%3Anormal%3B%0A%09mso-ansi-font-style%3Aitalic%3B%7D%0A%40list%20l2%3Alevel4%0A%09%7Bmso-level-text%3A%22%251%5C.%252%5C.%253%5C.%254%22%3B%0A%09mso-level-tab-stop%3A134.95pt%3B%0A%09mso-level-number-position%3Aleft%3B%0A%09margin-left%3A134.95pt%3B%0A%09text-indent%3A-53.85pt%3B%0A%09letter-spacing%3A0pt%3B%0A%09mso-ansi-font-weight%3Anormal%3B%0A%09mso-ansi-font-style%3Anormal%3B%7D%0A%40list%20l2%3Alevel5%0A%09%7Bmso-level-number-format%3Aalpha-lower%3B%0A%09mso-level-text%3A%22%5C(%255%5C)%22%3B%0A%09mso-level-tab-stop%3A134.95pt%3B%0A%09mso-level-number-position%3Aleft%3B%0A%09margin-left%3A134.95pt%3B%0A%09text-indent%3A-53.85pt%3B%0A%09letter-spacing%3A0pt%3B%0A%09mso-ansi-font-weight%3Anormal%3B%0A%09mso-ansi-font-style%3Anormal%3B%7D%0A%40list%20l2%3Alevel6%0A%09%7Bmso-level-number-format%3Aroman-lower%3B%0A%09mso-level-text%3A%22%5C(%256%5C)%22%3B%0A%09mso-level-tab-stop%3A179.85pt%3B%0A%09mso-level-number-position%3Aleft%3B%0A%09margin-left%3A161.9pt%3B%0A%09text-indent%3A-26.95pt%3B%0A%09mso-ascii-font-family%3ATahoma%3B%0A%09mso-fareast-font-family%3A%22Times%20New%20Roman%22%3B%0A%09mso-hansi-font-family%3ATahoma%3B%0A%09mso-bidi-font-family%3A%22Times%20New%20Roman%22%3B%0A%09letter-spacing%3A0pt%3B%0A%09mso-ansi-font-weight%3Anormal%3B%0A%09mso-ansi-font-style%3Anormal%3B%7D%0A%40list%20l2%3Alevel7%0A%09%7Bmso-level-number-format%3Aalpha-upper%3B%0A%09mso-level-text%3A%22%5C(%257%5C)%22%3B%0A%09mso-level-tab-stop%3A195.35pt%3B%0A%09mso-level-number-position%3Aleft%3B%0A%09margin-left%3A195.35pt%3B%0A%09text-indent%3A-33.75pt%3B%0A%09letter-spacing%3A0pt%3B%0A%09mso-ansi-font-weight%3Anormal%3B%0A%09mso-ansi-font-style%3Anormal%3B%7D%0A%40list%20l2%3Alevel8%0A%09%7Bmso-level-number-format%3Aroman-upper%3B%0A%09mso-level-text%3A%22%5C(%258%5C)%22%3B%0A%09mso-level-tab-stop%3A229.05pt%3B%0A%09mso-level-number-position%3Aleft%3B%0A%09margin-left%3A229.05pt%3B%0A%09text-indent%3A-33.7pt%3B%0A%09letter-spacing%3A0pt%3B%0A%09mso-ansi-font-weight%3Anormal%3B%0A%09mso-ansi-font-style%3Anormal%3B%7D%0A%40list%20l2%3Alevel9%0A%09%7Bmso-level-number-format%3Aroman-upper%3B%0A%09mso-level-text%3A%22%5C(%259%5C)%22%3B%0A%09mso-level-tab-stop%3A359.9pt%3B%0A%09mso-level-number-position%3Aleft%3B%0A%09margin-left%3A341.9pt%3B%0A%09text-indent%3A-36.0pt%3B%0A%09letter-spacing%3A0pt%3B%0A%09mso-ansi-font-weight%3Anormal%3B%0A%09mso-ansi-font-style%3Anormal%3B%7D%0A%40list%20l3%0A%09%7Bmso-list-id%3A726106042%3B%0A%09mso-list-type%3Ahybrid%3B%0A%09mso-list-template-ids%3A966313546%201990992190%2067895299%2067895301%2067895297%2067895299%2067895301%2067895297%2067895299%2067895301%3B%7D%0A%40list%20l3%3Alevel1%0A%09%7Bmso-level-start-at%3A0%3B%0A%09mso-level-number-format%3Abullet%3B%0A%09mso-level-style-link%3A%22Liste%20-%22%3B%0A%09mso-level-text%3A-%3B%0A%09mso-level-tab-stop%3Anone%3B%0A%09mso-level-number-position%3Aleft%3B%0A%09text-indent%3A-18.0pt%3B%0A%09font-family%3A%22Verdana%22%2Csans-serif%3B%0A%09mso-fareast-font-family%3A%22Times%20New%20Roman%22%3B%0A%09mso-bidi-font-family%3A%22Courier%20New%22%3B%7D%0A%40list%20l3%3Alevel2%0A%09%7Bmso-level-number-format%3Abullet%3B%0A%09mso-level-text%3Ao%3B%0A%09mso-level-tab-stop%3Anone%3B%0A%09mso-level-number-position%3Aleft%3B%0A%09text-indent%3A-18.0pt%3B%0A%09font-family%3A%22Courier%20New%22%3B%0A%09mso-bidi-font-family%3A%22Times%20New%20Roman%22%3B%7D%0A%40list%20l3%3Alevel3%0A%09%7Bmso-level-number-format%3Abullet%3B%0A%09mso-level-text%3A%EF%82%A7%3B%0A%09mso-level-tab-stop%3Anone%3B%0A%09mso-level-number-position%3Aleft%3B%0A%09text-indent%3A-18.0pt%3B%0A%09font-family%3AWingdings%3B%7D%0A%40list%20l3%3Alevel4%0A%09%7Bmso-level-number-format%3Abullet%3B%0A%09mso-level-text%3A%EF%82%B7%3B%0A%09mso-level-tab-stop%3Anone%3B%0A%09mso-level-number-position%3Aleft%3B%0A%09text-indent%3A-18.0pt%3B%0A%09font-family%3ASymbol%3B%7D%0A%40list%20l3%3Alevel5%0A%09%7Bmso-level-number-format%3Abullet%3B%0A%09mso-level-text%3Ao%3B%0A%09mso-level-tab-stop%3Anone%3B%0A%09mso-level-number-position%3Aleft%3B%0A%09text-indent%3A-18.0pt%3B%0A%09font-family%3A%22Courier%20New%22%3B%0A%09mso-bidi-font-family%3A%22Times%20New%20Roman%22%3B%7D%0A%40list%20l3%3Alevel6%0A%09%7Bmso-level-number-format%3Abullet%3B%0A%09mso-level-text%3A%EF%82%A7%3B%0A%09mso-level-tab-stop%3Anone%3B%0A%09mso-level-number-position%3Aleft%3B%0A%09text-indent%3A-18.0pt%3B%0A%09font-family%3AWingdings%3B%7D%0A%40list%20l3%3Alevel7%0A%09%7Bmso-level-number-format%3Abullet%3B%0A%09mso-level-text%3A%EF%82%B7%3B%0A%09mso-level-tab-stop%3Anone%3B%0A%09mso-level-number-position%3Aleft%3B%0A%09text-indent%3A-18.0pt%3B%0A%09font-family%3ASymbol%3B%7D%0A%40list%20l3%3Alevel8%0A%09%7Bmso-level-number-format%3Abullet%3B%0A%09mso-level-text%3Ao%3B%0A%09mso-level-tab-stop%3Anone%3B%0A%09mso-level-number-position%3Aleft%3B%0A%09text-indent%3A-18.0pt%3B%0A%09font-family%3A%22Courier%20New%22%3B%0A%09mso-bidi-font-family%3A%22Times%20New%20Roman%22%3B%7D%0A%40list%20l3%3Alevel9%0A%09%7Bmso-level-number-format%3Abullet%3B%0A%09mso-level-text%3A%EF%82%A7%3B%0A%09mso-level-tab-stop%3Anone%3B%0A%09mso-level-number-position%3Aleft%3B%0A%09text-indent%3A-18.0pt%3B%0A%09font-family%3AWingdings%3B%7D%0A%40list%20l4%0A%09%7Bmso-list-id%3A1119572507%3B%0A%09mso-list-type%3Ahybrid%3B%0A%09mso-list-template-ids%3A-1673080934%20-810775128%2067895321%2067895323%2067895311%2067895321%2067895323%2067895311%2067895321%2067895323%3B%7D%0A%40list%20l4%3Alevel1%0A%09%7Bmso-level-style-link%3AD%C3%A9finition%3B%0A%09mso-level-text%3A%221%5C.%251%22%3B%0A%09mso-level-tab-stop%3Anone%3B%0A%09mso-level-number-position%3Aleft%3B%0A%09margin-left%3A32.2pt%3B%0A%09text-indent%3A-18.0pt%3B%0A%09mso-ansi-font-size%3A8.0pt%3B%0A%09mso-bidi-font-size%3A8.0pt%3B%0A%09font-family%3A%22Tahoma%22%2Csans-serif%3B%0A%09mso-bidi-font-family%3A%22Times%20New%20Roman%22%3B%0A%09mso-ansi-font-weight%3Abold%3B%0A%09mso-ansi-font-style%3Anormal%3B%7D%0A%40list%20l4%3Alevel2%0A%09%7Bmso-level-number-format%3Aalpha-lower%3B%0A%09mso-level-tab-stop%3Anone%3B%0A%09mso-level-number-position%3Aleft%3B%0A%09text-indent%3A-18.0pt%3B%7D%0A%40list%20l4%3Alevel3%0A%09%7Bmso-level-number-format%3Aroman-lower%3B%0A%09mso-level-tab-stop%3Anone%3B%0A%09mso-level-number-position%3Aright%3B%0A%09text-indent%3A-9.0pt%3B%7D%0A%40list%20l4%3Alevel4%0A%09%7Bmso-level-tab-stop%3Anone%3B%0A%09mso-level-number-position%3Aleft%3B%0A%09text-indent%3A-18.0pt%3B%7D%0A%40list%20l4%3Alevel5%0A%09%7Bmso-level-number-format%3Aalpha-lower%3B%0A%09mso-level-tab-stop%3Anone%3B%0A%09mso-level-number-position%3Aleft%3B%0A%09text-indent%3A-18.0pt%3B%7D%0A%40list%20l4%3Alevel6%0A%09%7Bmso-level-number-format%3Aroman-lower%3B%0A%09mso-level-tab-stop%3Anone%3B%0A%09mso-level-number-position%3Aright%3B%0A%09text-indent%3A-9.0pt%3B%7D%0A%40list%20l4%3Alevel7%0A%09%7Bmso-level-tab-stop%3Anone%3B%0A%09mso-level-number-position%3Aleft%3B%0A%09text-indent%3A-18.0pt%3B%7D%0A%40list%20l4%3Alevel8%0A%09%7Bmso-level-number-format%3Aalpha-lower%3B%0A%09mso-level-tab-stop%3Anone%3B%0A%09mso-level-number-position%3Aleft%3B%0A%09text-indent%3A-18.0pt%3B%7D%0A%40list%20l4%3Alevel9%0A%09%7Bmso-level-number-format%3Aroman-lower%3B%0A%09mso-level-tab-stop%3Anone%3B%0A%09mso-level-number-position%3Aright%3B%0A%09text-indent%3A-9.0pt%3B%7D%0A%40list%20l5%0A%09%7Bmso-list-id%3A1681423315%3B%0A%09mso-list-type%3Ahybrid%3B%0A%09mso-list-template-ids%3A588678518%20-337716028%201639436%201770508%20984076%201639436%201770508%20984076%201639436%201770508%3B%7D%0A%40list%20l5%3Alevel1%0A%09%7Bmso-level-number-format%3Aroman-lower%3B%0A%09mso-level-style-link%3AListe-1%3B%0A%09mso-level-text%3A%22%5C(%251%5C)%22%3B%0A%09mso-level-tab-stop%3A45.35pt%3B%0A%09mso-level-number-position%3Aleft%3B%0A%09margin-left%3A45.35pt%3B%0A%09text-indent%3A-27.35pt%3B%7D%0A%40list%20l5%3Alevel2%0A%09%7Bmso-level-number-format%3Aalpha-lower%3B%0A%09mso-level-tab-stop%3A72.0pt%3B%0A%09mso-level-number-position%3Aleft%3B%0A%09text-indent%3A-18.0pt%3B%7D%0A%40list%20l5%3Alevel3%0A%09%7Bmso-level-number-format%3Aroman-lower%3B%0A%09mso-level-tab-stop%3A108.0pt%3B%0A%09mso-level-number-position%3Aright%3B%0A%09text-indent%3A-9.0pt%3B%7D%0A%40list%20l5%3Alevel5%0A%09%7Bmso-level-number-format%3Aalpha-lower%3B%0A%09mso-level-tab-stop%3A180.0pt%3B%0A%09mso-level-number-position%3Aleft%3B%0A%09text-indent%3A-18.0pt%3B%7D%0A%40list%20l5%3Alevel6%0A%09%7Bmso-level-number-format%3Aroman-lower%3B%0A%09mso-level-tab-stop%3A216.0pt%3B%0A%09mso-level-number-position%3Aright%3B%0A%09text-indent%3A-9.0pt%3B%7D%0A%40list%20l5%3Alevel8%0A%09%7Bmso-level-number-format%3Aalpha-lower%3B%0A%09mso-level-tab-stop%3A288.0pt%3B%0A%09mso-level-number-position%3Aleft%3B%0A%09text-indent%3A-18.0pt%3B%7D%0A%40list%20l5%3Alevel9%0A%09%7Bmso-level-number-format%3Aroman-lower%3B%0A%09mso-level-tab-stop%3A324.0pt%3B%0A%09mso-level-number-position%3Aright%3B%0A%09text-indent%3A-9.0pt%3B%7D%0A%40list%20l6%0A%09%7Bmso-list-id%3A1698193465%3B%0A%09mso-list-type%3Ahybrid%3B%0A%09mso-list-template-ids%3A-1697206230%20-337716028%2067895321%2067895323%2067895311%2067895321%2067895323%2067895311%2067895321%2067895323%3B%7D%0A%40list%20l6%3Alevel1%0A%09%7Bmso-level-number-format%3Aroman-lower%3B%0A%09mso-level-text%3A%22%5C(%251%5C)%22%3B%0A%09mso-level-tab-stop%3A45.35pt%3B%0A%09mso-level-number-position%3Aleft%3B%0A%09margin-left%3A45.35pt%3B%0A%09text-indent%3A-27.35pt%3B%7D%0A%40list%20l6%3Alevel2%0A%09%7Bmso-level-number-format%3Aalpha-lower%3B%0A%09mso-level-tab-stop%3A72.0pt%3B%0A%09mso-level-number-position%3Aleft%3B%0A%09text-indent%3A-18.0pt%3B%7D%0A%40list%20l6%3Alevel3%0A%09%7Bmso-level-number-format%3Aroman-lower%3B%0A%09mso-level-tab-stop%3A108.0pt%3B%0A%09mso-level-number-position%3Aright%3B%0A%09text-indent%3A-9.0pt%3B%7D%0A%40list%20l6%3Alevel5%0A%09%7Bmso-level-number-format%3Aalpha-lower%3B%0A%09mso-level-tab-stop%3A180.0pt%3B%0A%09mso-level-number-position%3Aleft%3B%0A%09text-indent%3A-18.0pt%3B%7D%0A%40list%20l6%3Alevel6%0A%09%7Bmso-level-number-format%3Aroman-lower%3B%0A%09mso-level-tab-stop%3A216.0pt%3B%0A%09mso-level-number-position%3Aright%3B%0A%09text-indent%3A-9.0pt%3B%7D%0A%40list%20l6%3Alevel8%0A%09%7Bmso-level-number-format%3Aalpha-lower%3B%0A%09mso-level-tab-stop%3A288.0pt%3B%0A%09mso-level-number-position%3Aleft%3B%0A%09text-indent%3A-18.0pt%3B%7D%0A%40list%20l6%3Alevel9%0A%09%7Bmso-level-number-format%3Aroman-lower%3B%0A%09mso-level-tab-stop%3A324.0pt%3B%0A%09mso-level-number-position%3Aright%3B%0A%09text-indent%3A-9.0pt%3B%7D%0A%40list%20l7%0A%09%7Bmso-list-id%3A1867982377%3B%0A%09mso-list-template-ids%3A-459391520%3B%7D%0A%40list%20l7%3Alevel1%0A%09%7Bmso-level-number-format%3Aalpha-upper%3B%0A%09mso-level-style-link%3A%22Titre%204%22%3B%0A%09mso-level-suffix%3Anone%3B%0A%09mso-level-text%3A%22Annexe%20%251%22%3B%0A%09mso-level-tab-stop%3Anone%3B%0A%09mso-level-number-position%3Aleft%3B%0A%09margin-left%3A0cm%3B%0A%09text-indent%3A0cm%3B%0A%09font-variant%3Anormal%20!important%3B%0A%09mso-text-animation%3Anone%3B%0A%09mso-hide%3Anone%3B%0A%09text-transform%3Anone%3B%0A%09position%3Arelative%3B%0A%09top%3A0pt%3B%0A%09mso-text-raise%3A0pt%3B%0A%09letter-spacing%3A0pt%3B%0A%09mso-font-kerning%3A0pt%3B%0A%09font-emphasize%3Anone%3B%0A%09mso-ansi-font-weight%3Abold%3B%0A%09mso-bidi-font-weight%3Anormal%3B%0A%09mso-ansi-font-style%3Anormal%3B%0A%09mso-bidi-font-style%3Anormal%3B%0A%09mso-no-proof%3Ano%3B%0A%09text-decoration%3Anone%3B%0A%09text-underline%3Anone%3B%0A%09text-decoration%3Anone%3B%0A%09text-line-through%3Anone%3B%0A%09vertical-align%3Abaseline%3B%7D%0A%40list%20l7%3Alevel2%0A%09%7Bmso-level-style-link%3A%22Titre%205%22%3B%0A%09mso-level-tab-stop%3A1.0cm%3B%0A%09mso-level-number-position%3Aleft%3B%0A%09margin-left%3A1.0cm%3B%0A%09text-indent%3A-1.0cm%3B%0A%09font-variant%3Anormal%20!important%3B%0A%09color%3Ablack%3B%0A%09mso-hide%3Anone%3B%0A%09text-transform%3Anone%3B%0A%09position%3Arelative%3B%0A%09top%3A0pt%3B%0A%09mso-text-raise%3A0pt%3B%0A%09letter-spacing%3A0pt%3B%0A%09mso-font-kerning%3A0pt%3B%0A%09mso-ansi-font-weight%3Abold%3B%0A%09mso-ansi-font-style%3Anormal%3B%0A%09text-decoration%3Anone%3B%0A%09text-underline%3Anone%3B%0A%09text-decoration%3Anone%3B%0A%09text-line-through%3Anone%3B%0A%09vertical-align%3Abaseline%3B%7D%0A%40list%20l7%3Alevel3%0A%09%7Bmso-level-style-link%3A%22Titre%206%22%3B%0A%09mso-level-text%3A%22%252%5C.%253%22%3B%0A%09mso-level-tab-stop%3A36.85pt%3B%0A%09mso-level-number-position%3Aleft%3B%0A%09margin-left%3A36.85pt%3B%0A%09text-indent%3A-36.85pt%3B%0A%09font-variant%3Anormal%20!important%3B%0A%09color%3Ablack%3B%0A%09mso-hide%3Anone%3B%0A%09text-transform%3Anone%3B%0A%09position%3Arelative%3B%0A%09top%3A0pt%3B%0A%09mso-text-raise%3A0pt%3B%0A%09letter-spacing%3A0pt%3B%0A%09mso-font-kerning%3A0pt%3B%0A%09mso-ansi-font-weight%3Abold%3B%0A%09mso-ansi-font-style%3Anormal%3B%0A%09text-decoration%3Anone%3B%0A%09text-underline%3Anone%3B%0A%09text-decoration%3Anone%3B%0A%09text-line-through%3Anone%3B%0A%09vertical-align%3Abaseline%3B%7D%0A%40list%20l7%3Alevel4%0A%09%7Bmso-level-style-link%3A%22Titre%207%22%3B%0A%09mso-level-text%3A%22%252%5C.%253%5C.%254%22%3B%0A%09mso-level-tab-stop%3A42.55pt%3B%0A%09mso-level-number-position%3Aleft%3B%0A%09margin-left%3A42.55pt%3B%0A%09text-indent%3A-42.55pt%3B%0A%09mso-ansi-font-size%3A10.0pt%3B%0A%09font-family%3A%22Tahoma%22%2Csans-serif%3B%0A%09mso-bidi-font-family%3A%22Times%20New%20Roman%22%3B%0A%09mso-ansi-font-weight%3Anormal%3B%0A%09mso-ansi-font-style%3Aitalic%3B%7D%0A%40list%20l7%3Alevel5%0A%09%7Bmso-level-text%3A%22%255%5C)%22%3B%0A%09mso-level-tab-stop%3A79.2pt%3B%0A%09mso-level-number-position%3Aleft%3B%0A%09margin-left%3A79.2pt%3B%0A%09text-indent%3A-21.6pt%3B%7D%0A%40list%20l7%3Alevel6%0A%09%7Bmso-level-number-format%3Aalpha-lower%3B%0A%09mso-level-text%3A%22%256%5C)%22%3B%0A%09mso-level-tab-stop%3A86.4pt%3B%0A%09mso-level-number-position%3Aleft%3B%0A%09margin-left%3A86.4pt%3B%0A%09text-indent%3A-21.6pt%3B%7D%0A%40list%20l7%3Alevel7%0A%09%7Bmso-level-number-format%3Aroman-lower%3B%0A%09mso-level-text%3A%22%257%5C)%22%3B%0A%09mso-level-tab-stop%3A93.6pt%3B%0A%09mso-level-number-position%3Aright%3B%0A%09margin-left%3A93.6pt%3B%0A%09text-indent%3A-14.4pt%3B%7D%0A%40list%20l7%3Alevel8%0A%09%7Bmso-level-number-format%3Aalpha-lower%3B%0A%09mso-level-tab-stop%3A100.8pt%3B%0A%09mso-level-number-position%3Aleft%3B%0A%09margin-left%3A100.8pt%3B%0A%09text-indent%3A-21.6pt%3B%7D%0A%40list%20l7%3Alevel9%0A%09%7Bmso-level-number-format%3Aroman-lower%3B%0A%09mso-level-tab-stop%3A108.0pt%3B%0A%09mso-level-number-position%3Aright%3B%0A%09margin-left%3A108.0pt%3B%0A%09text-indent%3A-7.2pt%3B%7D%0Aol%0A%09%7Bmargin-bottom%3A0cm%3B%7D%0Aul%0A%09%7Bmargin-bottom%3A0cm%3B%7D%0A%2D%2D%3E--></style>
\', \'\', 1, \'2019-07-05 14:38:06\', \'2019-07-05 14:38:06\')');
        $this->addSql('INSERT INTO settings (type, value, added) VALUES (\'TERMS_OF_SALE_PAGE_ID\', 96, NOW())');
        $this->addSql(
            <<<'TRANSLATIONS'
            INSERT INTO translations (locale, section, name, translation, added) VALUES
                ('fr_FR', 'tos-popup', 'title', 'Les conditions générales d‘utilisation évoluent.', NOW()),
                ('fr_FR', 'tos-popup', 'confirm-check-box-label', 'Je certifie avoir pris connaissance et accepter expressément <a href="/cgu">les conditions générales d‘utilisation de CALS</a>.', NOW())
TRANSLATIONS
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DELETE FROM settings WHERE type = \'TERMS_OF_SALE_PAGE_ID\'');
        $this->addSql('DELETE FROM tree_elements WHERE id_tree = 96');
        $this->addSql('DELETE FROM tree WHERE id_tree = 96');
        $this->addSql('INSERT INTO elements (id_element, id_template, id_bloc, name, slug, ordre, type_element, status, added, updated)
                    VALUES (144, 5, 0, \'Mandat de recouvrement - personne physique\', \'mandat-de-recouvrement\', 2, \'Texteditor\', 1, \'2016-09-14 09:24:37\', \'2016-09-14 09:24:37\'),
                           (145, 5, 0, \'Mandat de recouvrement - personne morale\', \'mandat-de-recouvrement-personne-morale\', 4, \'Texteditor\', 1, \'2016-09-14 09:24:37\', \'2016-09-14 09:24:37\'),
                           (146, 5, 0, \'Liste variables mandat de recouvrement\', \'backup-variables-mandat-de-recouvrement\', 6, \'Texte\', 1, \'2016-09-14 09:24:37\', \'2016-09-14 09:24:37\'),
                           (147, 5, 0, \'Contenu variables CGV non logué personne physique\', \'contenu-variables-par-defaut\', 7, \'Texte\', 1, \'2016-09-14 09:24:37\', \'2016-09-14 09:24:37\'),
                           (148, 5, 0, \'Contenu variables CGV non logué personne morale\', \'contenu-variables-par-defaut-morale\', 8, \'Texte\', 1, \'2016-09-14 09:24:37\', \'2016-09-14 09:24:37\'),
                           (149, 5, 0, \'Mandat de recouvrement avec prêt - personne physique\', \'mandat-de-recouvrement-avec-pret\', 3, \'Texteditor\', 1, \'2016-09-14 09:24:37\', \'2016-09-14 09:24:37\'),
                           (150, 5, 0, \'Mandat de recouvrement avec prêt - personne morale\', \'mandat-de-recouvrement-avec-pret-personne-morale\', 5, \'Texteditor\', 1, \'2016-09-14 09:24:37\', \'2016-09-14 09:24:37\')');

        $this->addSql('DELETE FROM translations WHERE section = \'tos-popup\' AND name in (\'title\', \'confirm-check-box-label\')');
    }
}
