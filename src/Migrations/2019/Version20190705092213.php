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
        return 'CALS-239 Insert new CGU';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('INSERT INTO tree (id_tree, id_langue, id_parent, id_template, id_user, title, slug, img_menu, menu_title, meta_title, meta_description, meta_keywords, ordre, status, status_menu, prive, indexation, added, updated) VALUES (96, \'fr\', 22, 5, 1, \'Conditions générales d‘utilisation\', \'conditions-generales-d-utilisation-96\', \'\', \'Conditions générales d‘utilisation\', \'Conditions générales d‘utilisation\', \'Conditions générales d‘utilisation\', \'\', 3, 1, 1, 0, 1, NOW(), NOW())');
        $this->addSql('DELETE FROM elements WHERE id_element in (144, 145, 146, 147, 148, 149, 150)');
        $this->addSql('UPDATE elements SET slug = \'service-terms-new\' WHERE slug = \'tos-new\'');
        $this->addSql('UPDATE elements SET slug = \'service-terms-update\' WHERE slug = \'tos-update\'');
        $this->addSql('UPDATE elements SET slug = \'service-terms-content\' WHERE slug = \'contenu-cgu\'');
        $this->addSql(
            <<<'TREE_ELEMENT'
            INSERT INTO tree_elements (id_tree, id_element, id_langue, value, complement, status, added, updated) VALUES (96, 142, 'fr', '<p>Les présentes conditions générales SaaS Crédit Agricole Lending Services (ci-après désignées les «&nbsp;<strong>Conditions Générales</strong>&nbsp;») régissent les relations entre la société Crédit Agricole Lending Services, société <strong>[•]</strong> au capital de <strong>[•]</strong> euros, immatriculée au registre du commerce et des sociétés de <strong>[•]</strong> sous le numéro <strong>[•]</strong>, dont le siège social est situé au <strong>[•]</strong> (ci-après désignée «&nbsp;<strong>Crédit Agricole Lending Services</strong>&nbsp;» ou «&nbsp;<strong>CALS</strong>&nbsp;») et toute personne morale qui utilise la Plateforme CALS (ci-après désignée le «&nbsp;<strong>Client</strong>&nbsp;»).</p>
<p>CALS et le Client sont ci-après dénommés individuellement une «&nbsp;<strong>Partie&nbsp;</strong>» et collectivement les «&nbsp;<strong>Parties&nbsp;</strong>».</p>
<h2>Article 1 Définitions</h2>
<p>Les termes et expressions dont la première lettre de chaque mot est en majuscule ont, au sein du Contrat, la signification qui leur est attribuée ci-après, qu’ils soient utilisés au singulier ou au pluriel.</p>
<ul>
  <li>«&nbsp;Agent&nbsp;» désigne l’Etablissement Autorisé ou l’Utilisateur Invité faisant partie du Pool Bancaire auquel il a été confié un rôle de suivi de l’Opération. Il est l’interlocuteur de l’Emprunteur pendant l’exécution de la Convention de Crédit.</li>
  <li>«&nbsp;Convention de Sous-Participation&nbsp;» désigne le document contractuel rédigé et signé dans le cadre d’une opération de Syndication Bancaire sur le Marché Secondaire, entre l’Etablissement Autorisé ou l’Utilisateur Invité cédant et le ou les Etablissement(s) Autorisé(s) et/ou l(es) Utilisateur(s) Invité(s) cessionnaire(s).</li>
  <li>«&nbsp;Arrangeur&nbsp;» désigne l’Etablissement Autorisé, intermédiaire de l’Emprunteur, qui est chargé d’organiser un financement et trouver un groupement d’Etablissements&nbsp; Autorisés et/ou d’Utilisateurs Invités qui accepte de financer l’Opération. L’Arrangeur est responsable de la constitution du Pool Bancaire, de la collecte des KYC et de la négociation et de l’établissement de la documentation contractuelle.</li>
  <li>«&nbsp;Conditions Particulières&nbsp;» désigne le contrat commercial conclu entre le Client et CALS qui détermine les conditions particulières d’accès à la Plateforme CALS et fixe le montant de la Redevance.</li>
  <li>«&nbsp;Contenus&nbsp;» désigne l’ensemble des éléments fournis par le Client via la Plateforme CALS (p. ex. marques, éléments distinctifs, documents commerciaux) protégés par des droits et/ou des autorisations dont le Client est titulaire.</li>
  <li>«&nbsp;Contrat&nbsp;» désigne les Conditions Générales ainsi que les annexes, avenants éventuels et les Conditions Particulières associées.</li>
  <li>«&nbsp;Convention de Crédit&nbsp;» désigne le document contractuel rédigé et signé dans le cadre d’une opération de Syndication Bancaire sur le Marché Primaire qui est composé (i) de la convention de prêt - qui régit les relations entre les Etablissements Bancaires et/ou les Utilisateurs Invités composant le Pool Bancaire et l’Emprunteur&nbsp;; et (ii) du contrat de syndicat, qui régit les relations entre les différents Etablissements Autorisés et/ou les Utilisateurs Invités, membres du Pool Bancaire.</li>
  <li>« Données Client» désigne l’ensemble des données relatives aux opérations de Syndication Bancaire proposées et réalisées par le Client via la Plateforme CALS.</li>
  <li>«&nbsp;Données Marché&nbsp;» désigne les données statistiques et anonymes relatives aux opérations de Syndication Bancaire réalisées via la Plateforme CALS.</li>
  <li>«&nbsp;Données Emprunteur&nbsp;» désigne les données à caractère personnel au sens de l’article 4 du Règlement 2016/679 relatif à la protection des personnes physiques à l’égard du traitement des données à caractère personnel et à la libre circulation de ces données (ci-après désigné le « RGPD») qui peuvent être contenues dans les documents fournis ou reçus par le Client dans le cadre d’une opération de Syndication Bancaire telles que les nom et prénom(s) du mandataire social de l’Emprunteur.&nbsp;</li>
  <li>«&nbsp;Emprunteur&nbsp;» désigne la personne morale de droit privé ou de droit public, cliente du Client ou d’un Etablissement Autorisé ou d’un Utilisateur Invité, qui est concerné par une opération de Syndication Bancaire sur le Marché Primaire ou sur le Marché secondaire réalisée via la Plateforme CALS.</li>
  <li>«&nbsp;Etablissement Autorisé&nbsp;» désigne un établissementde crédit au sens des articles L.&nbsp;511-9 et suivants du code monétaire et financier ou un fonds d’investissement tel que défini aux articles R. 214-202 et suivants du code monétaire et financier, qui détient un compte utilisateur sur la Plateforme CALS et qui est habilité – par le biais de cette dernière - à (i)&nbsp;proposer une Opération à un Pool Bancaire en qualité d’Arrangeur&nbsp;; (ii)&nbsp;faire partie d’un Pool Bancaire pour financer une Opération&nbsp;; et/ou (iii)&nbsp;céder une convention de prêt à un(des) autre(s) Etablissement(s) Autorisé(s). Le Client est un Etablissement Autorisé.</li>
  <li>«&nbsp;Force Majeure&nbsp;» désigne un évènement extérieur aux Parties, imprévisible et irrésistible, tel que défini par la jurisprudence des tribunaux française, en ce compris&nbsp;: guerre (déclarée ou non)&nbsp;; acte terroriste&nbsp;; invasion&nbsp;; rébellion&nbsp;; blocus&nbsp;; sabotage ou acte de vandalisme&nbsp;; grève ou conflit social, total ou partiel, externe à chacune des Parties&nbsp;; intempérie (notamment inondations, orages et tempêtes)&nbsp;; évènement déclaré «&nbsp;catastrophe naturelle&nbsp;»&nbsp;; incendie&nbsp;; épidémie&nbsp;; blocage des moyens de transport ou d’approvisionnement (notamment en énergie)&nbsp;; défaillance dans la fourniture de l’énergie électrique, du chauffage, de l’air conditionné, des réseaux de télécommunications, du transport des données&nbsp;; défaillance de satellites.</li>
  <li>«&nbsp;Information Confidentielle&nbsp;» désigne toute information communiquée (que ce soit par écrit, oralement ou par un autre moyen et que ce soit directement ou indirectement) par une Partie à l’autre Partie avant ou après la date d’entrée en vigueur du Contrat, y compris, sans limitation, les procédés, plans, savoir-faire, secrets commerciaux, inventions, techniques, opportunités commerciales et activités de l’une des Parties.</li>
  <li>«&nbsp;KYB&nbsp;» désigne les renseignements qu’un Etablissement Autorisé ou un Utilisateur Invité détient sur la situation financière d’un Emprunteur, traités via la Plateforme CALS, qui permettent aux Etablissements Autorisés et/ou aux Utilisateurs Invités de respecter leurs obligations légales, réglementaires et prudentielles de lutte contre le blanchiment de capitaux et de financement du terrorisme.</li>
  <li>«&nbsp;KYC&nbsp;» désigne les documents relatifs à un Emprunteur et/ou à ses représentants, traités via la Plateforme CALS qui permettent aux Etablissements Autorisés et/ou aux Utilisateurs Invités de respecter leurs obligations légales, réglementaires et prudentielles de lutte contre le blanchiment de capitaux et de financement du terrorisme. Les KYC sont susceptibles de contenir des données à caractère personnel au sens de l’article 4 du RGPD.</li>
  <li>«&nbsp;Marché Primaire&nbsp;» désigne le fait pour un Arrangeur de trouver un Pool Bancaire qui accepte de financer l’Opération. Dans le cadre du Marché Primaire, le risque de crédit est partagé dès l’origine entre les Etablissements Autorisés et/ou les Utilisateurs Invités composant le Pool Bancaire.</li>
  <li>«&nbsp;Marché Secondaire&nbsp;» désigne le fait pour un Etablissement Autorisé ou un Utilisateur Invité qui a conclu une convention de prêt bilatérale ou qui est partie à une convention de prêt syndiquée de transférer ultérieurement le risque et/ou la trésorerie, via la Plateforme CALS, de tout ou partie dudit crédit à un ou plusieurs autres Etablissements Autorisés et/ou Utilisateurs Invités.</li>
  <li>«&nbsp;Opération&nbsp;» désigne le crédit bancaire que désire obtenir l’Emprunteur grâce à une opération de syndication de crédit sur le Marché Primaire.</li>
  <li>«&nbsp;Période Initiale&nbsp;» désigne la première période contractuelle d’une durée d’un (1) an.</li>
  <li>« Plateforme CALS » désigne la solution logicielle éditée par CALS, laquelle permet de mettre en relation des Etablissements Autorisés afin qu’ils réalisent entre eux des opérations de Syndication Bancaires sur le Marché Primaire ainsi que sur le Marché Secondaire.</li>
  <li>«&nbsp;Pool Bancaire&nbsp;» désigne l’association de plusieurs Etablissements Autorisés et/ou des Utilisateurs Invités réunis dans un syndicat financier dépourvu de la personnalité juridique pour financer l’Opération.</li>
  <li>«&nbsp;Redevance&nbsp;» désigne les sommes dues par le Client à CALS en contrepartie de l’utilisation de la Plateforme CALS. Ces sommes sont précisées aux Conditions Particulières.</li>
  <li>«&nbsp;Responsable&nbsp;» désigne un Utilisateur Autorisé – salarié du Client – qui est habilité par le Client à gérer les habilitations d’accès à la Plateforme CALS et à communiquer les identifiant et mot de passe du Client aux Utilisateurs Autorisés.</li>
  <li>«&nbsp;Services&nbsp;» désigne l’ensemble des prestations fournies par CALS au Client aux termes du Contrat.</li>
  <li>«&nbsp;Syndication Bancaire&nbsp;» désigne la réunion de deux ou plusieurs Etablissements Autorisés et/ou Utilisateurs Invités, ayant pour objet la répartition de la charge d’un crédit octroyé à un Emprunteur.</li>
  <li>«&nbsp;Utilisateur Autorisé&nbsp;» désigne l’utilisateur identifié, personne physique, habilité par le Client à accéder à la Plateforme CALS et à utiliser les Services conformément aux stipulations des Conditions Générales. Un Utilisateur Autorisé peut avoir le statut de Responsable, d’Utilisateur Salarié ou d’Utilisateur Invité.</li>
  <li>«&nbsp;Utilisateur Invité&nbsp;» désigne un Utilisateur Autorisé, salarié d’un établissement de crédit ou d’un fonds d’investissement qui ne dispose pas d’un compte utilisateur sur la Plateforme CALS et qui est invité par un Etablissement Autorisé à participer à une opération de Syndication Bancaire réalisée via la Plateforme CALS. L’Utilisateur Invité s’engage expressément à être habilité par l’établissement de crédit ou le fonds d’investissement dans lequel il est salarié à réaliser des opérations de Syndication Bancaire en son nom et pour son compte.&nbsp;&nbsp;&nbsp;</li>
  <li>«&nbsp;Utilisateur Salarié&nbsp;» désigne l’utilisateur identifié, personne physique, salarié par le Client et habilité par le Responsable à accéder à la Plateforme CALS.</li>
</ul>
<h2>Article 2 Objet</h2>
<p>Les Conditions Générales définissent les conditions de mise disposition de la Plateforme CALS et les conditions de son utilisation par le Client et les Utilisateurs Invités.</p>
<h2>Article 3 Entrée en vigueur – Durée</h2>
<p>Le Contrat prend effet à compter de la signature du Contrat par l’ensemble des Parties, pour la Période Initiale.</p>
<p>A l’expiration de la Période Initiale, sauf en cas (i)&nbsp;de résiliation anticipée dans les conditions définies à l’Article 14 – «&nbsp;Résiliation&nbsp;»&nbsp;; ou (ii)&nbsp;dénonciation par l’une ou l’autre des Parties, par lettre recommandée avec demande d’avis de réception, adressée au minimum quatre-vingt-dix (90) jours avant l’expiration de la période contractuelle en cours, le Contrat sera tacitement renouvelé pour une nouvelle période contractuelle de même durée que la Période Initiale.</p>
<h2>Article 4 Description des Services</h2>
<h3>4.1 Conditions préalables</h3>
<p>Afin de pouvoir utiliser la Plateforme CALS, le Client s’engage à être titulaire de l’ensemble des autorisations et agréments nécessaires à la réalisation d’opérations de Syndication Bancaire.</p>
<p>Le Client reconnaît et accepte que les opérations de Syndication Bancaire réalisées via la Plateforme CALS doivent concerner uniquement les crédits bancaires qui ont été consentis à des personnes morales.</p>
<p>Les opérations de Syndication Bancaire réalisées via la Plateforme CALS ne peuvent à aucun moment porter sur un crédit bancaire octroyé à une personne physique.</p>
<p>Le Client se porte fort du respect de ces obligations par ses Utilisateurs Invités.</p>
<h3>4.2 Syndication Bancaire sur le Marché Primaire</h3>
<h4>4.2.1 Appel d’offre</h4>
<p>Lorsque qu’un Etablissement Autorisé est désigné par un Emprunteur pour mener à bien une Opération, il doit - via son compte utilisateur - créer un appel d’offre de crédit en cliquant sur le lien «&nbsp;Arrangement de dette&nbsp;».</p>
<p>L’appel d’offre de crédit doit mentionner&nbsp;:</p>
<ol>
  <li>l’identité de l’Emprunteur&nbsp;ainsi que son secteur d’activité&nbsp;;</li>
  <li>la durée de validité de l’offre&nbsp;;</li>
  <li>une description du projet envisagé&nbsp;;</li>
  <li>le montant du crédit souhaité et les modalités de remboursement y afférentes.</li>
</ol>
<p>L’Arrangeur a la possibilité de télécharger les KYC et les KYB qu’il a collectés sur l’Emprunteur et de les communiquer aux Etablissements Autorisés et/ou aux Utilisateurs Invités, destinataires de l’appel d’offre de crédit.</p>
<p>L’Etablissement Autorisé, qui agit en qualité d’Arrangeur, peut choisir de communiquer l’appel d’offre de crédit ainsi complété à l’ensemble des Etablissements Autorisés, sélectionner les Etablissements Autorisés auxquels il souhaite transmettre l’appel d’offre de crédit et/ou inviter un ou des Utilisateurs Invités.</p>
<h4>4.2.2 Offres de crédit</h4>
<h4>Chacun des Etablissements Autorisés et/ou des Utilisateurs Invités destinataires de l’appel d’offre de crédit peut émettre une offre de crédit afin de participer au financement de l’Opération.</h4>
<p>Pour ce faire, l’Etablissement Autorisé ou l’Utilisateur Invité concerné doit répondre à l’Appel d’Offre via son compte utilisateur en précisant&nbsp;:</p>
<ol>
  <li>le montant du prêt qu’il souhaite consentir&nbsp;;</li>
  <li>la durée dudit prêt&nbsp;;</li>
  <li>les conditions financières y afférentes (p. ex. taux d’intérêt, marge, commission bancaire).</li>
</ol>
<p>L’offre de crédit ainsi validée sera communiquée à l’Arrangeur, sur son compte utilisateur.</p>
<p>L’Arrangeur sélectionnera les différentes offres de crédit émises par les différents Etablissements Autorisés et/ou les Utilisateurs Invités.</p>
<p>Les Etablissements Autorisés et/ou les Utilisateurs Invités, expéditeurs des offres de crédit retenus, constituent le Pool Bancaire.</p>
<h4>4.2.3 Rédaction des documents contractuels</h4>
<p>L’Arrangeur rédige l’ensemble des documents contractuels relatifs à l’Opération à savoir les différents accords de confidentialité entre les parties à l’Opération concernée ainsi que la Convention de Crédit.</p>
<p>L’Arrangeur doit communiquer les documents contractuels qu’il a rédigés en les téléchargeant sur la Plateforme CALS afin de permettre aux Etablissements Autorisés et/ou Utilisateurs Invités concernés de les signer.</p>
<p>L’Arrangeur est seul responsable de conformité des documents contractuels à la législation applicable. La responsabilité de CALS ne pourra en aucun cas être engagée sur ce fondement.</p>
<h4>4.2.4 Signature des documents contractuels</h4>
<p>Les accords de confidentialités et la Convention de Crédit sont signés par l’Arrangeur et les différents membres du Pool Bancaire sur la Plateforme CALS via DocuSign, prestataire de signature électronique.</p>
<p>Afin de pouvoir utiliser les services de signature électronique proposés via la Plateforme CALS, le Client doit accepter les conditions générales de DocuSign, disponibles à l’adresse URL <strong>[•]</strong>.</p>
<h4>4.2.5 Suivi de l’Opération</h4>
<p>L’Agent est responsable du suivi de l’Opération et de la bonne exécution de la Convention de Crédit.</p>
<p>Les Etablissements Autorisés et/ou les Utilisateurs Invités&nbsp;membres du Pool Bancaire pourront, via la Plateforme CALS, être assistés dans la rédaction et l’envoi des rappels et communications relatifs à la Convention de Crédit aux parties dudit contrat.</p>
<p>L’Arrangeur, les Utilisateurs Invités et les Etablissements Autorisés membres du Pool Bancaire pourront suivre l’évolution de l’Opération via leur compte utilisateur.</p>
<h3>4.3 Syndication Bancaire sur le Marché Secondaire</h3>
<h4>4.3.1Appel d’offre de rachat</h4>
<p>L’Etablissement Autorisé qui a conclu une convention de prêt bilatérale ou syndiquée peut transférer tout ou partie du risque de ce crédit à un ou plusieurs autres Etablissements Autorisés et/ou des Utilisateurs Invités.</p>
<p>Pour ce faire, l’Etablissement Autorisé doit renseigner un appel d’offre de rachat via son compte utilisateur qui précise&nbsp;:</p>
<ol>
  <li>l’identité de l’Emprunteur&nbsp;ainsi que son secteur d’activité et l’évaluation du risque qui lui est associé&nbsp;;</li>
  <li>la durée de validité de l’offre&nbsp;de rachat&nbsp;;</li>
  <li>une description du projet concerné&nbsp;;</li>
  <li>le montant du crédit cédé et les modalités de remboursement y afférentes.</li>
</ol>
<p>L’Etablissement Autorisé cédant a la possibilité de télécharger les KYC et les KYB qu’il a collectés sur l’Emprunteur et de les communiquer aux Etablissements Autorisés et/ou Utilisateurs Invités, destinataires de l’appel d’offre de rachat.</p>
<p>L’Etablissement Autorisé concerné peut choisir de communiquer l’appel d’offre de rachat soit à l’ensemble des Etablissements Autorisés, soit aux seuls Etablissements Autorisés et/ou Utilisateurs Invités qu’il aura sélectionnés.</p>
<h4>4.3.2 Offre de rachat</h4>
<p>Chacun des Etablissements Autorisés et/ou Utilisateurs Autorisés destinataires de l’appel d’offre de rachat peut émettre une offre de rachat du crédit cédé par l’Etablissement Autorisé concerné.</p>
<p>Pour ce faire, les Etablissements Autorisés et/ou les Utilisateurs Invités intéressés doivent répondre à l’appel d’offre de crédit via leur compte utilisateur en précisant&nbsp;:</p>
<ol>
  <li>le montant du prêt qu’elle souhaite consentir&nbsp;;</li>
  <li>la durée dudit prêt&nbsp;;</li>
  <li>les conditions financières y afférentes (p. ex. taux d’intérêt, marge, commissions bancaires).</li>
</ol>
<p>L’offre de rachat ainsi validée sera communiquée à l’Etablissement Autorisé cédant, sur son compte utilisateur.</p>
<p>L’Etablissement Autorisé cédant sélectionnera la ou les différentes offres de rachat émis par les différents Etablissements Autorisés et/ou Utilisateurs Invités.</p>
<h4>4.3.3 Rédaction des documents contractuels</h4>
<p>L’Etablissement Autorisé cédant rédige l’ensemble des documents contractuels relatifs à l’opération de syndication à savoir les différents accords de confidentialité entre les parties à ladite opération ainsi que la Convention de Sous-Participation.</p>
<p>L’Etablissement Autorisé cédant doit communiquer les documents contractuels qu’il a rédigés en les téléchargeant sur la Plateforme CALS afin de permettre aux Etablissements Autorisés et/ou aux Utilisateurs Invités cessionnaires de les signer.</p>
<p>L’Etablissement Autorisé cédant est seul responsable de conformité des documents contractuels à la législation applicable. La responsabilité de CALS ne pourra en aucun cas être engagée sur ce fondement.</p>
<h4>4.3.4 Signature des documents contractuels</h4>
<p>Les accords de confidentialités ainsi que la Convention de Sous-Participation sont signés par l’Etablissement Autorisé cédant et le ou les différents Etablissements Autorisés cessionnaires via DocuSign, prestataire de signature électronique.</p>
<p>Suivi de la Convention de Sous-ParticipationLes Etablissements Autorisés et/ou les Utilisateurs Invités cessionnaires pourront, via la Plateforme CALS, être assistés dans la rédaction et l’envoi des rappels et communications relatifs aux parties à la Convention de Sous-Participation.</p>
<p>Les Etablissements Autorisés et/ou les Utilisateurs Invités membres à ladite opération de Syndication Bancaire pourront suivre son évolution via leur compte utilisateur.</p>
<h3>4.4 Collecte des KYC et des KYB</h3>
<p>Dans le cadre de la réalisation d’une opération de Syndication Bancaire, les Etablissements Autorisés et/ou Utilisateurs Invités parties à ladite opération ont l’obligation de connaître l’Emprunteur conformément aux dispositions législatives, règlementaires et déontologiques relatives à la lutte contre le blanchiment de capitaux et le financement du terrorisme, y compris les articles L. 561-1 et suivants et R. 561-1 et suivants du code monétaire et financier.</p>
<p>Pour ce faire, l’Arrangeur ou l’Etablissement Autorisé cédant une convention de prêt s’engage à collecter les KYC et les KYB nécessaires et, le cas échéant, à les communiquer aux parties à l’opération de Syndication Bancaire concernées. CALS ne pourra en aucun cas voir sa responsabilité engagée si les Etablissements Autorisés ne respectent pas leurs obligations liées à la collecte des KYC et des KYB.</p>
<p>L’Arrangeur et l’Etablissement Autorisé cédant une convention de prêt peuvent télécharger les KYC et les KYB sur la Plateforme CALS.</p>
<h2>Article 5 Utilisation de la Plateforme CALS</h2>
<h3>5.1 Abonnement à la Plateforme CALS</h3>
<p>Le Client souscrit un abonnement personnel auprès de CALS, afin d’utiliser la Plateforme CALS pour ses besoins professionnels dans les conditions et limites spécifiées aux Conditions Générales. Ce droit d’utilisation est concédé à titre non exclusif, non transférable et non cessible, pour le Monde entier et pour la durée des Conditions Générales.</p>
<p>Ce même droit d’utilisation est octroyé à chacun des Utilisateurs Autorisés dans le cadre de leur utilisation professionnelle de la Plateforme CALS.</p>
<p>Le droit d’utilisation de la Plateforme CALS concédé à un Utilisateur Invité par un Etablissement Autorisé concerne uniquement l’opération de Syndication Bancaire à laquelle il a été invité. Pour pouvoir accéder à l’intégralité des fonctionnalités de la Plateforme CALS, l’Utilisateur Invité devra souscrire un abonnement à la Plateforme CALS.</p>
<p>Dans le cadre de son droit d’usage de la Plateforme CALS, le Client s’engage sans réserve à ne pas&nbsp;:</p>
<ol>
  <li>effectuer une copie de la Plateforme CALS ou d’éléments de la Plateforme CALS, de quelque façon que ce soit&nbsp;;</li>
  <li>analyser, ou faire analyser par un tiers, au sens d’observer, étudier et tester, le fonctionnement de la Plateforme CALS en vue de déterminer les idées et principes sur lesquels les éléments du programme se basent lorsque la Plateforme CALS exécute les opérations de chargement, d’affichage, d’exécution, de transmission ou de stockage&nbsp;;</li>
  <li>décompiler, désassembler la Plateforme CALS, pratiquer l’ingénierie inverse de créer des œuvres dérivées à partir de la Plateforme CALS ou tenter de découvrir ou reconstituer le code source, les idées qui en sont la base, les algorithmes, les formats des fichiers ou les interfaces de programmation ou d’interopérabilité de la Plateforme CALS sauf dans la limite du droit accordé par l’article&nbsp;L.&nbsp;122-6-1 du Code de la propriété intellectuelle, de quelque manière que ce soit. Au cas où le Client souhaiterait obtenir les informations permettant de mettre en œuvre l’interopérabilité de la Plateforme CALS avec un autre logiciel, le Client s’engage à demander ces informations à CALS, qui pourra fournir les informations nécessaires au Client, sous réserve du paiement par ce dernier des coûts associés&nbsp;;</li>
  <li>modifier, améliorer, traduire la Plateforme CALS, y compris pour corriger les bugs et les erreurs, CALS se réservant exclusivement ce droit conformément à l’article L.&nbsp;122-6-1 I 2° du Code de la propriété intellectuelle&nbsp;;</li>
  <li>fournir à des tiers des prestations, à titre gratuit ou onéreux, qui soient basées sur la Plateforme CALS. En particulier, le Client s’interdit d’intégrer, traiter et/ou utiliser les données d’un tiers&nbsp;; et/ou octroyer un accès, total ou partiel, à la Plateforme CALS, notamment sous forme de service bureau, en ASP, en PaaS ou en SaaS&nbsp;;</li>
  <li>transférer, louer, sous-licencier, céder, nantir, ou transférer tout ou partie de la propriété de la Plateforme CALS de quelque manière que ce soit.</li>
</ol>
<p>La Plateforme CALS peut intégrer des logiciels tiers qui seront utilisés par le Client uniquement en relation avec la Plateforme CALS et ne seront jamais utilisés d’une quelconque autre manière sans l’accord préalable et écrit de CALS.</p>
<p>Le Client se porte fort du respect des stipulations du présent Article 5.1 par les Utilisateurs Autorisés.</p>
<h3>5.2 Accès à la Plateforme CALS – Utilisateurs Autorisés</h3>
<p>L’accès à la Plateforme CALS est limité au Client et le cas échéant, au Responsable et aux seuls Utilisateurs Autorisés auxquels le Responsable aura communiqué les identifiant et mot de passe pour accéder à la Plateforme CALS.</p>
<p>Le Client s’engage à communiquer ses identifiant et mot de passe uniquement&nbsp;:</p>
<ol>
  <li>s’agissant des Responsable et Utilisateurs Salariés, à son seul personnel compétent et habilité à réaliser des opérations de Syndication Bancaire&nbsp;;</li>
  <li>s’agissant des Utilisateurs Invités, uniquement à des personnes salariées d’établissements de crédit ou de fonds d’investissement disposant de la capacité de participer à des opérations de Syndication Bancaire en leur nom et pour leur compte. &nbsp;</li>
</ol>
<p>A réception de l’invitation émise par un Etablissement Autorisé, l’Utilisateur Invité doit accepter les Conditions Générales pour accéder à la Plateforme CALS.</p>
<p>Il s’engage à être pleinement habilité par l’établissement de crédit ou le fonds d’investissement qui l’emploie à réaliser des opérations de Syndication Bancaire en son nom et pour son compte.</p>
<p>La responsabilité de CALS ne pourra en aucun cas être engagée si une opération de Syndication Bancaire a été réalisée par une personne ne disposant pas des compétences et pouvoirs nécessaires pour ce faire.</p>
<p>Le Client est seul responsable de la sécurité des login et mot de passe. Le Client s’engage à informer promptement CALS de tout accès non autorisé, qu’il soit effectif ou supposé, au login, au mot de passe et/ou à la Plateforme CALS.</p>
<p>Toute action réalisée via le login appartenant au Client sera réputée come ayant été réalisée par le Client, sauf à ce qu’elle ait préalablement déclaré le login concerné comme ayant été perdu ou volé, allouant ainsi un délai raisonnable à CALS pour désactiver ledit login.</p>
<p>Dans ce cadre, le Client se porte fort du respect des termes des Conditions Générales par le Responsable et chacun des Utilisateurs Autorisés.</p>
<h3>5.3 Contenus</h3>
<p>Le Client garantit qu’il est pleinement titulaire de tous les droits et autorisations relatifs aux Contenus qu’il communique à un autre Etablissement Autorisé via la Plateforme CALS et fournit à CALS.</p>
<p>Le Client s’engage à détenir toutes les autorisations requises pour transmettre le contenu à un tiers ou à CALS.</p>
<p>A ce titre, le Client garantit et relève CALS de tout dommage, condamnation, frais ou coût relatif à toute demande, action et/ou réclamation formulée à l’encontre de CALS et fondée sur l’atteinte par le Client à un quelconque droit d’un tiers.</p>
<p>CALS est autorisée à faire usage de l’ensemble des Contenus, lorsque cela est nécessaire à l’exécution de ses obligations au titre du Contrat. Dans ce cadre, CALS s’engage à respecter toute ligne directrice qui lui serait communiquée préalablement à son utilisation des Contenus.</p>
<p>De la même façon, le Client garantit que les Contenus ne revêtent aucun caractère illicite, menaçant, humiliant, diffamatoire, obscène, haineux, pornographique ou blasphématoire, ou tout autre message qui pourrait constituer un crime ou un délit, engager la responsabilité civile, porter atteinte à la législation ou inciter à le faire, ou encore des contenus qui pourraient être utilisés à toute fin contraire à la loi ou au Contrat.</p>
<h2>Article 6 Conditions financières</h2>
<h3>6.1 Paiement des Services</h3>
<p>La Redevance que le Client s’engage à verser à CALS est définie aux Conditions Particulières.</p>
<p>Les prestations et/ou interventions supplémentaires réalisées par CALS dans les conditions définies au Contrat seront facturées par CALS, au taux horaire en vigueur au moment de la réalisation de la prestation ou intervention en cause.</p>
<h3>6.2 Modalités de paiement</h3>
<p>La périodicité de facturation et les délais de paiement sont visés au(x) Conditions Particulières correspondante(s).</p>
<p>En cas de non-paiement de toute somme dans les délais contractuels&nbsp;:</p>
<ol>
  <li>toute somme impayée portera automatiquement intérêt au jour le jour jusqu’à la date de son paiement intégral en principal, intérêts, frais et accessoires, à un taux égal à trois (3) fois le taux d’intérêt légal en vigueur, et ce, sans aucune formalité préalable, et sans préjudice des dommages-intérêts que CALS se réserve le droit de solliciter de manière judiciaire&nbsp;;</li>
  <li>CALS se réserve le droit, à sa seule discrétion avec ou sans préavis, de suspendre l’exécution de tout ou partie des Services en cours ou future, et ce jusqu’à complet paiement des sommes dues&nbsp;;</li>
  <li>tous les frais engagés par CALS pour le recouvrement des sommes dues seront à la charge du Client, en ce compris les frais d’huissier, frais de justice et honoraires d’avocat, lesdits frais ne pouvant en tout état de cause être inférieurs à l’indemnité forfaitaire visée par l’article L. 441-6 I 12e du Code de commerce, d’un montant de quarante (40) euros&nbsp;;</li>
  <li>toutes les sommes restant dues à CALS par le Client au titre du Contrat deviennent immédiatement exigibles.</li>
</ol>
<p>Les sommes versées par le Client à CALS dans le cadre du Contrat restent acquises à CALS et ne sont donc pas remboursables, et ce, même en cas de résiliation du Contrat ou de tout autre contrat conclu entre CALS et le Client.</p>
<ul>
  <li><strong>Révision tarifaire</strong></li>
</ul>
<p>À l’issue de chaque période contractuelle, la Redevance pourra être réévaluée, selon la formule suivante&nbsp;:</p>
<p>&nbsp;</p>
<p>Dans laquelle&nbsp;:</p>
<p>S = Dernier indice SYNTEC publié à la date de révision,<br>
So = Indice SYNTEC douze mois avant S,<br>
Po = Montant de la Redevance pour la période contractuelle précédente,<br>
P = Montant révisé de la Redevance.</p>
<h2>Article 7 Engagements de CALS</h2>
<h3>7.1 Conformité des Services</h3>
<p>Les Services que le Client souhaite voir réalisés seront fournis selon les termes des Conditions Générales par CALS qui s’engage, sauf stipulations expresses contraires, au titre d’une obligation de moyen.</p>
<p>CALS s’engage à ce que tous ses personnels mettent tout leur savoir-faire et leurs connaissances au service de la bonne exécution des Services. En cas de difficultés dans la fourniture des Services, CALS s’oblige à en informer aussitôt le Client.</p>
<p>CALS se réserve le droit de modifier à tout moment les caractéristiques de ses infrastructures techniques, le choix de ses fournisseurs techniques et la composition de ses équipes.</p>
<h3>7.2 Obligations en matières fiscale et sociale</h3>
<p>CALS déclare, en tant que de besoin, être immatriculée auprès du Registre du Commerce et des Sociétés, auprès des URSSAF et/ou auprès de toutes administrations ou organismes (en ce compris les administrations ou organismes d’assurance sociale) requis pour l’exécution du Contrat. Les immatriculations faites conformément à cet article, ainsi que les immatriculations effectuées préalablement à la conclusion du Contrat doivent couvrir expressément toutes les activités du Client pour l’exécution des prestations en application du Contrat. Conformément aux dispositions des articles L.&nbsp;8221-1 et suivants et D.&nbsp;8222-5 du Code du travail, CALS s’engage à remettre au Client tout document justificatif relatif à son immatriculation, le paiement de ses cotisations sociales et fiscales ainsi que l’emploi de ses préposés.</p>
<h3>7.3 Mises à jour</h3>
<p>Pendant toute la durée du Contrat, le Client bénéficie des mises à jour de la plateforme CALS qui sont développées et diffusées par CALS, à l’exclusion de toute nouvelle version (c.-à-d. évolution majeure) de la Plateforme CALS.</p>
<p>Le Client accepte, en conséquence, que CALS puisse, sans préavis et à tout moment, modifier une ou plusieurs fonctionnalités de la plateforme CALS.</p>
<h2>Article 8 Engagements du Client</h2>
<p>Le Client s’engage à&nbsp;:</p>
<ol>
  <li>disposer des agréments et autorisations nécessaires pour réaliser des opérations de Syndication Bancaire&nbsp;;</li>
  <li>respecter l’ensemble des dispositions législatives, règlementaires et déontologiques relatives à la lutte contre le blanchiment de capitaux et le financement du terrorisme. Le Client se porte fort du respect de l’ensemble de ces dispositions par les Utilisateurs Autorisés&nbsp;;</li>
  <li>se conformer aux codes de bonne conduite applicables à la Syndication Bancaire&nbsp;sur le Marché Primaire et sur le Marché Secondaire&nbsp;;</li>
  <li>ne pas transmettre par le biais de la Plateforme CALS des contenus à caractère illicite, menaçant, humiliant, diffamatoire, obscène, haineux, pornographique ou blasphématoire, ou tout autre message qui pourrait constituer un crime ou un délit, engager la responsabilité civile, porter atteinte à la législation ou inciter au faire, ou encore des contenus qui pourraient être utilisés à toute fin contraire à la loi ou aux présentes Conditions Générales&nbsp;;</li>
  <li>ne pas réaliser par le biais de la Plateforme CALS des opérations illicites qui pourraient constituer un crime ou un délit, engager la responsabilité civile, porter atteinte à la législation ou inciter au faire, ou encore des contenus qui pourraient être utilisés à toute fin contraire à la loi ou aux présentes Conditions Générales&nbsp;;</li>
  <li>respecter scrupuleusement l’ensemble des obligations et dispositions législatives applicables aux établissements de crédit&nbsp;;</li>
  <li>coopérer en toute bonne foi pour faciliter les interventions de CALS, notamment en lui communiquant toutes les informations pertinentes ou demandées dans un délai permettant à CALS de remplir ses obligations&nbsp;;</li>
  <li>assister CALS dans le cadre de la fourniture des Services, par le biais de ses personnels qualifiés et compétents&nbsp;;</li>
  <li>ne pas utiliser la Plateforme CALS de manière à ce que, du point de vue de CALS, les performances ou les fonctionnalités de la Plateforme CALS, ou de tout autre système informatique ou réseau utilisé par CALS ou par un quelconque tiers, soient impactés négativement ou que les utilisateurs de la Plateforme CALS soient négativement affectés&nbsp;;</li>
  <li>de charger ou transmettre sur la Plateforme CALS ou utiliser tout équipement, logiciel ou routine qui contienne des virus, chevaux de Troie, vers, bombes à retardement ou autres programmes et procédés destinés à endommager, interférer ou tenter d’interférer avec le fonctionnement normal de la Plateforme CALS, ou s’approprier la Plateforme CALS, ou encore recourir à n’importe quel moyen pour causer une saturation de nos systèmes ou porter atteinte aux droits de tiers&nbsp;;</li>
  <li>disposer d’un navigateur Internet à jour ainsi que d’une connexion Internet haut débit dont les frais restent à sa charge.</li>
</ol>
<p>CALS ne pourra voir sa responsabilité engagée par un manquement par le Client ou les Utilisateurs Autorisés à la législation applicable.</p>
<h2>Article 9 Propriété intellectuelle</h2>
<h3>9.1 Plateforme CALS</h3>
<p>Le Client reconnaît que la Plateforme CALS, en ce compris tous correctifs, solutions de contournement, mises à jour, mises à niveau, améliorations et modifications mis à la disposition du Client, ainsi que tous les secrets commerciaux, droits d’auteur, brevets, marques, noms commerciaux et autre droits de propriété intellectuelle y afférents restent à tout moment la propriété entière et exclusive de CALS et qu’aucune des stipulations du Contrat ne saurait être interprétée comme un quelconque transfert de l’un de ces droits au profit du Client.</p>
<h3>9.2 Savoir-faire</h3>
<p>Toute idée, savoir-faire ou technique qui a pu être développé par CALS sont la propriété exclusive de CALS. CALS peut, à sa seule discrétion, développer, utiliser, commercialiser et licencier tout élément similaire ou en relation avec les développements réalisés par CALS pour le Client. CALS n’a aucune obligation de révéler toute idée, savoir-faire ou technique qui a pu être développé par CALS et que CALS considère comme étant confidentiel et étant sa propriété.</p>
<p>Toutefois, CALS reconnaît le savoir-faire des Arrangeurs en matière d’opérations de Syndication Bancaire et ne considère pas que cette compétence lui appartienne.</p>
<ol>
  <li><strong>Disponibilité des Services</strong></li>
</ol>
<p>Les services proposés par CALS sont accessibles à distance, par le réseau Internet.</p>
<p>Le Client fait son affaire personnelle de la mise en place des moyens informatiques et de télécommunications permettant l’accès à la Plateforme CALS. Ils conservent à leur charge les frais de télécommunication lors de l’accès à Internet lors de l’utilisation de la Plateforme CALS.</p>
<p>Le Client reconnaît expressément qu’il est averti des aléas techniques qui peuvent affecter le réseau Internet et entraîner des ralentissements ou des indisponibilités rendant la connexion impossible. CALS ne peut être tenue responsable des difficultés d’accès aux services dus à des perturbations du réseau Internet.</p>
<p>CALS se réserve le droit, sans préavis ni indemnité, de suspendre temporairement l’accès à la Plateforme CALS lors de la survenance de pannes éventuelles ou de toute opération de maintenance nécessaire à son bon fonctionnement.</p>
<p>CALS peut apporter à la Plateforme CALS toutes les modifications et améliorations qu’elle jugera nécessaires.</p>
<h2>Article 11&nbsp;Protection des données à caractère personnel</h2>
<h3>11.1 Traitement des Données Client</h3>
<p>Le Client, dans le cadre de ses activités de Syndication Bancaire, met en œuvre des traitements automatisés de données à caractère personnel au sens de la loi n° 78-17 du 6 janvier 1978 relative à l’informatique, aux fichiers et aux libertés et du RGPD. Il a souhaité confier certains aspects techniques de ces traitements à CALS dans les conditions définies au Contrat.</p>
<p>CALS traite les Données Clients pour le compte du Client afin de lui fournir les Services.</p>
<h4>11.1.1 Engagements du Client</h4>
<p>Le Client s’engage dans le cadre de l’exécution du Contrat à&nbsp;:</p>
<ol>
  <li>n’intégrer dans les Données Clients que des informations strictement nécessaires à la bonne exécution des Services par CALS&nbsp;;</li>
  <li>documenter par écrit toute instruction concernant le traitement des Données Clients par CALS&nbsp;;</li>
  <li>se conformer aux dispositions de la Loi n° 78-17, du RGPD, de la LCENet plus généralement à la réglementation applicable en France&nbsp;;</li>
  <li>superviser le traitement des Données Clients, y compris en réalisant des audits selon les modalités préalablement définies avec CALS.</li>
</ol>
<p>CALS ne pourra voir sa responsabilité engagée pour un manquement par le Client à la législation applicable sauf lorsque la loi prescrit expressément le contraire.</p>
<p>Il appartient au Client de fournir toutes informations pertinentes aux personnes concernées par les opérations de traitement au moment de la collecte des données et de s’assurer que le traitement mis en œuvre repose sur une base légale.</p>
<h4>11.1.2 Engagements de CALS</h4>
<p>Conformément aux articles 28 et 32 du RGPD, CALS s’engage à&nbsp;:</p>
<ol>
  <li>prendre et à maintenir toutes mesures utiles, et notamment les mesures techniques et d’organisation appropriées, pour préserver la sécurité et la confidentialité des données personnelles qui lui sont confiées par le Client pour la fourniture des Services, afin d’empêcher qu’elles ne soient déformées, altérées, endommagées, diffusées ou que des personnes non autorisées y aient accès&nbsp;;</li>
  <li>veiller à ce que les personnes autorisées à traiter les données à caractère personnel pour son compte, en plus d’avoir reçu la formation nécessaire en matière de protection des données à caractère personnel, respectent la confidentialité ou soient soumises à une obligation légale appropriée de confidentialité&nbsp;;</li>
  <li>respecter les dispositions légales applicables et relatives aux conditions de traitement et/ou à la destination des données qui lui ont été communiquées par le Client ou auxquelles il aura accès dans le cadre de la fourniture des Services&nbsp;;</li>
  <li>n’agir que sur la seule instruction documentée du Client pour la réalisation du traitement des données personnelles concernées&nbsp;;</li>
  <li>exploiter les informations nominatives collectées ou auxquelles il aura pu avoir accès pour les seuls besoins de la fourniture au Client des Services&nbsp;;</li>
  <li>ne pas exploiter pour des finalités contraires aux Conditions Générales les informations nominatives collectées ou auxquelles il aura pu avoir accès dans le cadre de l’exécution des Conditions Générales conformément aux dispositions légales applicables, et à ne les transférer qu’à un tiers indiqué ou autorisé par le Client&nbsp;;</li>
  <li>ne pas revendre ou céder de données qui ont un caractère strictement confidentiel sauf à ce que les données utilisées par CALS ne puissent permettre à aucun moment d’identifier un Utilisateur Autorisé du Client et dès lors que ces données soient utilisées afin de réaliser des statistiques anonymes&nbsp;;</li>
  <li>le Client, dans la mesure du possible, par la mise en place de mesures techniques et organisationnelles appropriées, ainsi qu’à s’acquitter de son obligation de donner suite aux demandes dont les personnes concernées le saisissent en vue d’exercer leurs droits d’accès, de rectification, d’effacement, d’opposition, de limitation et à la portabilité des données&nbsp;;</li>
  <li>aider le Client, dans la mesure du possible et compte tenu des informations qui lui ont été communiquées par ce dernier, à respecter son obligation de&nbsp;: (a)&nbsp;notifier à l’autorité de contrôle une violation de données à caractère personnel&nbsp;; (b)&nbsp;communiquer à la personne concernée une violation de données à caractère personnel&nbsp;; (c)&nbsp;réaliser une étude d’impact relative à la protection des données.</li>
</ol>
<p>CALS se réserve la possibilité de confier l’exécution de tout ou partie des prestations du Contrat à un ou des sous-traitant(s) ultérieur(s) à condition qu’ils aient été validés – préalablement et par écrit – par le Client et dans ce cadre à leur faire souscrire des engagements équivalents aux stipulations du présent article 11.1.</p>
<p>Le Client autorise expressément CALS à faire appel&nbsp;:</p>
<ol>
  <li>à la société DocuSign – prestataire de signature électronique – afin de mettre en œuvre la signature électronique des Contrats de Prêt, des Conventions de Sous-Participation et les accords de confidentialité par les Utilisateurs Autorisés&nbsp;;</li>
  <li>à la société Amazon Web Services afin d’héberger la Plateforme CALS.</li>
  <li><strong>Traitement de données par</strong><strong>CALS</strong></li>
</ol>
<p>Par ailleurs, CALS, dans ses relations avec le Client, est amenée à traiter, pour son propre compte, des données à caractère personnel de préposés, dirigeants, sous-traitants, Utilisateurs invités, agents et/ou prestataires du Client.</p>
<p>Dans ce cadre, les personnels et Utilisateurs Invités du Client bénéficient d’un droit d’accès et, le cas échéant, de rectification, de suppression ou de portabilité des données les concernant. Ils disposent, aussi, du droit de définir des directives relatives au sort de leurs données à caractère personnel après leur mort.</p>
<p>Par ailleurs, les personnels et Utilisateurs Invités du Client pourront s’opposer pour des raisons légitimes au traitement des données personnelles les concernant ou encore, le limiter.</p>
<p>L’exercice de ces droits s’effectue à tout moment en écrivant à CALS par email à l’adresse <strong>[•]</strong>.</p>
<p>En sus, les personnels et Utilisateurs Invités du Client disposent de la possibilité d’introduire une réclamation auprès d''une autorité de contrôle.</p>
<p>Le Client s’engage à informer ses préposés, dirigeants, sous-traitants, agents, Utilisateurs Invités et/ou prestataires desdits droits et de leur communiquer l’ensemble des informations imposées par les articles 13 et 14 du RGPD.</p>
<h2>Article 12 Garantie</h2>
<p>CALS n’accorde aucune garantie qui ne soit expressément visée au Contrat.</p>
<p>CALS déclare être titulaire de l’ensemble des droits de propriété intellectuelle relatifs à la Plateforme CALS et que la Plateforme CALS ne constitue pas une contrefaçon d’une œuvre préexistante.</p>
<p>En conséquence, CALS garantit le Client contre toute action, réclamation, revendication ou opposition de la part de toute personne invoquant un droit de propriété intellectuelle ou un acte de concurrence déloyale et/ou parasitaire en France, sous réserve que CALS soit notifiée par le Client d’une telle action.</p>
<p>CALS sera seule autorisée à avoir le contrôle de toute défense et/ou de toute transaction dans le cadre d’une telle action. À ce titre, CALS s’engage à intervenir dans toutes les procédures et/ou les actions qui seraient initiées à l’encontre du Client sur fondement d’une violation d’un droit de propriété intellectuelle par la Plateforme CALSet/ou d’un acte de concurrence déloyale et/ou parasitisme commis par CALS en relation avec la Plateforme CALS. Le Client s’engage à fournir à CALS toute information ou assistance raisonnable dans le cadre de cette défense.</p>
<p>Dans l’hypothèse où, à l’issue de cette action ou procédure, la Plateforme CALS serait considérée, par une décision de justice insusceptible de recours, comme constituant une contrefaçon, CALS s’engage, à ses frais et sa discrétion, à&nbsp;:</p>
<ol>
  <li>obtenir pour le Client le droit de continuer à utiliser la Plateforme CALS&nbsp;; ou</li>
  <li>remplacer la Plateforme CALS par un logiciel équivalent et non contrefaisant&nbsp;; ou</li>
  <li>modifier tout ou partie de la Plateforme CALS contrefaisant de sorte qu’elle ne soit plus contrefaisante&nbsp;; ou</li>
  <li>résilier le Contrat.</li>
</ol>
<p>Cependant, CALS ne sera pas tenue d’indemniser le Client si l’action, la réclamation, la revendication ou l’opposition est due à&nbsp;:</p>
<ol>
  <li>une utilisation non-conforme, une modification ou une adaptation de la plateforme CALS par le Client&nbsp;;</li>
  <li>le défaut de mise en œuvre par le Client d’un correctif, d’une mise à jour, d’une nouvelle version et/ou de toute autre forme de correction ou d’amélioration de la Plateforme CALS&nbsp;;</li>
  <li>l’utilisation par le Client de la Plateforme CALS en combinaison avec des produits, matériels, logiciels qui ne sont pas la propriété de CALS ou qui n’ont pas été développés par CALS&nbsp;;</li>
  <li>l’utilisation, la commercialisation ou la mise à disposition de la Plateforme CALS au bénéfice d’un tiers&nbsp;;</li>
  <li>des informations, des instructions, des spécifications ou des matériels fournis par le Client ou un tiers.</li>
</ol>
<h2>Article 13 Responsabilité</h2>
<p>Il est expressément convenu entre les Parties que les stipulations du présent Article 13ont été convenues entre les Parties dans le cadre d’une négociation globale, de sorte que chacune des Parties les considère comme justifiées et proportionnées au regard de ses autres engagements aux termes du Contrat.</p>
<p>CALS se limite à fournir des services de mise en relation d’établissements bancaires ou de fonds d’investissement afin de leur permettre de réaliser des opérations de Syndication Bancaire.</p>
<p>La responsabilité de CALS ne saurait être engagée du fait du non-respect par le Client de ses obligations légales et réglementaires.</p>
<p>Le Client reconnaît que CALS n’est ni partie, ni garant de la bonne exécution de tout contrat qu’il sera amené à conclure par le biais de la Plateforme CALS avec un autre Etablissement Autorisé ou un Utilisateur Invité.</p>
<p>Le Client est seul responsable de la conclusion et de l’exécution des contrats relatifs à de la Syndication Bancaire qu’il conclut avec des autres Etablissements Autorisés et/ou Utilisateurs Invités par l’intermédiaire de la Plateforme CALS, CALS n’intervenant que pour les mettre en relation. La conclusion et l’exécution de ces contrats, qui interviennent directement entre le Client et les Etablissements Autorisés et/ou les Utilisateurs Invités s’opèrent à l’initiative et sous la responsabilité exclusive de ces derniers.</p>
<p>A ce titre CALS ne saurait assumer une quelconque responsabilité au titre du non-respect par (i) le Client de ses engagements contractuels vis-à-vis d’un Emprunteur, d’un Etablissement Autorisé ou d’un Utilisateur Invité ou (ii) un autre Etablissement Autorisé ou un Utilisateur Invité à ses obligations contractuelles.</p>
<p>CALS ne saurait assumer une quelconque responsabilité au titre des relations entre le Client et les autres Etablissements Autorisés ou les Utilisateurs Invités intervenant suite à leur mise en relation. A cet égard, le Client est réputé accepter et assumer pleinement les risques résultant de ses interactions avec d’autres Etablissements Autorisés et/ou Utilisateurs Invités ou liés à la Syndication Bancaire.</p>
<p>CALS ne saurait être tenue responsable que des dommages directs et prévisibles au sens des articles 1231-3 et 1231-4 du Code civil engendrés par un manquement de CALS à ses obligations aux termes du Contrat.</p>
<p>Il est expressément convenu entre les Parties que CALS ne saurait être responsable de tout gain manqué&nbsp;; perte de chiffre d’affaires ou de bénéfice&nbsp;; perte de clientèle&nbsp;; perte d’une chance&nbsp;; perte en termes d’images ou de renommée&nbsp;; de tout coût en vue de l’obtention d’un produit, d’un logiciel, d’un service ou d’une technologie de substitution&nbsp;; ou de toute difficulté technique dans l’acheminement d’un message via Internet.</p>
<p>La responsabilité de CALS ne pourra être recherchée en cas de préjudice résultant d’une destruction de fichiers ou de données provenant de l’utilisation par le Client d’un ou plusieurs éléments fournis dans le cadre des Services.</p>
<p>La responsabilité totale cumulée de CALS, tous dommages confondus et pour quelque raison que ce soit, ne pourra être d’un montant supérieur aux sommes effectivement perçues par CALS au titre du Contrat pendant les douze (12) mois précédant la survenance du dernier évènement dommageable.</p>
<p>En tout état de cause, le Client ne pourra mettre en jeu la responsabilité de CALS, du fait d’un manquement au titre du Contrat, que pendant un délai de douze (12) mois à compter de la survenance du manquement en cause, ce que reconnaît et accepte expressément le Client.</p>
<p>CALS ne sera en aucun cas responsable des dommages qui découleraient du non-respect par le Client de ses obligations.</p>
<h2>Article 14 Résiliation</h2>
<p>Chaque Partie pourra de plein droit, sans préjudice de tous dommages-intérêts qu’elle se réserve le droit de solliciter judiciairement, résilier le Contrat avec effet immédiat en cas de manquement par l’autre Partie à l’une de ses obligations essentielles au titre du Contrat, et notamment en cas de défaut de paiement des factures de Redevances dues par le Client à CALS, s’il n’a pas été remédié à ce manquement par la partie défaillante dans un délai de trente (30) jours ouvrables à compter de la notification de ce manquement faite par l’autre Partie, par lettre recommandée avec demande d’avis de réception.</p>
<p>En cas de cessation du Contrat, quel qu’en soit le motif, le Client devra immédiatement cesser d’utiliser tout élément fourni dans le cadre des Services.</p>
<p>En cas de résiliation pour quelque raison que ce soit, l’ensemble des Services réalisés et non encore facturés seront dus à CALS.</p>
<p>Nonobstant l’expiration ou la résiliation du Contrat, il est expressément convenu entre les Parties que les articles 6, 12, 13, 15, et 18 resteront pleinement applicables entre les Parties.</p>
<h2>Article 15 Réversibilité</h2>
<p>Dans un délai de quarante-cinq (45) jours à compter de l’expiration ou de la résiliation du Contrat, CALS s’engage à remettre au Client une copie de l’ensemble des Données Emprunteurs disponibles sur la Plateforme CALS.</p>
<p>Ces données seront mises à la disposition du Client pour leur téléchargement et/ou remises sur un support physique, au choix de CALS.</p>
<p>CALS s’engage à fournir un export complet des Données Emprunteurs sur la Plateforme CALS dans un format conforme à l’état de l’art.</p>
<p>Toute fourniture d’un export complet des Données Emprunteurs, au-delà d’un export unique postérieurement à l’expiration ou à la résiliation du Contrat, sera facturée au Client conformément au devis qui sera préalablement établi par CALS.</p>
<p>A l’issue de la période de réversibilité, CALS procèdera à l’effacement complet des Données Emprunteurs.</p>
<p>Le Client reconnaît et accepte que les Données Marché seront conservées par CALS uniquement à des fins d’amélioration de la Plateforme CALS.</p>
<h2>Article 16 Confidentialité</h2>
<h3>16.1 Notion d’Information Confidentielle</h3>
<p>Ne constituent pas des Informations Confidentielles&nbsp;:</p>
<ol>
  <li>les informations actuellement accessibles ou devenant accessibles au public sans manquement aux termes du Contrat de la part d’une Partie&nbsp;;</li>
  <li>les informations légalement détenues par une Partie avant leur divulgation par l’autre&nbsp;;</li>
  <li>les informations ne résultant ni directement ni indirectement de l’utilisation de tout ou partie des Informations Confidentielles&nbsp;;</li>
  <li>les informations valablement obtenues auprès d’un tiers autorisé à transférer ou à divulguer lesdites informations.</li>
</ol>
<h3>16.2 Engagement de Confidentialité</h3>
<p>Chaque Partie s’engage en son nom et au nom de ses préposés, agents, sous-traitants et partenaires, pendant la durée du Contrat et pendant une période de cinq (5) ans après sa cessation, à&nbsp;:</p>
<ol>
  <li>ne pas utiliser les Informations Confidentielles à des fins autres que l’exécution de ses obligations conformément au Contrat&nbsp;;</li>
  <li>prendre toute précaution qu’il utilise pour protéger ses propres informations confidentielles d’une valeur importante, étant précisé que ces précautions ne sauraient être à inférieures à celles d’un professionnel diligent&nbsp;;</li>
  <li>ne divulguer les Informations Confidentielles à quiconque, par quelque moyen que ce soit, sauf à ses préposés, agents, prestataires de service ou sous-traitants auxquels ces informations sont nécessaires pour le respect de ses obligations par chacune des Parties.</li>
</ol>
<p>Au terme du Contrat, en raison de la survenance de son terme ou de sa résiliation, chaque Partie devra sans délai remettre à l’autre Partie toutes les Informations Confidentielles, quel que soit leur support, obtenues dans le cadre du Contrat. Chaque Partie s’interdit d’en conserver copie sous quelque forme que ce soit, sauf accord exprès préalable et écrit de l’autre Partie.</p>
<h2>Article 17 Stipulations diverses</h2>
<h3>17.1 Documents contractuels</h3>
<p>Les documents contractuels sont, par ordre de priorité décroissante&nbsp;:</p>
<ol>
  <li>le corps du Contrat&nbsp;;</li>
  <li>les Conditions Particulières&nbsp;;</li>
</ol>
<p>En cas de contradiction entre différents documents, les stipulations du document de rang supérieur prévaudront.</p>
<h3>17.2 Communication – Publicité</h3>
<p>Le Client accepte de figurer parmi références-client de CALS et notamment que le Contrat puisse servir d’exemple de collaboration réciproquement fructueuse. A cette fin, CALS est autorisée à utiliser le nom et le logo du Client sur son site Internet et sur des brochures commerciales.</p>
<h3>17.3 Cession/transfert du Contrat</h3>
<p>CALS aura la possibilité de transférer tout ou partie des droits et obligations résultant pour elle du Contrat à toute filiale à constituer, ainsi que par suite notamment de fusion, scission, apport partiel d’actif ou cession totale ou partielle de son fonds de commerce.</p>
<p>Il est expressément convenu entre les Parties que toute modification dans la structure capitalistique de CALS, en ce compris un changement de contrôle, sera sans effet sur l’exécution du Contrat.</p>
<p>Le Client n’est pas autorisé à transférer tout ou partie de ses obligations aux termes du Contrat, de quelque manière que ce soit, sans l’accord préalable, écrit et exprès de CALS.</p>
<h3>17.4 Notification – Computation des délais</h3>
<p>Toute notification requise ou nécessaire en application des stipulations du Contrat devra être faite par écrit et sera réputée valablement donnée si remise en main propre ou adressée par lettre recommandée avec demande d’avis de réception à l’adresse de l’autre Partie figurant sur les Conditions Particulières ou à toute autre adresse notifiée à l’autre Partie dans les formes définies au présent article 17.4.</p>
<p>Sauf disposition particulière dans un article du Contrat, les délais sont calculés par jour calendaire. Tout délai calculé à partir d’une notification courra à compter de la première tentative de remise au destinataire, le cachet de la Poste faisant foi.</p>
<h3>17.5 Force Majeure</h3>
<p>Chacune des Parties ne saurait voir sa responsabilité engagée pour le cas où l’exécution de ses obligations serait retardée, restreinte ou rendue impossible du fait de la survenance d’un cas de Force Majeure. Il est expressément convenu entre les Parties que les stipulations du présent article 17.5ne sont pas applicables aux obligations de payer.</p>
<p>Dans l’hypothèse de la survenance d’une Force Majeure, l’exécution des obligations de chaque Partie est suspendue. Si la Force Majeure se poursuit pendant plus d’un (1) mois, le Contrat pourra être résilié à la demande de la Partie la plus diligente sans pour autant que la responsabilité d’une Partie puisse être engagée à l’égard de l’autre. Chacune des Parties supporte la charge de tous les frais qui lui incombent et qui résultent de la survenance de la Force Majeure.</p>
<h3>17.6 Fournisseurs – Prestataires – Sous-traitants</h3>
<p>Pendant toute la durée du Contrat, CALS sera libre de faire appel à tout fournisseur, prestataires et/ou sous-traitant de son choix.</p>
<p>Le Client autorise CALS à sous-traiter en partie ou en totalité les Services qui lui ont été confiés. Le sous-traitant pourra traiter des Données Emprunteur dans les conditions de l’Article 11.1.</p>
<p>Dans ce cadre, CALS restera, dans les conditions fixées au Contrat, responsable de la fourniture des Services.</p>
<h3>17.7 Convention de preuve</h3>
<p>Les registres informatisés seront conservés dans les systèmes informatiques de CALS dans des conditions raisonnables de sécurité et seront considérés comme les preuves des échanges et/ou des actions réalisées par les Utilisateurs Autorisés sur la Plateforme CALS, ce que le Client déclare accepter.</p>
<h3>17.8 Modification du Contrat</h3>
<p>Le Contrat ne pourra être modifié que d’un commun accord entre les Parties, par voie d’avenant écrit, signé par un représentant habilité de chacune des Parties.</p>
<h3>17.9 Renonciation</h3>
<p>Le fait que l’une ou l’autre des Parties n’exerce pas l’un quelconque de ses droits au titre des présentes ne saurait emporter renonciation de sa part à son exercice, une telle renonciation ne pouvant procéder que d’une déclaration expresse de la Partie concernée.</p>
<h3>17.10 Validité</h3>
<p>Dans l’hypothèse où une ou plusieurs stipulations du Contrat seraient considérées comme non valides par une juridiction compétente, les autres clauses conserveront leur portée et effet.</p>
<p>La stipulation considérée comme invalide sera remplacée par une stipulation dont le sens et la portée seront le plus proches possibles de la clause ainsi invalidée, tout en restant conforme à la législation applicable et à la commune intention des Parties.</p>
<h3>17.11&nbsp;Intégralité</h3>
<p>Le Contrat constitue l’intégralité de l’accord entre les Parties, à l’exclusion de tout autre document, notamment ceux pouvant être émis par le Client avant ou après la signature du Contrat.</p>
<h2>Article 18 Loi applicable - juridiction compétente</h2>
<p>Le Contrat est régi par le droit français.</p>
<p>Les Parties acceptent expressément de soumettre tout litige relatif au Contrat (en ce compris tout différend concernant sa négociation, sa conclusion, son exécution, sa résiliation et/ou sa cessation) et/ou aux relations commerciales entre les Parties ainsi qu’à leur rupture éventuelle, à la compétence exclusive des Tribunaux de Paris, nonobstant pluralité de défendeurs ou appel en garantie, y compris pour les procédures sur requête ou en référé.</p>
', '', 1, NOW(), NOW());
TREE_ELEMENT
        );
        $this->addSql('INSERT INTO settings (type, value, added) VALUES (\'SERVICE_TERMS_PAGE_ID\', 96, NOW())');
        $this->addSql(
            <<<'TRANSLATIONS'
            INSERT INTO translations (locale, section, name, translation, added) VALUES
                ('fr_FR', 'service-terms-popup', 'title', 'Les conditions générales d‘utilisation évoluent.', NOW()),
                ('fr_FR', 'service-terms-popup', 'confirm-check-box-label', 'Je certifie avoir pris connaissance et accepter expressément <a href="/conditions-service">les conditions générales d‘utilisation de CALS</a>.', NOW()),
                ('fr_FR', 'mail-title', 'service-terms-accepted', 'Vous avez accepté les conditions générales d‘utilisation', NOW())
TRANSLATIONS
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DELETE FROM settings WHERE type = \'SERVICE_TERMS_PAGE_ID\'');
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

        $this->addSql('DELETE FROM translations WHERE section = \'service-terms-popup\' AND name in (\'title\', \'confirm-check-box-label\')');
    }
}
