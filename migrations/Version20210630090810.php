<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20210630090810 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '[Core] CALS-3746 Add new CGU';
    }

    public function up(Schema $schema): void
    {
        $publicId = "LOWER(
            CONCAT(
                HEX(RANDOM_BYTES(4)), '-',
                HEX(RANDOM_BYTES(2)), '-', 
                '4', SUBSTR(HEX(RANDOM_BYTES(2)), 2, 3), '-', 
                CONCAT(HEX(FLOOR(ASCII(RANDOM_BYTES(1)) / 64)+8), SUBSTR(HEX(RANDOM_BYTES(2)), 2, 3)), '-',
                HEX(RANDOM_BYTES(6))
            )
        )";

        $content = <<<'HTML'
                  <section class="article">
                    <p>
                      Les présentes conditions générales d’utilisation (ci-après désignées les «<b> Conditions Générales </b>») régissent les relations entre la société KLS, société par
                      actions simplifiée à associé unique au capital de 30.000 euros, immatriculée au registre du commerce et des sociétés de Paris sous le numéro 850 890 666, dont le siège
                      social est situé au 50 rue la Boétie, 75008 Paris (ci-après désignée «<b> KLS </b>») et toute personne physique qui utilise la Plateforme KLS dans le cadre de son
                      activité professionnelle (ci-après désignée la «<b> Personne Habilitée </b>») via son compte utilisateur («<b> Compte Utilisateur </b>»).
                    </p>
                    <p>
                      La création d’un Compte Utilisateur sur la Plateforme KLS implique l’acceptation pleine et entière des Conditions Générales au moyen d’une case à cocher, dont la Personne
                      Habilitée reconnaît avoir pris connaissance, les avoir comprises et acceptées en parfaite connaissance de cause.
                    </p>
                    <p>
                      KLS et la Personne Habilitée sont ci-après dénommés individuellement une «<b> Partie </b>» et collectivement les «<b> Parties </b>».
                    </p>
                  </section>
                  <section class="article">
                    <h2>Définitions</h2>
                    <p>
                      Les termes et expressions dont la première lettre de chaque mot est en majuscule ont, au sein des Conditions Générales, la signification qui leur est attribuée ci-après,
                      qu’ils soient utilisés au singulier ou au pluriel.
                    </p>
                    <ol>
                      <li>
                        <p>
                          «<b> Agency </b>» permet la gestion du crédit syndiqué par l’Agent du crédit en relation avec l’Emprunteur et les Participants
                        </p>
                      </li>
                      <li>
                        <p>
                          «<b> Agent du crédit </b>» désigne l’Établissement Client qui est chargé de l’Agency c’est-à-dire de la préparation du closing d’un crédit syndiqué, du suivi des
                          engagements de l’Emprunteur, de la relation entre l’Emprunteur et les Participants tout au long de la vie d’un crédit syndiqué
                        </p>
                      </li>
                      <li>
                        <p>
                          «<b> Établissement Client </b>» désigne tout établissement qui a signé avec KLS un Contrat de services pour au moins l’un de ses produits, et qui est habilité à
                          effectuer des opérations de crédit au sens de des articles L. 511-5 et L. 511-6 du Code Monétaire et Financier, en ce inclus les établissements de crédit, fonds de
                          dettes et fonds d’investissement. L’Établissement Client est représenté par un Administrateur ou toute autre Personne Habilitée
                        </p>
                      </li>
                      <li>
                        <p>
                          «<b> Force Majeure </b>» désigne un évènement extérieur aux Parties, imprévisible et irrésistible, tel que défini par la jurisprudence des tribunaux française, en ce
                          compris : guerre (déclarée ou non) ; acte terroriste ; invasion ; rébellion ; blocus ; sabotage ou acte de vandalisme ; grève ou conflit social, total ou partiel,
                          externe à chacune des Parties ; intempérie (notamment inondations, orages et tempêtes) ; évènement déclaré « catastrophe naturelle » ; incendie ; épidémie ; blocage
                          des moyens de transport ou d’approvisionnement (notamment en énergie) ; défaillance dans la fourniture de l’énergie électrique, du chauffage, de l’air conditionné,
                          des réseaux de télécommunications, du transport des données ; défaillance de satellites
                        </p>
                      </li>
                      <li>
                        <p>
                          «<b> Module </b>» désigne un ensemble cohérent de services ou de fonctionnalités d’un produit de la Plateforme KLS que l’Établissement Client demeure libre d’activer
                          via son Administrateur. L’Établissement Client ne peut accéder à un Module que s’il a préalablement été activé. A compter de son activation par l’Établissement
                          Client, le Module sera activé pour une période définie dans le Contrat de services signé entre KLS et l’Établissement Client, et facturé dans les conditions telles
                          que précisées à l’article «<b> Conditions Financières </b>» desdits Contrats. Des Modules supplémentaires sont susceptibles d’être ajoutés à la Plateforme KLS. Ils
                          seront portés à la connaissance de l’Établissement Client et feront l’objet d’un nouveau contrat de service ou d’un avenant au Contrat en cours qui précisera le prix
                          et les fonctionnalités du Module concerné
                        </p>
                      </li>
                      <li>
                        <p>
                          «<b> Personne Habilitée </b>» désigne une personne physique identifiée au sein d’un Etablissement Client, d’un Etablissement Invité, de l’Emprunteur, d’un
                          Prestataire, et agissant pour son nom et son compte. Une Personne Habilitée peut accéder à la Plateforme KLS et utiliser les produits et modules conformément aux
                          stipulations des contrats de service dans les limites d’habilitation fixée par KLS pour chacun des profils de la Personne Habilitée
                        </p>
                      </li>
                      <li>
                        <p>
                          «<b> Plateforme KLS </b>» désigne la solution logicielle éditée par KLS, laquelle permet avec son produit KLS Syndication de mettre en relation des Établissements
                          Clients et/ou des Établissements Invités afin qu’ils réalisent entre eux et suivent avec l’Emprunteur des opérations de Syndication Bancaire ; et avec son produit KLS
                          Credit & Guaranty de permettre la distribution par les Établissements Client des programmes de financement garantis par le FEI (fonds Européen d’Investissement)ou
                          refinancés par la CDC (Caisse des dépôts et Consignations) ou la BEI. (Banque Européenne d’Investissement)
                        </p>
                      </li>
                      <li>
                        <p>
                          «<b> Responsable </b>», appelé aussi «<b> Administrateur </b>» désigne parmi les Personnes Habilitées, et au sein des Établissements Clients une personne physique
                          salariée qui est habilitée par ce dernier, avec la faculté de déléguer sous sa seule responsabilité, à activer les différents modules de la Plateforme KLS, à gérer
                          les habilitations d’accès à la Plateforme KLS et à créer des comptes à toute autre Personne Habilitée
                        </p>
                      </li>
                      <li>
                        <p>
                          «<b> Syndication Bancaire </b>» désigne la réunion de deux ou plusieurs Établissements Clients ayant pour objet de partager le risque, le financement et/ou la
                          rémunération d’un prêt
                        </p>
                      </li>
                      <li>
                        <p>
                          «<b> Compte Utilisateur </b>» désigne le compte qui permet à tout Personne Habilitée de bénéficier, après avoir renseigné des informations obligatoires le concernant,
                          d’un accès à la Plateforme KLS sans obligation d’en utiliser les fonctionnalités
                        </p>
                      </li>
                    </ol>
                  </section>
                  <section class="article">
                    <h2>Objet</h2>
                    <p>Les Conditions Générales définissent les conditions dans lesquelles les Personnes Habilitées peuvent accéder et utiliser la Plateforme KLS.</p>
                  </section>
                  <section class="article">
                    <h2>Accès à la Plateforme KLS</h2>
                    <p>Afin de pouvoir accéder à la Plateforme KLS, les Personnes Habilitées doivent être titulaire d’un Compte Utilisateur.</p>
                    <p>
                      Afin de pouvoir utiliser la Plateforme KLS, la Personne Habilitée s’engage expressément à être habilité (i) par l’Etablissement Client dont il est salarié à réaliser ou
                      suivre des opérations de crédit qu’elles soient syndiquées dans le cadre du produit KLS Syndication ou qu’elles soient intégrées dans des programmes soutenus par le FEI,
                      la BEI ou la CDC dans le cadre de KLS Credit et Guaranty.
                    </p>
                    <p>Enfin, la Personne Habilitée déclare et garantit être soumis à une obligation de confidentialité afin de pouvoir accéder à la Plateforme KLS.</p>
                  </section>
                  <section class="article">
                    <h2>Compte des Personnes Habilitées</h2>
                    <p>Le Responsable crée les Comptes Utilisateurs des Personnes Habilitées. Il a la faculté de déléguer son droit de création de Comptes Utilisateurs.</p>
                    <p>Lorsque le Responsable crée un Compte Utilisateur, la Personne Habilitée concernée reçoit un email sur son adresse électronique professionnelle. </p>
                    <p>Afin de finaliser la création de son Compte Utilisateur, la Personne Habilitée concernée doit suivre les instructions précisées sur l’email réceptionné. </p>
                    <p>Pour achever la création de son Compte Utilisateur, la Personne Habilitée doit accepter les présentes Conditions Générales d’Utilisation. </p>
                    <p>
                      Dans le cadre de la création de son Compte Utilisateur sur la Plateforme KLS, il sera demandé à la Personne Habilitée de choisir un mot de passe. Pour des raisons de
                      sécurité et de confidentialité, il est recommandé à la Personne Habilitée de choisir des mots de passe composés de plusieurs types de caractères, et de le modifier
                      régulièrement.
                    </p>
                    <p>
                      L’identifiant et le mot de passe sont uniques et personnels. Ils ne doivent pas être divulgués à des tiers. Toute utilisation de la Plateforme KLS réalisée au moyen de
                      l’identifiant et du mot de passe d’une Personne Habilitée sera réputée avoir été réalisée par ladite Personne Habilitée. En cas de divulgation de son identifiant et mot
                      de passe, la Personne Habilitée doit contacter dans les plus brefs délais le Responsable ou le support informatique de KLS à l’adresse e-mail :
                      <a href="mailto:support@kls-platform.com">support@kls-platform.com</a> afin que ceux-ci soient désactivés.
                    </p>
                    <p>Le compte personnel de chaque Personne Habilitée lui permet de mettre à jour les données le concernant.</p>
                    <p>
                      Les Personnes Habilitées sont informées et acceptent que leurs droits d’accès et d’utilisation de la Plateforme KLS varient en fonction du produit de KLS Platform utilisé
                      et des dossiers qu’ils sont ou non intéressés à connaître.
                    </p>
                  </section>
                  <section class="article">
                    <h2>Licence d’utilisation de la Plateforme KLS</h2>
                    <section class="subsection">
                      <h3>Droit d’utilisation</h3>
                      <p>
                        KLS accorde un droit d’utilisation personnel, non exclusif, non cessible et sans droit de licence, de la Plateforme KLS à la Personne Habilitée dans les limites et
                        conditions spécifiées aux présentes Conditions Générales d’Utilisation, pour le monde entier et pour toute la durée pendant laquelle le Compte Utilisateur est actif sur
                        la Plateforme KLS.
                      </p>
                    </section>
                    <section class="subsection">
                      <h3>Limitations</h3>
                      <p>La Personne Habilitée s’interdit, directement ou indirectement, sauf accord exprès, préalable et écrit de KLS : </p>
                      <ol>
                        <li>
                          <p>
                            de décompiler, désassembler la Plateforme KLS, de pratiquer l’ingénierie inverse ou de tenter de découvrir ou reconstituer le code source, les idées qui en sont la
                            base, les algorithmes, les formats des fichiers ou les interfaces de programmation ou d’interopérabilité de la Plateforme KLS sauf dans la limite du droit accordé
                            par l’article L. 122-6-1 du code de la propriété intellectuelle, de quelque manière que ce soit. Au cas où la Personne Habilitée souhaiterait obtenir les
                            informations permettant de mettre en œuvre l’interopérabilité de la Plateforme KLS avec un autre logiciel développé ou acquis de manière indépendante par, la
                            Personne Habilitée et ce pour un emploi conforme à la destination de la Plateforme KLS, la Personne Habilitée s’engage, avant de faire appel à un tiers, à consulter
                            préalablement KLS qui pourra lui fournir les informations nécessaires à la mise en œuvre de cette interopérabilité. Le coût exact engendré en interne chez KLS pour
                            la fourniture de ces informations sera facturé par KLS à l’Établissement;
                          </p>
                        </li>
                        <li>
                          <p>
                            de procéder seul, ou avec l’aide d’un tiers prestataire, à la correction des éventuelles erreurs de la Plateforme KLS pour le rendre conforme à sa destination, KLS
                            se réservant seul l’exercice de ce droit conformément à l’article L. 122-6-1-I du code de la propriété intellectuelle ;
                          </p>
                        </li>
                        <li>
                          <p>de supprimer ou modifier toute référence ou indication relative aux droits de propriété de KLS ou de tout tiers ;</p>
                        </li>
                        <li>
                          <p>de transférer, utiliser ou exporter la Plateforme KLS en violation de la réglementation en vigueur ;</p>
                        </li>
                        <li>
                          <p>
                            d’intégrer ou d’associer la Plateforme KLS avec d’autres logiciels ou documents ou de créer des œuvres composites ou dérivées avec l’aide de tout ou partie de la
                            Plateforme KLS ;
                          </p>
                        </li>
                        <li>
                          <p>
                            d’effectuer toute autre utilisation de la Plateforme KLS que celle permise dans le cadre des présentes Conditions Générales d’Utilisation et/ou du Contrat de
                            Service KLS Syndication et/ou du Contrat de Service KLS Credit et Guaranty.
                          </p>
                        </li>
                      </ol>
                    </section>
                  </section>
                  <section class="article">
                    <h2>Engagements des Personnes Habilitées</h2>
                    <p>En l’absence d’autorisation préalable et écrite de KLS, il est interdit :</p>
                    <ol>
                      <li>
                        <p>
                          d’utiliser la Plateforme KLS autrement que de bonne foi, et conformément à l’objet des présentes Conditions Générales d’Utilisation, pour faciliter les interventions
                          de KLS ;
                        </p>
                      </li>
                      <li>
                        <p>
                          de charger ou transmettre sur la Plateforme KLS ou utiliser tout équipement, logiciel ou routine qui contienne des virus, chevaux de Troie, vers, bombes à retardement
                          ou autres programmes et procédés destinés à endommager, interférer ou tenter d’interférer avec le fonctionnement normal de la Plateforme KLS, ou s’approprier la
                          Plateforme KLS, ou encore recourir à n’importe quel moyen pour causer une saturation des systèmes de KLS ou porter atteinte aux droits de tiers.
                        </p>
                      </li>
                    </ol>
                    <p>
                      Il est rappelé que les articles 323-1 et suivants du code pénal sanctionnent par des peines allant jusqu’à cinq (5) ans d’emprisonnement et 150.000 euros d’amende,
                      notamment :
                    </p>
                    <ol>
                      <li>
                        <p>l’accès et le maintien frauduleux dans un système de traitement automatisé de données ;</p>
                      </li>
                      <li>
                        <p>la suppression, la modification ou l’ajout frauduleux de données dans ce système ;</p>
                      </li>
                      <li>
                        <p>le fait d’entraver ce système.</p>
                      </li>
                    </ol>
                    <p>Les Personnes Habilitées déclarent et garantissent :</p>
                    <ol>
                      <li>
                        <p>
                          disposer des autorisations nécessaires pour réaliser ou suivre des opérations de Syndication Bancaire , ou conclure et suivre des opérations de crédit dans le cadre
                          du produit KLS Credit et Guaranty ;
                        </p>
                      </li>
                      <li>
                        <p>
                          posséder les pouvoirs nécessaires dans la chaîne délégataire et hiérarchique de l’Etablissement Client ou Invité, dont ils sont salariés, pour participer à une
                          opération de Syndication Bancaire au nom et pour le compte de leur employeur, et/ou conclure et suivre des opérations de crédit dans le cadre du produit KLS Credit et
                          Guaranty ;
                        </p>
                      </li>
                      <li>
                        <p>
                          être pleinement autorisés par leur supérieur hiérarchique à participer à des opérations de Syndication Bancaire et/ou conclure et suivre des opérations dans le cadre
                          du produit KLS Credit et Guaranty et à engager leur employeur dans le cadre de ces opérations ;
                        </p>
                      </li>
                      <li>
                        <p>être habilités au sein de leur propre structure à procéder à chacune des actions et des opérations réalisées sur la Plateforme KLS ;</p>
                      </li>
                      <li>
                        <p>
                          respecter l’ensemble des dispositions législatives, règlementaires et déontologiques relatives à la lutte contre le blanchiment de capitaux et le financement du
                          terrorisme ;
                        </p>
                      </li>
                      <li>
                        <p>
                          ne pas transmettre par le biais de la Plateforme KLS des contenus à caractère illicite, ou tout autre message qui pourrait constituer un crime ou un délit, engager la
                          responsabilité civile, porter atteinte à la législation ou inciter au faire, ou encore des contenus qui pourraient être utilisés à toute fin contraire à la loi ou aux
                          présentes Conditions Générales d’Utilisation et/ou du Contrat de Service KLS Syndication et/ou du Contrat de Service KLS Credit et Guaranty ;
                        </p>
                      </li>
                      <li>
                        <p>
                          ne pas réaliser par le biais de la Plateforme KLS des opérations illicites qui pourraient constituer un crime ou un délit, engager la responsabilité civile, porter
                          atteinte à la législation ou inciter au faire, ou encore des contenus qui pourraient être utilisés à toute fin contraire à la loi ou aux présentes Conditions
                          Générales d’Utilisation et/ou du Contrat de Service KLS Syndication et/ou du Contrat de Service KLS Credit et Guaranty.
                        </p>
                      </li>
                    </ol>
                    <p>
                      La Personne Habilitée, ensemble avec KLS, s’engagent à prendre toutes les mesures afin de garantir le respect du droit de la concurrence. Elles ne devront échanger que
                      les informations nécessaires à la mise en œuvre de la syndication et/ou du suivi de l’opération dans le cadre de l’Agency et/ou des opérations de crédit dans le cadre du
                      produit KLS Credit et Guaranty et s’abstenir d’échanger toute information commercialement sensible en dehors du cadre de ces opérations.
                    </p>
                    <p>
                      Sont considérées comme sensibles des informations individualisées récentes (généralement de moins d’un an) ou futures, de nature non-publique, relatives (i) au prix de
                      vente ou d’achat de biens/services et aux composantes de ces prix (par exemple, marges, commissions, remises,…) ; (ii) à la stratégie commerciale (par exemple, conditions
                      de vente, coûts, clients, investissements, plan marketings, opportunités «<b> business </b>», projets de développement individuel ou en coopération interbancaire,
                      promotions à venir, intentions de réduction ou développement d’activités,…) ; (iii) mais également au positionnement concurrentiel sur le marché (par exemple, parts de
                      marché, chiffres d’affaires, volumes et valeurs de ventes, quantités, capacités, indicateurs de production,…).
                    </p>
                  </section>
                  <section class="article">
                    <h2>Protection des données à caractère personnel</h2>
                    <section class="subsection">
                      <h3>Collecte de données à caractère personnel</h3>
                      <p>
                        KLS, dans ses relations avec les Personnes Habilitées, est amenée à traiter les données à caractère personnel des Personnes Habilitées, lesquelles données lui ont été
                        communiquées soit par l’Etablissement Client, soit par les Personnes Habilitées dans le cadre de leur inscription sur la Plateforme KLS ainsi que dans le cadre de leur
                        utilisation de la Plateforme KLS. Les Personnes Habilitées sont informées que la communication de leurs données à caractère personnel est nécessaire à l’utilisation de
                        la Plateforme KLS. KLS ne procèdera à aucun autre traitement de données à caractère personnel autre que ceux décrits au sein des présentes Conditions Générales
                        d’Utilisation.
                      </p>
                      <h3>Bases légales du traitement de données à caractère personnel</h3>
                      <p>
                        En utilisant la Plateforme KLS, les Personnes Habilitées ont accepté les termes des présentes Conditions Générales d’Utilisation. Ce document formalise une relation
                        contractuelle entre les Personnes Habilitées et KLS servant de base juridique à la collecte et au traitement des données à caractère personnel des Personnes Habilitées
                        par KLS et l’Etablissement Client.
                      </p>
                    </section>
                    <section class="subsection">
                      <h3>Catégories des données à caractère personnel</h3>
                      <p>Les données à caractère personnel que KLS est amenée à collecter et traiter dans le cadre de l’utilisation de la Plateforme KLS sont :</p>
                      <ol>
                        <li>
                          <p>les données d’identification (p. ex. nom, prénom(s), date de naissance) ;</p>
                        </li>
                        <li>
                          <p>les coordonnées (p. ex. téléphone, adresse e-mail, adresse postale) ;</p>
                        </li>
                        <li>
                          <p>les données relatives à la vie professionnelle (p.ex. profession) ;</p>
                        </li>
                        <li>
                          <p>les données de connexion (p. ex. logs).</p>
                        </li>
                      </ol>
                    </section>
                    <section class="subsection">
                      <h3>Finalités du traitement</h3>
                      <p>Ces données sont collectées et traitées par KLS pour les finalités suivantes :</p>
                      <ol>
                        <li>
                          <p>le fonctionnement de la Plateforme KLS ;</p>
                        </li>
                        <li>
                          <p>le suivi de l’utilisation de la Plateforme KLS ;</p>
                        </li>
                        <li>
                          <p>l’envoi d’emails par KLS aux Personnes Habilitées ;</p>
                        </li>
                        <li>
                          <p>vérifier que la Personne Habilitée n’est pas un robot ;</p>
                        </li>
                        <li>
                          <p>présentation commerciale de KLS ;</p>
                        </li>
                      </ol>
                    </section>
                    <section class="subsection">
                      <h3>Les droits des personnes concernées</h3>
                      <p>
                        Conformément à la loi du 6 janvier 1978, modifiée, relative à l’informatique, aux fichiers et aux libertés (ci-après désignée la « Loi n° 78-17») et au Règlement (UE)
                        2016/679 du Parlement européen et du Conseil du 27 avril 2016 relatif à la protection des personnes physiques à l&apos;égard du traitement des données à caractère
                        personnel et à la libre circulation de ces données (ci-après désigné le «<b> RGPD </b>»), les Personnes Habilitées disposent du droit d’accéder à leurs données
                        personnelles, les rectifier, les effacer, demander leur portabilité, définir des directives relatives au sort de ces données après leur décès, demander la limitation de
                        ce traitement, s’y opposer ou retirer leur consentement.
                      </p>
                      <p>L’exercice de ces droits s’effectue à tout moment en écrivant à KLS à l’adresse électronique suivante : dpo@kls-platform.com</p>
                      <p>
                        La Personne Habilitée peut, à tout moment, porter réclamation auprès de la CNIL dont les coordonnées sont disponibles sur son site Internet (
                        <a href="https://www.cnil.fr">www.cnil.fr</a>).
                      </p>
                    </section>
                    <section class="subsection">
                      <h3>Conservation des données</h3>
                      <p>
                        Les données à caractère personnel des Personnes Habilitées qui n’ont participé à aucune opération de Syndication Bancaire ou opération dans le cadre du produit KLS
                        Credit et Gauranty sont conservées uniquement pendant la durée de leur inscription sur la Plateforme KLS. Les données des Personnes Habilitées sont conservées
                        conformément aux informations présentées dans le registre de traitement de KLS.
                      </p>
                      <p>
                        Les données à caractère personnel des Personnes Habilitées qui ont pris part à des opérations de Syndication Bancaire ou opérations de crédit dans le cadre du produit
                        KLS Credit et Guaranty sont conservées pendant la durée des crédits et 5 ans à compter de la suppression de leur compte sur la Plateforme KLS, sauf pour les données
                        collectées pour le suivi de l’utilisation de la Plateforme KLS et le suivi des analytics, et l’envoi d’emails par la Plateforme KLS aux Personnes Habilitées, où la
                        durée de conservation est d’une (1) année à compter de la dernière utilisation de leur Compte Utilisateur, afin de permettre à KLS de respecter ses obligations de
                        transparence.{' '}
                      </p>
                      <p>
                        En cas de procédure contentieuse, toutes informations, documents et pièces contenant des données personnelles des Personnes Habilitées tendant à établir les faits
                        litigieux peuvent être conservés pour la durée de la procédure, y compris pour une durée supérieure à celles indiquées ci-dessus.
                      </p>
                      <p>
                        Certaines données pourront être archivées au-delà des durées prévues pour les besoins de la recherche, de la constatation et de la poursuite des infractions pénales
                        dans le seul but de permettre, en tant que besoins, la mise à disposition de ces données à l’autorité judiciaire.{' '}
                      </p>
                      <p>
                        L&apos;archivage implique que ces données soient anonymisées et ne soient plus consultables en ligne mais soient extraites et conservées sur un support autonome et
                        sécurisé.
                      </p>
                    </section>
                    <section class="subsection">
                      <h3>Destinataires des données</h3>
                      <p>
                        Les données à caractère personnel collectées par le biais de la Plateforme KLS pourront être transférées à des tiers lorsque cela est nécessaire à l’exploitation et à
                        la maintenance de la Plateforme KLS (p. ex. hébergeur de la Plateforme KLS), à la bonne exécution des opérations de Syndication Bancaire (p. ex. prestataire de
                        signature électronique), au suivi des opérations de Syndication Bancaire (p. ex. Etablissement Client), à la conclusion d’opérations de crédit dans le cadre du produit
                        KLS Credit et Guaranty et afin de répondre à une injonction des autorités légales.{' '}
                      </p>
                      <p>
                        A ce titre, lorsque l’entité concernée est située en dehors de l’Union Européenne, ou dans un pays ne disposant pas d’une réglementation adéquate au sens du RGPD, nous
                        encadrons notre relation contractuelle avec cette entité en adoptant un dispositif contractuel approprié.
                      </p>
                    </section>
                  </section>
                  <section class="article">
                    <h2>Suspension/Interruption de l’accès à la Plateforme KLS</h2>
                    <p>
                      KLS se réserve le droit de suspendre l’accès à la Plateforme KLS ou à certaines fonctionnalités de la Plateforme KLS, moyennant un préavis de 48 heures, sans formalité et
                      sans indemnités, par courrier électronique en cas de manquement de la part des Personnes Habilitées à leurs obligations au titre des présentes Conditions Générales
                      d’Utilisation, notamment dans les cas suivants :
                    </p>
                    <ol>
                      <li>
                        <p>
                          un manquement à tout ou partie des stipulations des présentes Conditions Générales d’Utilisation et/ou du Contrat de Service KLS Syndication et/ou du Contrat de
                          Service KLS Credit et Guaranty ;
                        </p>
                      </li>
                      <li>
                        <p>un manquement à la législation applicable ;</p>
                      </li>
                      <li>
                        <p>l’atteinte par la Personne Habilitée aux droits de propriété intellectuelle de KLS et/ou d’un tiers.</p>
                      </li>
                    </ol>
                    <p>
                      KLS se réserve la possibilité d’interrompre, à tout moment, de manière temporaire ou définitive, l’accès à la Plateforme KLS. Dans le cas d’une interruption définitive,
                      les Personnes Habilitées seront informées par tout moyen pertinent déterminé par la Plateforme KLS.
                    </p>
                    <p>
                      KLS ne pourra en aucun cas être tenue responsable à l’encontre de la Personne Habilitée pour la suspension ou l’interruption de l’accès à la Plateforme KLS intervenue
                      dans les conditions prévues au présent article.
                    </p>
                    <p>
                      KLS interrompra, de manière définitive pour toute Personne Habilitée d’un Etablissement Client, l’accès à la Plateforme KLS en cas de cessation, pour quelque raison que
                      ce soit du Contrat de Service KLS Syndication et/ou du Contrat de Service KLS Credit et Guaranty et ce, sans préavis, sans formalité et sans indemnité.
                    </p>
                    <p>
                      La Personne Habilitée devra contacter l’Etablissement Client pour récupérer les données et documents qu’il désire. KLS ne pourra en aucun cas être tenu responsable pour
                      l’effacement de ces documents.
                    </p>
                  </section>
                  <section class="article">
                    <h2>Garantie - Responsabilité</h2>
                    <p>KLS ne fournit aucune autre garantie que celles expressément visées par les présentes Conditions Générales d’Utilisation.</p>
                    <p>
                      En particulier, les Personnes Habilitées sont informées qu’elles sont seules responsables de l’usage qu’elles font de la Plateforme KLS et qu’elles ne pourront obtenir un
                      quelconque dédommagement en cas d’utilisation détournée de la Plateforme KLS.
                    </p>
                    <p>
                      KLS ne saurait être responsable de tout dommage subi par la Personne Habilitée qui résulterait du défaut du respect de tout ou partie des présentes Conditions Générales
                      d’Utilisation, d’une faute de sa part, du fait d’un tiers ou de la survenance d’un cas de Force Majeure.
                    </p>
                    <p>
                      KLS ne saurait être responsable de toute défaillance de matériel, services et/ou installation qu’elle ne fournit pas (directement ou indirectement), quand bien même
                      ceux-ci seraient en relation avec la Plateforme KLS.
                    </p>
                  </section>
                  <section class="article">
                    <h2>Stipulations diverses</h2>
                    <section class="subsection">
                      <h3>Modification des présentes Conditions Générales d’Utilisation</h3>
                      <p>KLS se réserve le droit d’apporter, à tout moment, aux présentes Conditions Générales d’Utilisation toutes les modifications qu’elle jugera nécessaires et utiles.</p>
                      <p>
                        En cas de modification des présentes Conditions Générales d’Utilisation, KLS s’engage à faire accepter à nouveau à la Personne Habilitée les nouvelles conditions
                        générales au moment où il accède à nouveau à la Plateforme KLS.
                      </p>
                      <p>Les Personnes Habilitées n’ayant pas expressément accepté les nouvelles conditions générales ne pourront pas avoir accès à la Plateforme KLS.</p>
                    </section>
                    <section class="subsection">
                      <h3>Force Majeure</h3>
                      <p>
                        Chacune des Parties ne saurait voir sa responsabilité engagée pour le cas où l’exécution de ses obligations serait retardée, restreinte ou rendue impossible du fait de
                        la survenance d’un événement échappant au contrôle de chacune des Parties, qui ne pouvait être raisonnablement prévu lors de la conclusion des Conditions Générales et
                        dont les effets ne peuvent être évités par des mesures appropriée (ci-après désignée la «<b> Force Majeure </b>»).
                      </p>
                      <p>
                        Sont notamment considérée comme Force Majeure, sans que cette liste soit limitative, les évènements suivants : guerre (déclarée ou non) ; acte terroriste ; invasion ;
                        rébellion ; blocus ; sabotage ou acte de vandalisme ; grève ou conflit social, total ou partiel, externe à chacune des Parties ; intempérie (notamment inondations,
                        orages et tempêtes) ; évènement déclaré « catastrophe naturelle » ; incendie ; épidémie ; blocage des moyens de transport ou d’approvisionnement (notamment en énergie)
                        ; défaillance dans la fourniture de l&apos;énergie électrique, du chauffage, de l&apos;air conditionné, des réseaux de télécommunications, du transport des données ;
                        défaillance de satellites.
                      </p>
                    </section>
                    <section class="subsection">
                      <h3>Renonciation</h3>
                      <p>
                        Le fait que l’une ou l’autre des Parties n’exerce pas l’un quelconque de ses droits au titre des présentes ne saurait emporter renonciation de sa part à son exercice,
                        une telle renonciation ne pouvant procéder que d’une déclaration expresse de la Partie concernée.
                      </p>
                    </section>
                    <section class="subsection">
                      <h3>Convention de preuve</h3>
                      <p>
                        Les registres informatisés seront conservés dans les systèmes informatiques de KLS dans des conditions raisonnables de sécurité et seront considérés comme les preuves
                        des échanges intervenus sur la Plateforme KLS ou par courrier électronique.
                      </p>
                    </section>
                    <section class="subsection">
                      <h3>Invalidité partielle</h3>
                      <p>
                        Dans l’hypothèse où une ou plusieurs stipulations des Conditions Générales seraient considérées comme non valides par une juridiction compétente, les autres clauses
                        conserveront leur portée et effet.
                      </p>
                      <p>
                        La stipulation considérée comme invalide sera remplacée par une stipulation dont le sens et la portée seront le plus proches possibles de la clause ainsi invalidée,
                        tout en restant conforme à la législation applicable et à la commune intention des Parties.
                      </p>
                    </section>
                    <section class="article">
                      <h2>Loi applicable - juridiction compétente</h2>
                      <p>Les Conditions Générales sont régies par le droit français.</p>
                      <p class="uppercase">
                        Les Parties acceptent expressément de soumettre tout litige relatif aux Conditions Générales (en ce compris tout différend concernant sa négociation, sa conclusion, son
                        exécution, sa résiliation et/ou sa cessation) et/ou aux relations commerciales entre les Parties ainsi qu’à leur rupture éventuelle, à la compétence exclusive des
                        Tribunaux de Paris, nonobstant pluralité de défendeurs ou appel en garantie, y compris pour les procédures sur requête ou en référé.
                      </p>
                    </section>
                  </section>
            HTML;

        $this->addSql("INSERT INTO core_legal_document VALUES (3, 1, 'CGU', '{$content}', {$publicId}, NOW())");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DELETE FROM core_legal_document WHERE type = 1 ORDER BY added DESC LIMIT 1');
    }
}
