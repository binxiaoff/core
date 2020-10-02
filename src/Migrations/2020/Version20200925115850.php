<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Ramsey\Uuid\Uuid;

final class Version20200925115850 extends AbstractMigration
{
    protected $content = <<<EOT
<p>Les présentes conditions générales d’utilisation (ci-après désignées les «&nbsp;<strong>Conditions Générales</strong>&nbsp;») régissent les relations entre la société Crédit Agricole Lending Services, société par actions simplifiée à associé unique au capital de 30.000 euros, immatriculée au registre du commerce et des sociétés de Paris sous le numéro 850&nbsp;890 666, dont le siège social est situé au 50 rue la Boétie, 75008 Paris (ci-après désignée «&nbsp;<strong>Crédit Agricole Lending Services</strong>&nbsp;» ou «&nbsp;<strong>CALS</strong>&nbsp;») et toute personne physique qui utilise la Plateforme KLS dans le cadre de son activité professionnelle (ci-après désignée la « <strong>Personne Habilitée</strong> ») via son compte utilisateur («&nbsp;Compte Utilisateur&nbsp;»).</p>
<p>La création d’un Compte Utilisateur sur la Plateforme KLS implique l’acceptation pleine et entière des Conditions Générales au moyen d’une case à cocher, dont la Personne Habilitée reconnaît avoir pris connaissance, les avoir comprises et acceptées en parfaite connaissance de cause.</p>
<p>CALS et la Personne Habilitée sont ci-après dénommés individuellement une «&nbsp;<strong>Partie</strong>&nbsp;» et collectivement les «&nbsp;<strong>Parties</strong>&nbsp;».</p>
<section class="article" id="definitions">
    <h2>Définitions</h2>
    <p>Les termes et expressions dont la première lettre de chaque mot est en majuscule ont, au sein des Conditions Générales, la signification qui leur est attribuée ci-après, qu’ils soient utilisés au singulier ou au pluriel.</p>
    <ol>
        <li>«&nbsp;<strong>Établissement Client</strong>&nbsp;» désigne tout établissement qui a signé avec KLS le présent Contrat, et qui est habilité à effectuer des opérations de crédit au sens de des articles L. 511-5 et L. 511-6 du Code Monétaire et Financier, en ce inclus les établissements de crédit, fonds de dettes et fonds d’investissement. L’Établissement Client peut participer, via la plateforme KLS, à une opération de Syndication Bancaire en qualité d’Arrangeur ou de Participant. L’Établissement Client est représenté par un Administrateur ou toute autre Personne Habilitée.</li>
        <li>«&nbsp;<strong>Force Majeure</strong>&nbsp;» désigne un évènement extérieur aux Parties, imprévisible et irrésistible, tel que défini par la jurisprudence des tribunaux française, en ce compris&nbsp;: guerre (déclarée ou non)&nbsp; acte terroriste&nbsp; invasion&nbsp; rébellion&nbsp; blocus&nbsp; sabotage ou acte de vandalisme&nbsp; grève ou conflit social, total ou partiel, externe à chacune des Parties&nbsp; intempérie (notamment inondations, orages et tempêtes)&nbsp; évènement déclaré «&nbsp;catastrophe naturelle&nbsp;»&nbsp; incendie&nbsp; épidémie&nbsp; blocage des moyens de transport ou d’approvisionnement (notamment en énergie)&nbsp; défaillance dans la fourniture de l’énergie électrique, du chauffage, de l’air conditionné, des réseaux de télécommunications, du transport des données&nbsp; défaillance de satellites.</li>
        <li>«&nbsp;<strong>Module</strong>&nbsp;» désigne un ensemble cohérent de fonctionnalités via la Plateforme KLS que l’Établissement Client demeure libre d’activer via son Administrateur. L’Établissement Client ne peut accéder à un Module que s’il a préalablement été activé. A compter de son activation par l’Établissement Client, le Module sera activé pour une année, indépendamment de la date de signature du Contrat, et facturé dans les conditions telles que précisées à l’article 5 «&nbsp;Conditions Financières&nbsp;» du Contrat. Des Modules supplémentaires sont susceptibles d’être ajoutés à la Plateforme KLS. Ils seront portés à la connaissance de l’Établissement Client et feront l’objet d’un avenant au Contrat qui précisera le prix et les fonctionnalités du Module concerné.</li>
        <li>«&nbsp;<strong>Personne Habilitée</strong>&nbsp;» désigne une personne physique identifiée au sein d’un Etablissement Client, d’un Etablissement Invité GCA ou non GCA, de l’Emprunteur, d’un Prestataire et agissant pour son nom et son compte. Une Personne Habilitée peut accéder à la Plateforme KLS et utiliser les Services conformément aux stipulations du Contrat, dans les limites d’habilitation fixée par KLS pour chacun des profils de la Personne Habilitée.</li>
        <li>«&nbsp;<strong>Plateforme KLS</strong>&nbsp;» désigne la solution logicielle éditée par CALS, laquelle permet avec son produit KLS Syndication de mettre en relation des Établissements Clients et/ou des Établissements Invités afin qu’ils réalisent entre eux et suivent des opérations de Syndication Bancaire.</li>
        <li>«&nbsp;<strong>Responsable</strong>&nbsp;» désigne parmi les Personnes Habilitées, et au sein des Établissements Clients, une personne physique salariée qui est habilitée par ce dernier, avec la faculté de déléguer sous sa seule responsabilité, à activer les différents modules de la Plateforme KLS, à gérer les habilitations d’accès à la Plateforme KLS et à créer des comptes à toute autre Personne Habilitée.</li>
        <li>«&nbsp;<strong>Syndication Bancaire</strong>&nbsp;» désigne la réunion de deux ou plusieurs Établissements Clients ayant pour objet de partager le risque, le financement et/ou la rémunération d’un prêt.</li>
        <li>«&nbsp;<strong>Compte Utilisateur</strong>&nbsp;» désigne le compte qui permet à tout Personne Habilitée de bénéficier, après avoir renseigné des informations obligatoires le concernant, d’un accès à la Plateforme KLS sans obligation d’en utiliser les fonctionnalités.</li>
    </ol>
</section>
<section class="article" id="object">
    <h2>Objet</h2>
    <p>Les Conditions Générales définissent les conditions dans lesquelles les Personnes Habilitées peuvent accéder et utiliser la Plateforme KLS.</p>
</section>
<section class="article" id="platformAccess">
    <h2>Accès à la platforme KLS</h2>
    <p>Afin de pouvoir accéder à la Plateforme KLS, les Personnes Habilitées doivent être titulaire d’un Compte Utilisateur.</p>
    <p>Afin de pouvoir utiliser la Plateforme KLS, la Personne Habilitée s’engage expressément à être habilité par l’Etablissement Client dont il est salarié à réaliser des opérations de Syndication Bancaire en son nom et pour son compte.</p>
    <p>Enfin, la Personne Habilitée déclare et garantit être soumis à une obligation de confidentialité afin de pouvoir accéder à la Plateforme KLS.</p>
</section>
<section class="article" id="authorizedPeopleAccounts">
    <h2>Compte des Personnes Habilitées</h2>
    <p>Le Responsable crée les Comptes Utilisateurs des Personnes Habilitées.</p>
    <p>Lorsque le Responsable crée un Compte Utilisateur, la Personne Habilitée concernée reçoit un email sur son adresse électronique professionnelle.</p>
    <p>Afin de finaliser la création de son Compte Utilisateur, la Personne Habilitée concernée doit suivre les instructions précisées sur l’email réceptionné.</p>
    <p>Pour achever la création de son Compte Utilisateur, la Personne Habilitée doit accepter les présentes Conditions Générales d’Utilisation.</p>
    <p>Dans le cadre de la création de son Compte Utilisateur sur la Plateforme KLS, il sera demandé la Personne Habilitée de choisir un mot de passe. Pour des raisons de sécurité et de confidentialité, il est recommandé à la Personne Habilitée de choisir des mots de passe composés de plusieurs types de caractères, et de le modifier régulièrement.</p>
    <p>L’identifiant et le mot de passe sont uniques et personnels. Ils ne doivent pas être divulgués à des tiers. Toute utilisation de la Plateforme KLS réalisée au moyen de l’identifiant et du mot de passe d’une Personne Habilitée sera réputée avoir été réalisée par ladite Personne Habilitée. En cas de divulgation de son identifiant et mot de passe, la Personne Habilitée doit contacter dans les plus brefs délais le Responsable ou le support informatique de CALS à l’adresse e-mail&nbsp;:
        <a href="mailto:support@ca-lendingservices.com">support@ca-lendingservices.com</a> afin que ceux-ci soient désactivés.</p>
    <p>Le compte personnel de chaque Personne Habilitée lui permet de mettre à jour les données le concernant.</p>
    <p>Les Personnes Habilitées sont informées et acceptent que leurs droits d’accès et d’utilisation de la Plateforme KLS</p>
</section>
<section class="article" id="usagePlatformLicence">
    <h2>License d'utilisation de la platforme KLS</h2>
    <section>
        <h3>Droit d’utilisation</h3>
        <p>CALS accorde un droit d’utilisation personnel, non exclusif, non cessible et sans droit de licence, de la Plateforme KLS à la Personne Habilitée dans les limites et conditions spécifiées aux présentes Conditions Générales d’Utilisation, pour le monde entier et pour toute la durée pendant laquelle le Compte Utilisateur est actif sur la Plateforme KLS.</p>
    </section>
    <section>
        <h3>Limitations</h3>
        <p>La Personne Habilitée s’interdit, directement ou indirectement, sauf accord exprès, préalable et écrit de CALS&nbsp;:</p>
        <ol type="i">
            <li>de décompiler, désassembler la Plateforme KLS, de pratiquer l’ingénierie inverse ou de tenter de découvrir ou reconstituer le code source, les idées qui en sont la base, les algorithmes, les formats des fichiers ou les interfaces de programmation ou d’interopérabilité de la Plateforme KLS sauf dans la limite du droit accordé par l’article L. 122-6-1 du code de la propriété intellectuelle, de quelque manière que ce soit. Au cas où la Personne Habilitée souhaiterait obtenir les informations permettant de mettre en œuvre l’interopérabilité de la Plateforme KLS avec un autre logiciel développé ou acquis de manière indépendante par, la Personne Habilitée et ce pour un emploi conforme à la destination de la Plateforme KLS, la Personne Habilitée s’engage, avant de faire appel à un tiers, à consulter préalablement CALS qui pourra lui fournir les informations nécessaires à la mise en œuvre de cette interopérabilité. Le coût exact engendré en interne chez CALS pour la fourniture de ces informations sera facturé par CALS à l’Établissement Client&nbsp;</li>
            <li>de procéder seul, ou avec l’aide d’un tiers prestataire, à la correction des éventuelles erreurs de la Plateforme KLS pour le rendre conforme à sa destination, CALS se réservant seul l’exercice de ce droit conformément à l’article L. 122-6-1-I du code de la propriété intellectuelle&nbsp;</li>
            <li>de supprimer ou modifier toute référence ou indication relative aux droits de propriété de CALS ou de tout tiers&nbsp;</li>
            <li>de transférer, utiliser ou exporter la Plateforme KLS en violation de la réglementation en vigueur&nbsp;</li>
            <li>d’intégrer ou d’associer la Plateforme KLS avec d’autres logiciels ou documents ou de créer des œuvres composites ou dérivées avec l’aide de tout ou partie de la Plateforme&nbsp;KLS&nbsp;</li>
            <li>d’effectuer toute autre utilisation de la Plateforme KLS que celle permise dans le cadre des présentes Conditions Générales d’Utilisation et/ou du Contrat de Service.</li>
        </ol>
    </section>
</section>
<section class="article" id="authorizedPeopleEngagement">
    <h2>Engagements des Personnes Habilitées</h2>
    <p>En l’absence d’autorisation préalable et écrite de CALS, il est interdit&nbsp;:</p>
    <ol type="i">
        <li>d’utiliser la Plateforme KLS autrement que de bonne foi, et conformément à l’objet des présentes Conditions Générales d’Utilisation, pour faciliter les interventions de CALS&nbsp;</li>
        <li>de charger ou transmettre sur la Plateforme KLS ou utiliser tout équipement, logiciel ou routine qui contienne des virus, chevaux de Troie, vers, bombes à retardement ou autres programmes et procédés destinés à endommager, interférer ou tenter d’interférer avec le fonctionnement normal de la Plateforme KLS, ou s’approprier la Plateforme KLS, ou encore recourir à n’importe quel moyen pour causer une saturation des systèmes de CALS ou porter atteinte aux droits de tiers.</li>
    </ol>
    <p>Il est rappelé que les articles 323-1 et suivants du code pénal sanctionnent par des peines allant jusqu’à cinq (5) ans d’emprisonnement et 150.000&nbsp;euros d’amende, notamment&nbsp;:</p>
    <ol type="i">
        <li>l’accès et le maintien frauduleux dans un système de traitement automatisé de données&nbsp;</li>
        <li>la suppression, la modification ou l’ajout frauduleux de données dans ce système&nbsp;</li>
        <li>le fait d’entraver ce système.</li>
    </ol>
    <p>Les Personnes Habilitées déclarent et garantissent&nbsp;:</p>
    <ol type="i">
        <li>disposer des autorisations nécessaires pour réaliser des opérations de Syndication Bancaire&nbsp;</li>
        <li>posséder les pouvoirs nécessaires dans la chaîne délégataire et hiérarchique de l’Etablissement Client,  dont ils sont salariés, pour participer à une opération de Syndication Bancaire au nom et pour le compte de leur employeur&nbsp;</li>
        <li>être pleinement autorisés par leur supérieur hiérarchique à participer à des opérations de Syndication Bancaire et à engager leur employeur dans le cadre d’opérations de Syndication Bancaire&nbsp;</li>
        <li>être habilités au sein de leur propre structure à procéder à chacune des actions et des opérations réalisées sur la Plateforme KLS&nbsp;</li>
        <li>respecter l’ensemble des dispositions législatives, règlementaires et déontologiques relatives à la lutte contre le blanchiment de capitaux et le financement du terrorisme&nbsp;</li>
        <li>ne pas transmettre par le biais de la Plateforme KLS des contenus à caractère illicite, ou tout autre message qui pourrait constituer un crime ou un délit, engager la responsabilité civile, porter atteinte à la législation ou inciter au faire, ou encore des contenus qui pourraient être utilisés à toute fin contraire à la loi ou aux présentes Conditions Générales d’Utilisation&nbsp;</li>
        <li>ne pas réaliser par le biais de la Plateforme KLS des opérations illicites qui pourraient constituer un crime ou un délit, engager la responsabilité civile, porter atteinte à la législation ou inciter au faire, ou encore des contenus qui pourraient être utilisés à toute fin contraire à la loi ou aux présentes Conditions Générales d’Utilisation.</li>
    </ol>
    <p>La Personne Habilitée, ensemble avec CALS, s’engagent à prendre toutes les mesures afin de garantir le respect du droit de la concurrence. Elles ne devront échanger que les informations nécessaires à la mise en œuvre de la syndication et s’abstenir d’échanger toute information commercialement sensible en dehors du cadre de ladite syndication.</p>
    <p>Sont considérées comme sensibles des informations individualisées récentes (généralement de moins d’un an) ou futures, de nature non-publique, relatives (i) au prix de vente ou d’achat de biens/services et aux composantes de ces prix (par exemple, marges, commissions, remises,…)&nbsp; (ii) à la stratégie commerciale (par exemple, conditions de vente, coûts, clients, investissements, plan marketings, opportunités «&nbsp;business&nbsp;», projets de développement individuel ou en coopération interbancaire, promotions à venir, intentions de réduction ou développement d’activités,…)&nbsp; (iii) mais également au positionnement concurrentiel sur le marché (par exemple, parts de marché, chiffres d’affaires, volumes et valeurs de ventes, quantités, capacités, indicateurs de production,…).</p>
</section>
<section class="article" id="personalDataProtection">
    <h2>Protection des données à caractère personnel</h2>
    <section>
        <h3>Collecte de données à caractère personnel</h3>
        <p>CALS, dans ses relations avec les Personnes Habilitées, est amenée à traiter les données à caractère personnel des Personnes Habilitées, lesquelles données lui ont été communiquées soit par l’Etablissement Client, soit par les Personnes Habilitées dans le cadre de leur inscription sur la Plateforme KLS ainsi que dans le cadre de leur utilisation de la Plateforme KLS.</p>
        <p>Les Personnes Habilitées sont informées que la communication de leurs données à caractère personnel est nécessaire à l’utilisation de la Plateforme KLS.</p>
        <p>CALS ne procèdera à aucun autre traitement de données à caractère personnel autre que ceux décrits au sein des présentes Conditions Générales d’Utilisation.</p>
    </section>
    <section>
        <h3>Bases légales du traitement de données à caractère personnel</h3>
        <p>En utilisant la Plateforme KLS, les Personnes Habilitées ont accepté les termes des présentes Conditions Générales d’Utilisation.</p>
        <p>Ce document formalise une relation contractuelle entre les Personnes Habilitées et CALS servant de base juridique à la collecte et au traitement des données à caractère personnel des Personnes Habilitées par CALS et l’Etablissement Client.</p>
    </section>
    <section>
        <h3>Catégories des données à caractère personnel</h3>
        <p>Les données à caractère personnel que CALS est amenée à collecter et traiter dans le cadre de l’utilisation de la Plateforme KLS sont&nbsp;:</p>
        <section>
            <ol type="i">
                <li>les données d’identification (p. ex. nom, prénom(s), date de naissance)&nbsp;</li>
                <li>les coordonnées (p. ex. téléphone, adresse e-mail, adresse postale)&nbsp;</li>
                <li>les données relatives à la vie professionnelle (p.ex. profession)&nbsp;</li>
                <li>les données de connexion&nbsp;(p. ex. logs).</li>
            </ol>
        </section>
    </section>
    <section>
        <h3>Finalités du traitement</h3>
        <p>Ces données sont collectées et traitées par CALS pour les finalités suivantes&nbsp;:</p>
        <ol type="i">
            <li>le fonctionnement de la Plateforme KLS&nbsp;</li>
            <li>le suivi de l’utilisation de la Plateforme KLS&nbsp;</li>
            <li>l’envoi d’emails par la Plateforme KLS aux Personnes Habilitées&nbsp;</li>
            <li>vérifier que la Personne Habilitée n’est pas un robot&nbsp;</li>
            <li>la signature électronique de documents.</li>
        </ol>
    </section>
    <section>
        <h3>Les droits des personnes concernées</h3>
        <p>Conformément à la loi du 6 janvier 1978, modifiée, relative à l’informatique, aux fichiers et aux libertés (ci-après désignée la « Loi n° 78-17») et au Règlement (UE) 2016/679 du Parlement européen et du Conseil du 27 avril 2016 relatif à la protection des personnes physiques à l'égard du traitement des données à caractère personnel et à la libre circulation de ces données (ci-après désigné le « RGPD »), les Personnes Habilitées disposent du droit d’accéder à leurs données personnelles, les rectifier, les effacer, demander leur portabilité, définir des directives relatives au sort de ces données après leur décès, demander la limitation de ce traitement, s’y opposer ou retirer leur consentement.</p>
        <p>L’exercice de ces droits s’effectue à tout moment en écrivant à CALS à l’adresse électronique suivante&nbsp;:
            <a href="mailto:dpo@ca-lendingservices.com">dpo@ca-lendingservices.com</a>.</p>
        <p>La Personne Habilitée peut, à tout moment, porter réclamation auprès de la CNIL dont les coordonnées sont disponibles sur son site Internet (<a
                href="https://www.cnil.fr" rel="noopener" target="_blank">www.cnil.fr</a>).</p>
    </section>
    <section>
        <h3>Conservation des données</h3>
        <p>Les données à caractère personnel des Personnes Habilitées qui n’ont participé à aucune opération de Syndication Bancaire sont conservées uniquement pendant la durée de leur inscription sur la Plateforme KLS. Les données des Personnes Habilitées sont conservées conformément aux informations présentées dans le registre de traitement de CALS.</p>
        <p>Les données à caractère personnel des Personnes Habilitées qui ont pris part à des opérations de Syndication Bancaire sont conservées pendant la durée des crédits et 5 ans à compter de la suppression de leur compte sur la Plateforme KLS, sauf pour les données collectées pour le suivi de l’utilisation de la Plateforme KLS et le suivi des analytics, et l’envoi d’emails par la Plateforme KLS aux Personnes Habilitées, où la durée de conservation est d’une (1) année à compter de la dernière utilisation de leur Compte Utilisateur, afin de permettre à CALS de respecter ses obligations de transparence.</p>
        <p>En cas de procédure contentieuse, toutes informations, documents et pièces contenant des données personnelles des Personnes Habilitées tendant à établir les faits litigieux peuvent être conservés pour la durée de la procédure, y compris pour une durée supérieure à celles indiquées ci-dessus.</p>
        <p>Certaines données pourront être archivées au-delà des durées prévues pour les besoins de la recherche, de la constatation et de la poursuite des infractions pénales dans le seul but de permettre, en tant que besoins, la mise à disposition de ces données à l’autorité judiciaire.</p>
        <p>L'archivage implique que ces données soient anonymisées et ne soient plus consultables en ligne mais soient extraites et conservées sur un support autonome et sécurisé.</p>
    </section>
    <section>
        <h3>Destinataires des données</h3>
        <p>Les données à caractère personnel collectées par le biais de la Plateforme KLS pourront être transférées à des tiers lorsque cela est nécessaire à l’exploitation et à la maintenance de la Plateforme KLS (p. ex. hébergeur de la Plateforme KLS), à la bonne exécution des opérations de Syndication Bancaire (p. ex. prestataire de signature électronique), au suivi des opérations de Syndication Bancaire (p. ex. Etablissement Client) et afin de répondre à une injonction des autorités légales.</p>
        <p>A ce titre, lorsque l’entité concernée est située en dehors de l’Union Européenne, ou dans un pays ne disposant pas d’une réglementation adéquate au sens du RGPD, nous encadrons notre relation contractuelle avec cette entité en adoptant un dispositif contractuel approprié.</p>
    </section>
</section>
<section class="article" id="platformAccessSuspension">
    <h2>Suspension/Interruption de l’accès à la Plateforme KLS</h2>
    <p>CALS se réserve le droit de suspendre l’accès à la Plateforme KLS ou à certaines fonctionnalités de la Plateforme KLS, moyennant un préavis de 48 heures, sans formalité et sans indemnités, par courrier électronique en cas de manquement de la part des Personnes Habilitées à leurs obligations au titre des présentes Conditions Générales d’Utilisation, notamment dans les cas suivants&nbsp;:</p>
    <ol type="i">
        <li>un manquement à tout ou partie des stipulations des présentes Conditions Générales d’Utilisation&nbsp;</li>
        <li>un manquement à la législation applicable&nbsp;</li>
        <li>l’atteinte par la Personne Habilitée aux droits de propriété intellectuelle de CALS et/ou d’un tiers.</li>
    </ol>
    <p>CALS se réserve la possibilité d’interrompre, à tout moment, de manière temporaire ou définitive, l’accès à la Plateforme KLS. Dans le cas d’une interruption définitive, les Personnes Habilitées seront informés par tout moyen pertinent déterminé par la Plateforme KLS.</p>
    <p>CALS ne pourra en aucun cas être tenue responsable à l’encontre de la Personne Habilitée pour la suspension ou l’interruption de l’accès à la Plateforme KLS intervenue dans les conditions prévues au présent article.</p>
    <p>CALS interrompra, de manière définitive pour toute Personne Habilitée d’un Etablissement Client, l’accès à la Plateforme KLS en cas de cessation, pour quelque raison que ce soit du Contrat de Service et ce, sans préavis, sans formalité et sans indemnité.</p>
    <p>La Personne Habilitée devra contacter l’Etablissement Client pour récupérer les données et documents qu’il désire. CALS ne pourra en aucun cas être tenu responsable pour l’effacement de ces documents.</p>
</section>
<section class="article" id="warranty">
    <h2>Garantie - Responsabilité</h2>
    <p>CALS ne fournit aucune autre garantie que celles expressément visées par les présentes Conditions Générales d’Utilisation.</p>
    <p>En particulier, les Personnes Habilitées sont informées qu’elles sont seules responsables de l’usage qu’elles font de la Plateforme KLS et qu’elles ne pourront obtenir un quelconque dédommagement en cas d’utilisation détournée de la Plateforme KLS.</p>
    <p>CALS ne saurait être responsable de tout dommage subi par la Personne Habilitée qui résulterait du défaut du respect de tout ou partie des présentes Conditions Générales d’Utilisation, d’une faute de sa part, du fait d’un tiers ou de la survenance d’un cas de Force Majeure.</p>
    <p>CALS ne saurait être responsable de toute défaillance de matériel, services et/ou installation qu’elle ne fournit pas (directement ou indirectement), quand bien même ceux-ci seraient en relation avec la Plateforme KLS.</p>
</section>
<section class="article" id="miscellaneous">
    <h2>Stipulations diverses</h2>
    <section>
        <h3>Modification des présentes Conditions Générales d’Utilisation</h3>
        <p>CALS se réserve le droit d’apporter, à tout moment, aux présentes Conditions Générales d’Utilisation toutes les modifications qu’elle jugera nécessaires et utiles.</p>
        <p>En cas de modification des présentes Conditions Générales d’Utilisation, CALS s’engage à faire accepter à nouveau à la Personne Habilitée les nouvelles conditions générales au moment où il accède à nouveau à la Plateforme KLS.</p>
        <p>Les Personnes Habilitées n’ayant pas expressément accepté les nouvelles conditions générales ne pourront pas avoir accès à la Plateforme KLS.</p>
    </section>
    <section>
        <h3>Force Majeure</h3>
        <p>Chacune des Parties ne saurait voir sa responsabilité engagée pour le cas où l’exécution de ses obligations serait retardée, restreinte ou rendue impossible du fait de la survenance d’un événement échappant au contrôle de chacune des Parties, qui ne pouvait être raisonnablement prévu lors de la conclusion des Conditions Générales et dont les effets ne peuvent être évités par des mesures appropriée (ci-après désignée la « Force Majeure »).</p>
        <p>Sont notamment considérée comme Force Majeure, sans que cette liste soit limitative, les évènements suivants&nbsp;: guerre (déclarée ou non)&nbsp; acte terroriste&nbsp; invasion&nbsp; rébellion&nbsp; blocus&nbsp; sabotage ou acte de vandalisme&nbsp; grève ou conflit social, total ou partiel, externe à chacune des Parties&nbsp; intempérie (notamment inondations, orages et tempêtes)&nbsp; évènement déclaré « catastrophe naturelle »&nbsp; incendie&nbsp; épidémie&nbsp; blocage des moyens de transport ou d’approvisionnement (notamment en énergie)&nbsp; défaillance dans la fourniture de l'énergie électrique, du chauffage, de l'air conditionné, des réseaux de télécommunications, du transport des données&nbsp; défaillance de satellites.</p>
    </section>
    <section>
        <h3>Renonciation</h3>
        <p>Le fait que l’une ou l’autre des Parties n’exerce pas l’un quelconque de ses droits au titre des présentes ne saurait emporter renonciation de sa part à son exercice, une telle renonciation ne pouvant procéder que d’une déclaration expresse de la Partie concernée.</p>
    </section>
    <section>
        <h3>Convention de preuve</h3>
        <p>Les registres informatisés seront conservés dans les systèmes informatiques de CALS dans des conditions raisonnables de sécurité et seront considérés comme les preuves des échanges intervenus sur la Plateforme KLS ou par courrier électronique.</p>
    </section>
    <section>
        <h3>Invalidité partielle</h3>
        <p>Dans l’hypothèse où une ou plusieurs stipulations des Conditions Générales seraient considérées comme non valides par une juridiction compétente, les autres clauses conserveront leur portée et effet.</p>
        <p>La stipulation considérée comme invalide sera remplacée par une stipulation dont le sens et la portée seront le plus proches possibles de la clause ainsi invalidée, tout en restant conforme à la législation applicable et à la commune intention des Parties.</p>
    </section>
</section>
<section class="article" id="applicableLaw">
    <h2>Loi applicable - juridiction compétente</h2>
    <p>Les Conditions Générales sont régies par le droit français.</p>
    <p><small>LES PARTIES ACCEPTENT EXPRESSÉMENT DE SOUMETTRE TOUT LITIGE RELATIF AUX CONDITIONS GÉNÉRALES (EN CE COMPRIS TOUT DIFFÉREND CONCERNANT SA NÉGOCIATION, SA CONCLUSION, SON EXÉCUTION, SA RÉSILIATION ET/OU SA CESSATION) ET/OU AUX RELATIONS COMMERCIALES ENTRE LES PARTIES AINSI QU’À LEUR RUPTURE ÉVENTUELLE, À LA COMPÉTENCE EXCLUSIVE DES TRIBUNAUX DE PARIS, NONOBSTANT PLURALITÉ DE DÉFENDEURS OU APPEL EN GARANTIE, Y COMPRIS POUR LES PROCÉDURES SUR REQUÊTE OU EN RÉFÉRÉ.</small></p>
</section>
<br>
<br>
<section class="annexe" id="annexe">
    <h2>Présentation des habilitations sur la plateforme KLS</h2>
    <br>
    <h3>«&nbsp;Responsable&nbsp;»&nbsp;: Désignation des profils «&nbsp;Administrateur&nbsp;» d’un Établissement Client&nbsp;</h3>
    <img style="max-width:100%;" src="/images/gtu_manager.png" />
    <br>
    <h3>«&nbsp;Personne Habilitée&nbsp;»&nbsp;: Habilitation d’un collaborateur au sein d’un Établissement Client&nbsp;</h3>
    <img style="max-width:100%;" src="/images/gtu_habilitations.png" />
    <br>
    <h3>«&nbsp;Personne Habilitée&nbsp;»&nbsp;: Invitation d’un collaborateur d’un Établissement Invité, externe au Groupe Crédit Agricole&nbsp;:</h3>
    <img style="max-width:100%;" src="/images/gtu_habilitations_external.png" />
</section>
EOT;

    public function getDescription(): string
    {
        return 'CALS-2470 Push new service terms';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("INSERT INTO legal_document (id, type, title, content, public_id, first_time_instruction, differential_instruction, added) VALUES (2, 1, 'Conditions générales d‘utilisation', :content, :uuid, '', '', '2020-09-25 12:00:00');", [
            'content'=> $this->content,
            'uuid' => (Uuid::uuid4())->toString()
        ]);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
    }
}
