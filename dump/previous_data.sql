INSERT INTO settings (id_setting, type, value, added) VALUES
(1, 'Echecs login avant affichage captcha', '1', NOW()),
(2, 'SERVICE_TERMS_PAGE_ID', '1', NOW());

INSERT INTO translations (locale, section, name, translation, added) VALUES
('fr_FR', 'mail-identity-updated', 'firstName', 'Prénom', NOW()),
('fr_FR', 'mail-identity-updated', 'lastName', 'Nom', NOW()),
('fr_FR', 'mail-identity-updated', 'jobFunction', 'Fonction professionnelle', NOW()),
('fr_FR', 'mail-identity-updated', 'mobile', 'Téléphone mobile', NOW()),
('fr_FR', 'mail-identity-updated', 'phone', 'Téléphone fixe', NOW()),
('fr_FR', 'mail-identity-updated', 'content-message-plural', 'Ces informations personnelles ont été modifiées :', NOW()),
('fr_FR', 'mail-identity-updated', 'content-message-singular', 'Cette information personnelle a été modifiée :', NOW()),
('fr_FR', 'market-segment', 'corporate', 'Entreprises', NOW()),
('fr_FR', 'market-segment', 'public_collectivity', 'Collectivités publiques', NOW()),
('fr_FR', 'market-segment', 'agriculture', 'Agriculture', NOW()),
('fr_FR', 'market-segment', 'real_estate_development', 'Promotion immobilière', NOW()),
('fr_FR', 'market-segment', 'ppp', 'PPP', NOW()),
('fr_FR', 'market-segment', 'energy', 'EnR', NOW()),
('fr_FR', 'market-segment', 'patrimonial', 'Patrimonial', NOW()),
('fr_FR', 'market-segment', 'pro', 'Pro', NOW()),
('fr_FR', 'staff-roles', 'duty_staff_operator', 'opérationnel', NOW()),
('fr_FR', 'staff-roles', 'duty_staff_manager', 'coordinateur', NOW()),
('fr_FR', 'staff-roles', 'duty_staff_admin', 'administrateur', NOW()),
('fr_FR', 'staff-roles', 'duty_staff_accountant', 'contact de facturation', NOW()),
('fr_FR', 'staff-roles', 'duty_staff_signatory', 'signataire', NOW()),
('fr_FR', 'staff-roles', 'duty_staff_auditor', 'Auditeur', NOW());

INSERT INTO foncaris_funding_type (id, category, description) VALUES
(1, 1, 'Garantie à 1ère Demande'),
(2, 1, 'Caution Loyer'),
(3, 1, 'Caution Soumission'),
(4, 1, 'Caution de restitution d’acompte'),
(5, 1, 'Caution Fiscale & Douane'),
(6, 1, 'Caution Garantie d’Achèvement'),
(7, 1, 'Caution groupe CA (Servicing)'),
(8, 1, 'Autre Cautions'),
(9, 2, 'Découvert Confirmé'),
(10, 2, 'Découvert Non Confirmé'),
(11, 2, 'CT Billet'),
(12, 2, 'Crédit de Campagne'),
(13, 2, 'Crédoc'),
(14, 2, 'Avance en devise'),
(15, 2, 'CT TVA / Subvention'),
(16, 2, 'Stand By'),
(17, 2, 'CT Financement opérations de marché'),
(18, 2, 'Autre CT'),
(19, 3, 'Crédit : Ouverture de Crédit Moyen Terme (RCF)'),
(20, 3, 'Crédit : Crédit Stand By Amortissable'),
(21, 3, 'Crédit : Crédit Stand By InFine'),
(22, 3, 'Prêt décaissé Amortissable'),
(23, 3, 'Prêt décaissé InFine'),
(24, 3, 'Prêt avec Déblocage Successif Amortissable'),
(25, 3, 'Prêt avec Déblocage Successif InFine'),
(26, 3, 'Prêt Participatif Subordonné / Amortissable'),
(27, 3, 'Prêt Participatif Subordonné / InFine'),
(28, 3, 'Placement Privé'),
(29, 3, 'Autre Prêts'),
(30, 3, 'Crédit Bail Mobilier (CBM)'),
(31, 3, 'Crédit Bail Immobilier (CBI)');

INSERT INTO foncaris_security (id, category, description) VALUES
(1, 1, 'CAUT.SOLID.PARTIEL.P.PHYS.'),
(2, 1, 'CAUT.SOLIDAIRE'),
(3, 1, 'CAUT.DES.ADMINISTRATEURS'),
(4, 1, 'CAUT.PERSONNE(S) MORALE(S)'),
(5, 1, 'ENGAGEMENT SOLID.ASSOCIES'),
(6, 1, 'CAUT.HYPOTHECAIRE SCI'),
(7, 1, 'CAUT.GAGISTE FDS COMMERCE'),
(8, 1, 'CAUT.GROUPEMENT AGRICOLE'),
(9, 1, 'AUTRES CAUTIONS'),
(10, 1, 'AVAL'),
(11, 1, 'AVAL DU DIRIGEANT'),
(12, 1, 'COFACE'),
(13, 1, 'LETTRE D''INTENTION'),
(14, 1, 'BPIFRANCE'),
(15, 1, 'AVAL FRANCEAGRIMER'),
(16, 1, 'CAUTION COFACE'),
(17, 1, 'ETABL.FINANCIERS'),
(18, 1, 'AUTRE ORGANISMES'),
(19, 2, 'DELEGATION DES LOYERS'),
(20, 2, 'DELEGATION DE CREANCES'),
(21, 2, 'CESSION CREANCES (LOI DAILLY) NON NOTIFIEE'),
(22, 2, 'DAILLY GARANTIE'),
(23, 2, 'CESSION CREANCE DAILLY NOTIFIEE'),
(24, 2, 'DELEGATION D’ASSURANCE'),
(25, 2, 'QUITTANCE SUBROGATIVE'),
(26, 3, 'HYPOT.CONSENTIE PAR 1 TIERS'),
(27, 3, 'HYPOTHEQUE CONVENTIONNELLE'),
(28, 3, 'HYPOTHEQUE MARITIME'),
(29, 3, 'HYPOTHEQUE AERIENNE'),
(30, 3, 'PROM.AFFECT.HYPOTHECAIRE'),
(31, 3, 'HYPOTHEQUE NON INSCRITE'),
(32, 3, 'HYPOTHEQUE + PPD'),
(33, 3, 'MANDAT D’HYPOTHEQUER'),
(34, 3, 'SUB.PRIVILEGE DE CO-PARTAGEANT'),
(35, 3, 'HYPOTHEQUE FLUVIALE'),
(36, 3, 'HYPOTHEQUE EN PARTAGE DE RANG'),
(37, 3, 'AUTRE HYPOTHEQUE'),
(38, 3, 'PRIVILEGE PRETEUR DENIER'),
(39, 3, 'SUBROG.PRIV.VENDEUR HYP.'),
(40, 3, 'PPD EN PARTAGE DE RANG'),
(41, 3, 'PRIVILEGE DE NEW MONEY'),
(42, 3, 'AUTRE PRIVILEGE'),
(43, 3, 'SUBROG.PRIV.VENDEUR'),
(44, 4, 'NANTIS.MATERIELS/OUTILLAGE'),
(45, 4, 'NANTIS.DE MARCHES PUBLICS'),
(46, 4, 'NANTIS.FONDS COMMERCE'),
(47, 4, 'NANTIS.ASSURANCE CREDIT'),
(48, 4, 'NANTIS.TITRES: AUT.ORGAN.'),
(49, 4, 'NANTISSEMENT DE PARTS'),
(50, 4, 'ENGAG.BLOCAGE CPTE COURANT'),
(51, 4, 'WARRANT'),
(52, 4, 'NANTIS.DE PARTS SCI'),
(53, 4, 'ENGAGEMENT VITICOLE'),
(54, 4, 'NANTIS.ACTION SOCIETES'),
(55, 4, 'NANTIS.PARTS STES CIVILES'),
(56, 4, 'NANTIS.PARTS STES COMMERCIALES'),
(57, 4, 'WARRANT MATERIEL'),
(58, 4, 'AITRE NANTISSEMENT'),
(59, 4, 'GAGE SUR VEHICULE'),
(60, 4, 'GAGE SUR STOCKS'),
(61, 4, 'GAGE ESPECES');

INSERT INTO legal_document (id, type, title, content, first_time_instruction, differential_instruction, added, public_id) VALUES (1, 1, 'Conditions générales d‘utilisation', '<p>Les présentes conditions générales d’utilisation (ci-après désignées les « Conditions Générales ») régissent les
    relations entre la société Crédit Agricole Lending Services, société par actions simplifiée à associé unique au
    capital de 30.000 euros, immatriculée au registre du commerce et des sociétés de Paris sous le numéro 850 890 666,
    dont le siège social est situé au 50 rue de la Boétie, 75008 Paris (ci-après désignée « Crédit Agricole Lending
    Services » ou « CALS ») et toute personne physique qui utilise la Plateforme KLS dans le cadre de son activité
    professionnelle (ci-après désignée l’ « Utilisateur »).</p>

<p>La création d’un compte Utilisateur Autorisé sur la Plateforme KLS implique l’acceptation pleine et entière des
    Conditions Générales au moyen d’une case à cocher, dont l’Utilisateur reconnaît avoir pris connaissance, les avoir
    comprises et acceptées en parfaite connaissance de cause.</p>

<p>CALS et l’Utilisateur sont ci-après dénommés individuellement une « Partie » et collectivement les « Parties ». </p>

<section id="definitions">
    <h2>Définitions</h2>
    <p>Les termes et expressions dont la première lettre de chaque mot est en majuscule ont, au sein des Conditions
        Générales, la signification qui leur est attribuée ci-après, qu’ils soient utilisés au singulier ou au
        pluriel.</p>

    <ol>
        <li>« <strong>Client</strong> » désigne l’Établissement Autorisé (i) au sein duquel les Responsables et
            Utilisateurs Salariés
            sont salariés ; et/ou (ii) pour lequel le Prestataire intervient ; et/ou (iii) qui a invité l’Utilisateur
            Invité
        </li>
        <li> « <strong>Établissement Autorisé</strong> » désigne tout établissement habilité à effectuer des opérations
            de crédit au sens
            des articles L 511-5 et L 511-6 du Code Monétaire et Financier, en ce inclus les établissements de crédit et
            fonds d’investissement, qui détient un compte utilisateurs sur la Plateforme KLS et qui peut, par le biais
            de la Plateforme KLS, être amené à participer à une opération de Syndication Bancaire en qualité d’Arrangeur
            ou de Participant. L’Établissement Autorisé est représenté, dans le cadre de l’utilisation de la Plateforme
            KLS, par un Responsable qui lui-même peut désigner des Utilisateurs Invités pour intervenir sur la
            Plateforme KLS. Seul l’Établissement Autorisé est Client.
        </li>
        <li>« <strong>Force Majeure</strong> » désigne un évènement extérieur aux Parties, imprévisible et irrésistible,
            tel que
            défini par la jurisprudence des tribunaux française, en ce compris : guerre (déclarée ou non) ; acte
            terroriste ; invasion ; rébellion ; blocus ; sabotage ou acte de vandalisme ; grève ou conflit social, total
            ou partiel, externe à chacune des Parties ; intempérie (notamment inondations, orages et tempêtes) ;
            évènement déclaré « catastrophe naturelle » ; incendie ; épidémie ; blocage des moyens de transport ou
            d’approvisionnement (notamment en énergie) ; défaillance dans la fourniture de l’énergie électrique, du
            chauffage, de l’air conditionné, des réseaux de télécommunications, du transport des données ; défaillance
            de satellites.
        </li>
        <li>« <strong>Module</strong> » désigne un ensemble cohérent de fonctionnalités disponibles sur la Plateforme
            KLS que le
            Client demeure libre d’activer, via son Responsable. L’Utilisateur ne peut accéder à un Module que s’il a
            préalablement été activé. A compter de son activation par le Client, le Module sera activé pour la durée de
            la période contractuelle en cours et facturé au prix défini dans le présent Contrat. Des Modules
            supplémentaires sont susceptibles d’être ajouté à la Plateforme KLS. Ils seront portés à la connaissance du
            Client qui restera libre de les activer ou non. Si le Client est intéressé par un Module supplémentaire
            disponible sur la plateforme KLS mais qu’il n’a pas encore activé dans le cadre du Contrat, le Client devra
            conclure via son Responsable, un avenant au Contrat qui précisera le prix et les fonctionnalités du Module
            concerné.
        </li>
        <li>« <strong>Module Arrangeur</strong> » désigne le Module qui permet à un Établissement Autorisé d’agir en
            qualité
            d’Arrangeur et de proposer, via la Plateforme KLS, une opération de Syndication Bancaire à un, plusieurs, ou
            un groupement, d’Établissement(s) Autorisé(s) ou d’Établissement(s) Invité(s). L’activation du Module
            Arrangeur enclenche la facturation de la Redevance indiquée au Contrat.
        </li>
        <li>« <strong>Module Participant</strong> » désigne le Module qui permet à un Établissement Autorisé de répondre
            à une offre
            de participation à une opération de Syndication Bancaire organisée sur la Plateforme KLS. L’activation du
            Module Participant enclenche la facturation de l’Abonnement Participant indiquée au Contrat.
        </li>
        <li>« <strong>Plateforme KLS</strong> » désigne la solution logicielle éditée par CALS, laquelle permet de
            mettre en relation
            des Établissements Autorisés et/ou des Utilisateurs Invités afin qu’ils réalisent entre eux des opérations
            de Syndication Bancaire.
        </li>
        <li>« <strong>Responsable</strong> » désigne, au sein des Utilisateurs Autorisés, une personne physique salariée
            du Client qui
            est habilitée par ce dernier à activer les différents modules de la Plateforme KLS, à gérer les
            habilitations d’accès à la Plateforme KLS et à créer des comptes aux Utilisateurs Salariés, aux Utilisateurs
            Invités et aux Utilisateurs Prestataires.
        </li>
        <li>« <strong>Syndication Bancaire</strong> » désigne la réunion de deux ou plusieurs Établissements Autorisés
            ayant pour objet
            de partager le risque, le financement et/ou la rémunération d’un prêt.
        </li>
        <li>« <strong>Utilisateur Autorisé</strong> » désigne une personne physique identifiée et habilitée à accéder à
            la Plateforme KLS
            au nom et pour le compte d’un Établissement Autorisé, d’un Prestataire ou d’un Établissement Invité et à
            utiliser les Services conformément aux stipulations du Contrat. Un Utilisateurs Autorisé peut avoir le
            statut de Responsable, d’Utilisateur Salarié, d’Utilisateur Prestataire ou d’Utilisateur Invité en fonction
            de l’entité au nom de laquelle il agit.
        </li>
        <li>« <strong>Utilisateur Invité</strong> » désigne, parmi les Utilisateurs Autorisés, une personne physique,
            agissant au nom et
            pour le compte d’un Établissement Invité, qui est habilité par un Responsable à accéder à la Plateforme KLS
            pour consulter les offres de participation à une opération de Syndication Bancaire.
        </li>
        <li>« <strong>Utilisateur Prestataire</strong> » désigne, parmi les Utilisateurs Autorisés, une personne
            physique agissant au nom
            et pour le compte d’un Prestataire d’un Établissement Autorisé ou d’un Établissement Invité, qui est
            habilité par le Responsable à accéder à la Plateforme KLS.
        </li>
        <li>« <strong>Utilisateur Salarié</strong> » désigne, parmi les Utilisateurs Autorisés, une personne physique
            salariée du Client
            ou de l’Établissement Invité qui est habilitée par le Responsable à accéder à la Plateforme KLS.
        </li>
        <li>« <strong>Compte Utilisateur</strong> » désigne le compte qui permet à tout Utilisateur Autorisé de
            bénéficier, après avoir
            renseigné des informations obligatoires le concernant, d’un accès à la Plateforme KLS sans obligation d’en
            utiliser les fonctionnalités.
        </li>
    </ol>
</section>

<section id="object">
    <h2>Objet</h2>
    <p>Les Conditions Générales définissent les conditions dans lesquelles les Utilisateurs Autorisés peuvent accéder et
        utiliser la Plateforme KLS.</p>
</section>

<section id="platformAccess">
    <h2>Accès à la platforme KLS</h2>
    <p>Afin de pouvoir accéder à la Plateforme KLS, les Utilisateurs Autorisés, doivent y avoir été autorisés par le
        Responsable et être titulaire d’un compte Utilisateur.</p>
    <p>Afin de pouvoir utiliser la Plateforme KLS, l’Utilisateur Autorisé s’engage expressément à être habilité par le
        Client dont il est salarié à réaliser des opérations de Syndication Bancaire en son nom et pour son compte. </p>
    <p>Enfin, l’Utilisateur Autorisé déclare et garantit être soumis à une obligation de confidentialité afin de pouvoir
        accéder à la Plateforme KLS.</p>
</section>

<section id="authorizedPeopleAccounts">
    <h2>Compte des utilisateurs autorisés</h2>
    <p>Le Responsable crée les comptes Utilisateurs Autorisés.</p>

    <p>Lorsque le Responsable crée un compte Utilisateur, l’Utilisateur Autorisé concerné reçoit un email sur son
        adresse électronique professionnelle.</p>
    <p>Afin de finaliser la création de son Compte Utilisateur, l’Utilisateur Autorisé concerné doit suivre les
        instructions précisées sur l’email réceptionné.</p>
    <p>Pour achever la création de son Compte, l’Utilisateur Autorisé doit accepter les Conditions Générales.</p>
    <p>Dans le cadre de la création de son Compte sur la Plateforme KLS, il sera demandé à l’Utilisateur Autorisé de
        choisir un mot de passe. Pour des raisons de sécurité et de confidentialité, il est recommandé à l’Utilisateur
        Autorisé de choisir des mots de passe composés de plusieurs types de caractères, et de le modifier
        régulièrement.</p>
    <p>L’identifiant et le mot de passe sont uniques et personnels. Ils ne doivent pas être divulgués à des tiers.
        Toute utilisation de la Plateforme KLS réalisée au moyen de l’identifiant et du mot de passe d’un Utilisateur
        Autorisé sera réputée avoir été réalisée par ledit Utilisateur Autorisé. En cas de divulgation de son
        identifiant et
        mot de passe, l’Utilisateur Autorisé doit contacter dans les plus brefs délais le Responsable ou le support
        informatique de CALS à l’adresse e-mail : support@ca-lendingservices.com afin que ceux-ci soient désactivés.</p>
    <p>Le compte personnel de chaque Utilisateur Autorisé lui permet de mettre à jour les données le concernant.</p>
    <p>Le <strong>compte personnel du Responsable</strong> lui permet d’activer les différents Modules, de créer des
        comptes pour les
        Utilisateurs Autorisés ainsi que d’accéder à la Plateforme KLS afin de consulter les opérations de Syndication
        Bancaire disponibles proposées ainsi que d’y participer.</p>
    <p>Le <strong>Compte Utilisateur Salarié</strong> lui permet d’initier ou de participer à des opérations de
        Syndication Bancaire sur
        le Marché Primaire et sur le Marché Secondaire pour le compte de l''Utilisateur Autorisé qu’il représente.</p>
    <p>Le <strong>Compte Utilisateur de l’Utilisateur Invité</strong> lui permet uniquement de consulter les opérations
        de Syndication
        Bancaire auxquelles il a été invité, de prendre connaissance de la documentation y afférente, de participer à
        une opération de Syndication Bancaire ainsi que de suivre ladite opération. Pour pouvoir accéder à l’intégralité
        des fonctionnalités de la Plateforme KLS, l’Utilisateur Invité devra conclure un contrat commercial avec CALS.
    </p>
    <p>Le <strong>Compte Utilisateur de l’Utilisateur Prestataire</strong> lui permet uniquement de consulter les
        documents pertinents de
        l’opération de Syndication Bancaire dans laquelle il a vocation à intervenir.</p>
    <p>Les Utilisateurs Autorisés sont informés et acceptent que leurs droits d’accès et d’utilisation de la Plateforme
        KLS varient en fonction des dossiers de Syndication Bancaire qu’ils sont ou non intéressés à connaître.</p>
</section>

<section id="platformUsage">
    <h2>Utilisation de la platforme KLS</h2>
    <section>
        <h3>Responsables et Utilisateurs Salariés</h3>
        <p>Selon les Services souscrits par le Client, les Responsables et Utilisateurs Salariés peuvent, via la
            Plateforme KLS :</p>
        <ol type="i">
            <li>initier une opération de Syndication Bancaire ;</li>
            <li>participer à une opération de Syndication Bancaire</li>
        </ol>
        <p>Les Responsables et Utilisateurs Salariés peuvent suivre, via leur compte, les opérations de Syndication
            Bancaire auxquelles ils souhaitent participer et participent. </p>
        <p>Les Utilisateurs Salariés s’engagent à être autorisés par le Client à réaliser et participer à des opérations
            de Syndication Bancaire.</p>
    </section>
    <section>
        <h3>Utilisateurs Invités</h3>
        <p>Les Utilisateurs Invités peuvent uniquement accéder à la Plateforme KLS (i) pour visualiser les opérations de
            Syndication Bancaire auxquelles ils sont invités et consulter la documentation y afférente et, le cas
            échéant (ii) sous réserve de la conclusion d’un contrat commercial avec CALS, de faire une offre de
            participation sur ces opérations de Syndication Bancaire et suivre leur évolution.</p>
    </section>
    <section>
        <h3>Utilisateurs Prestataires</h3>
        <p>Les Utilisateurs Prestataires peuvent accéder à la Plateforme KLS uniquement pour prendre connaissance des
            documents qui leur sont nécessaires afin de mener à bien la mission qui leur a été confiée par le
            Client. </p>
        <p>Les Utilisateurs Prestataires ne sont pas autorisés à participer à des opérations de Syndication Bancaire via
            la Plateforme KLS.</p>
    </section>
</section>

<section id="usagePlatformLicence">
    <h2>License d''utilisation de la platforme KLS</h2>
    <section>
        <h3>Droit d’utilisation</h3>
        <p>CALS accorde un droit d’utilisation personnel, non exclusif, non cessible et sans droit de licence, de la
            Plateforme KLS à l’Utilisateur Autorisé dans les limites et conditions spécifiées aux Conditions Générales,
            pour le monde entier et pour toute la durée pendant laquelle le compte de l’Utilisateur est actif sur la
            Plateforme KLS.</p>
    </section>
    <section>
        <h3>Limitations</h3>
        <p>L’Utilisateur Autorisé s’interdit, directement ou indirectement, sauf accord exprès, préalable et écrit de
            CALS : </p>
        <ol type="i">
            <li>de décompiler, désassembler la Plateforme KLS, de pratiquer l’ingénierie inverse ou de tenter de
                découvrir ou reconstituer le code source, les idées qui en sont la base, les algorithmes, les formats
                des fichiers ou les interfaces de programmation ou d’interopérabilité de la Plateforme KLS sauf dans la
                limite du droit accordé par l’article L. 122-6-1 du code de la propriété intellectuelle, de quelque
                manière que ce soit. Au cas où l’Utilisateur Autorisé souhaiterait obtenir les informations permettant
                de mettre en œuvre l’interopérabilité de la Plateforme KLS avec un autre logiciel développé ou acquis de
                manière indépendante par, l’Utilisateur Autorisé et ce pour un emploi conforme à la destination de la
                Plateforme KLS, l’Utilisateur Autorisé s’engage, avant de faire appel à un tiers, à consulter
                préalablement CALS qui pourra lui fournir les informations nécessaires à la mise en œuvre de cette
                interopérabilité. Le coût exact engendré en interne chez CALS pour la fourniture de ces informations
                sera facturé par CALS au Client ;
            </li>
            <li>de procéder seul, ou avec l’aide d’un tiers prestataire, à la correction des éventuelles erreurs de la
                Plateforme KLS pour le rendre conforme à sa destination, CALS se réservant seul l’exercice de ce droit
                conformément à l’article L. 122-6-1-I du code de la propriété intellectuelle ;
            </li>
            <li>de supprimer ou modifier toute référence ou indication relative aux droits de propriété de CALS ou de
                tout tiers ;
            </li>
            <li>de transférer, utiliser ou exporter la Plateforme KLS en violation de la réglementation en vigueur ;
                d’intégrer ou d’associer la Plateforme KLS avec d’autres logiciels ou documents ou de créer des œuvres
                composites ou dérivées avec l’aide de tout ou partie de la Plateforme KLS ;
            </li>
            <li>d’effectuer toute autre utilisation de la Plateforme KLS que celle permise dans le cadre des Conditions
                Générales et/ou du Contrat de Service.
            </li>
        </ol>
    </section>
</section>
<section id="authorizedPeopleEngagement">
    <h2>Engagements des utilisateurs autorisés</h2>
    <p>En l’absence d’autorisation préalable et écrite de CALS, il est interdit :</p>
    <ol type="i">

        <li>D’utiliser la Plateforme KLS autrement que de bonne foi, et conformément à l’objet des présentes
            Conditions Générales d’Utilisation, pour faciliter les interventions de CALS ;
        </li>
        <li>de charger ou transmettre sur la Plateforme KLS ou utiliser tout équipement, logiciel ou routine qui
            contienne des virus, chevaux de Troie, vers, bombes à retardement ou autres programmes et procédés
            destinés à endommager, interférer ou tenter d’interférer avec le fonctionnement normal de la Plateforme
            KLS, ou s’approprier la Plateforme KLS, ou encore recourir à n’importe quel moyen pour causer une
            saturation des systèmes de CALS ou porter atteinte aux droits de tiers.
        </li>

    </ol>
    <p>Il est rappelé que les articles 323-1 et suivants du code pénal sanctionnent par des peines allant jusqu’à
        cinq (5) ans d’emprisonnement et 150.000 euros d’amende, notamment :</p>
    <ol type="i">
        <li>l’accès et le maintien frauduleux dans un système de traitement automatisé de données ;
        <li>la suppression, la modification ou l’ajout frauduleux de données dans ce système ;
        <li>le fait d’entraver ce système.
    </ol>
    <p>Les Utilisateurs Autorisés déclarent et garantissent :</p>
    <ol type="i">
        <li>disposer des autorisations nécessaires pour réaliser des opérations de Syndication Bancaire ;</li>
        <li>posséder les pouvoirs nécessaires dans la chaîne délégataire et hiérarchique du Client, dont ils sont
            salariés, pour participer à une opération de Syndication Bancaire au nom et pour le compte de leur
            employeur ;
        </li>
        <li>être pleinement autorisés par leur supérieur hiérarchique à participer à des opérations de Syndication
            Bancaire et à engager leur employeur dans le cadre d’opérations de Syndication Bancaire ;
        </li>
        <li>être habilités au sein de leur propre structure à procéder à chacune des actions et des opérations
            réalisées sur la Plateforme KLS ;
        </li>
        <li>respecter l’ensemble des dispositions législatives, règlementaires et déontologiques relatives à la
            lutte contre le blanchiment de capitaux et le financement du terrorisme ;
        </li>
        <li>ne pas transmettre par le biais de la Plateforme KLS des contenus à caractère illicite, ou tout autre
            message qui pourrait constituer un crime ou un délit, engager la responsabilité civile, porter atteinte
            à la législation ou inciter au faire, ou encore des contenus qui pourraient être utilisés à toute fin
            contraire à la loi ou aux présentes Conditions Générales ;
        </li>
        <li>ne pas réaliser par le biais de la Plateforme KLS des opérations illicites qui pourraient constituer un
            crime ou un délit, engager la responsabilité civile, porter atteinte à la législation ou inciter au
            faire, ou encore des contenus qui pourraient être utilisés à toute fin contraire à la loi ou aux
            présentes Conditions Générales.
        </li>
    </ol>
    <p>L’Utilisateur autorisé, ensemble avec CALS, s’engagent à prendre toutes les mesures afin de garantir le
        respect du droit de la concurrence. Elles ne devront échanger que les informations nécessaires à la mise en
        œuvre de la syndication et s’abstenir d’échanger toute information commercialement sensible en dehors du
        cadre de ladite syndication.</p>
    <p>Sont considérées comme sensibles des informations individualisées récentes (généralement de moins d’un an) ou
        futures, de nature non-publique, relatives (i) au prix de vente ou d’achat de biens/services et aux
        composantes de ces prix (par exemple, marges, commissions, remises,…) ; (ii) à la stratégie commerciale (par
        exemple, conditions de vente, coûts, clients, investissements, plan marketings, opportunités « business »,
        projets de développement individuel ou en coopération interbancaire, promotions à venir, intentions de
        réduction ou développement d’activités,…) ; (iii) mais également au positionnement concurrentiel sur le
        marché (par exemple, parts de marché, chiffres d’affaires, volumes et valeurs de ventes, quantités,
        capacités, indicateurs de production,…).</p>
</section>
<section id="personalDataProtection">
    <h2>Protection des données à caractère personnel</h2>
    <section>
        <h3>Collecte de données à caractère personnel</h3>
        <p>CALS, dans ses relations avec les Utilisateurs Autorisés, est amenée à traiter les données à caractère
            personnel des Utilisateurs Autorisés, lesquelles données lui ont été communiquées soit par le Client,
            soit par les Utilisateurs Autorisés dans le cadre de leur inscription sur la Plateforme KLS ainsi que
            dans le cadre de leur utilisation de la Plateforme KLS.</p>
        <p>Les Utilisateurs Autorisés sont informés que la communication de leurs données à caractère personnel est
            nécessaire à l’utilisation de la Plateforme KLS. </p>
        <p>CALS ne procèdera à aucun autre traitement de données à caractère personnel autre que ceux décrits au
            sein des Conditions Générales.</p>
    </section>
    <section>
        <h3>Bases légales du traitement de données à caractère personnel</h3>
        <p>En utilisant la Plateforme KLS, les Utilisateurs Autorisés ont accepté les termes des Conditions
            Générales.</p>
        <p>Ce document formalise une relation contractuelle entre les Utilisateurs Autorisés et CALS servant de base
            juridique à la collecte et au traitement des données à caractère personnel des Utilisateurs Autorisés
            par CALS et le Client.</p>
    </section>
    <section>
        <h3>Catégories des données à caractère personnel</h3>
        <p>Les données à caractère personnel que CALS est amenée à collecter et traiter dans le cadre de
            l’utilisation de la Plateforme KLS sont :</p>
        <section>
            <ol type="i">

                <li>les données d’identification (p. ex. nom, prénom(s), date de naissance) ;</li>
                <li>les coordonnées (p. ex. téléphone, adresse e-mail, adresse postale) ;</li>
                <li>les données relatives à la vie professionnelle (p.ex. profession) ;</li>
                <li>les données de connexion (p. ex. logs).</li>

            </ol>
        </section>
    </section>
    <section>
        <h3>Finalités du traitement</h3>
        <p>Ces données sont collectées et traitées par CALS pour les finalités suivantes :</p>
        <ol type="i">

            <li>le fonctionnement de la Plateforme KLS ;</li>
            <li>le suivi de l’utilisation de la Plateforme KLS ;</li>
            <li>l’envoi d’emails par la Plateforme KLS aux Utilisateurs ;</li>
            <li>vérifier que l’Utilisateur n’est pas un robot ;</li>
            <li>la signature électronique de documents.</li>

        </ol>
    </section>
    <section>
        <h3>Les droits des personnes concernées</h3>
        <p>Conformément à la loi du 6 janvier 1978, modifiée, relative à l’informatique, aux fichiers et aux
            libertés (ci-après désignée la « Loi n° 78-17») et au Règlement (UE) 2016/679 du Parlement européen et
            du Conseil du 27 avril 2016 relatif à la protection des personnes physiques à l''égard du traitement des
            données à caractère personnel et à la libre circulation de ces données (ci-après désigné le « RGPD »),
            les Utilisateurs Autorisés disposent du droit d’accéder à leurs données personnelles, les rectifier, les
            effacer, demander leur portabilité, définir des directives relatives au sort de ces données après leur
            décès, demander la limitation de ce traitement, s’y opposer ou retirer leur consentement.</p>
        <p>L’exercice de ces droits s’effectue à tout moment en écrivant à CALS à l’adresse électronique suivante :
            <a href="mailto:dpo@ca-lendingservices.com">dpo@ca-lendingservices.com</a>.</p>
        <p>L’Utilisateur Autorisé peut, à tout moment, porter réclamation auprès de la CNIL dont les coordonnées
            sont disponibles sur son site Internet (<a href="https://www.cnil.fr">www.cnil.fr</a>).</p>
    </section>
    <section>
        <h3>Conservation des données</h3>
        <p>Les données à caractère personnel des Utilisateurs Autorisés qui n’ont participé à aucune opération de
            Syndication Bancaire sont conservées uniquement pendant la durée de leur inscription sur la Plateforme
            KLS. Les données des Utilisateurs Autorisés sont conservées conformément aux informations présentées
            dans le registre de traitement de CALS.</p>
        <p>Les données à caractère personnel des Utilisateurs Autorisés qui ont pris part à des opérations de
            Syndication Bancaire sont conservées pendant la durée des crédits et 5 ans à compter de la suppression
            de leur compte sur la Plateforme KLS, sauf pour les données collectées pour le suivi de l’utilisation de
            la Plateforme KLS et analytics, et l’envoi d’emails par la Plateforme KLS aux Utilisateurs, où la durée
            de conservation est d’une année à compter de la dernière utilisation du compte, afin de permettre à CALS
            de respecter ses obligations de transparence.</p>
        <p>En cas de procédure contentieuse, toutes informations, documents et pièces contenant des données
            personnelles des Utilisateurs Autorisés tendant à établir les faits litigieux peuvent être conservés
            pour la durée de la procédure, y compris pour une durée supérieure à celles indiquées ci-dessus.</p>
        <p>Certaines données pourront être archivées au-delà des durées prévues pour les besoins de la recherche, de
            la constatation et de la poursuite des infractions pénales dans le seul but de permettre, en tant que
            besoins, la mise à disposition de ces données à l’autorité judiciaire.</p>
        <p>L''archivage implique que ces données soient anonymisées et ne soient plus consultables en ligne mais
            soient extraites et conservées sur un support autonome et sécurisé.</p>
    </section>
    <section>
        <h3>Destinataires des données</h3>
        <p>Les données à caractère personnel collectées par le biais de la Plateforme KLS pourront être transférées
            à des tiers lorsque cela est nécessaire à l’exploitation et à la maintenance de la Plateforme KLS (p.
            ex. hébergeur de la Plateforme KLS), à la bonne exécution des opérations de Syndication Bancaire (p. ex.
            prestataire de signature électronique), au suivi des opérations de Syndication Bancaire (p. ex. Client,
            autres Établissements Autorisés) et afin de répondre à une injonction des autorités légales.</p>
        <p>A ce titre, lorsque l’entité concernée est située en dehors de l’Union Européenne, ou dans un pays ne
            disposant pas d’une réglementation adéquate au sens du RGPD, nous encadrons notre relation contractuelle
            avec cette entité en adoptant un dispositif contractuel approprié.</p>
    </section>
</section>
<section id="platformAccessSuspension">
    <h2>Suspension/interruption de l’accès a la plateforme kls</h2>
    <p>CALS se réserve le droit de suspendre l’accès à la Plateforme KLS ou à certaines fonctionnalités de la Plateforme
        KLS, moyennant un préavis de 48 heures, sans formalité et sans indemnités, par courrier électronique en cas de
        manquement de la part des Utilisateurs Autorisés à leurs obligations au titre des Conditions Générales,
        notamment dans les cas suivants :
    <ol type="i">

        <li>un manquement à tout ou partie des stipulations des Conditions Générales ;</li>
        <li>un manquement à la législation applicable ;</li>
        <li>l’atteinte par l’Utilisateur Autorisé aux droits de propriété intellectuelle de CALS et/ou d’un tiers.</li>
    </ol>
    <p>CALS se réserve la possibilité d’interrompre, à tout moment, de manière temporaire ou définitive, l’accès à la
        Plateforme KLS. Dans le cas d’une interruption définitive, les Utilisateurs Autorisés seront informés par tout
        moyen pertinent déterminé par la Plateforme KLS.</p>
    <p>CALS ne pourra en aucun cas être tenue responsable à l’encontre de l’Utilisateur Autorisé pour la suspension ou
        l’interruption de l’accès à la Plateforme KLS intervenue dans les conditions prévues au présent article.</p>
    <p>CALS interrompra, de manière définitive pour tout Utilisateur Autorisé d’un Client, l’accès à la Plateforme KLS
        en cas de cessation, pour quelque raison que ce soit du Contrat de Service et ce, sans préavis, sans formalité
        et sans indemnité.</p>
    <p>L’Utilisateur Autorisé devra contacter le Client pour récupérer les données et documents qu’il désire. CALS ne
        pourra en aucun cas être tenu responsable pour l’effacement de ces documents.</p>
</section>
<section id="warranty">
    <h2>Garantie-Responsabilité</h2>
    <p>CALS ne fournit aucune autre garantie que celles expressément visées par les Conditions Générales.</p>
    <p>En particulier, les Utilisateurs Autorisés sont informés qu’ils sont seuls responsables de l’usage qu’ils font de
        la Plateforme KLS et qu’ils ne pourront obtenir un quelconque dédommagement en cas d’utilisation détournée de la
        Plateforme KLS.</p>
    <p>CALS ne saurait être responsable de tout dommage subi par l’Utilisateur Autorisé qui résulterait du défaut du
        respect de tout ou partie des Conditions Générales, d’une faute de sa part, du fait d’un tiers ou de la
        survenance d’un cas de Force Majeure.</p>
    <p>CALS ne saurait être responsable de toute défaillance de matériel, services et/ou installation qu’elle ne fournit
        pas (directement ou indirectement), quand bien même ceux-ci seraient en relation avec la Plateforme KLS.</p>

</section>
<section id="miscellaneous">
    <h2>Stipulations diverses</h2>
    <section>
        <h3>Modification des Conditions Générales</h3>
        <p>CALS se réserve le droit d’apporter, à tout moment, aux présentes Conditions Générales toutes les
            modifications qu’elle jugera nécessaires et utiles.</p>
        <p>En cas de modification des Conditions Générales, CALS s’engage à faire accepter à nouveau à l’Utilisateur
            Autorisé les nouvelles conditions générales au moment où il accède à nouveau à la Plateforme KLS.</p>
        <p>Les Utilisateurs Autorisés n’ayant pas expressément accepté les nouvelles conditions générales ne pourront
            pas avoir accès à la Plateforme KLS.</p>
    </section>
    <section>
        <h3>Force Majeure</h3>
        <p>Chacune des Parties ne saurait voir sa responsabilité engagée pour le cas où l’exécution de ses obligations
            serait retardée, restreinte ou rendue impossible du fait de la survenance d’un événement échappant au
            contrôle de chacune des Parties, qui ne pouvait être raisonnablement prévu lors de la conclusion des
            Conditions Générales et dont les effets ne peuvent être évités par des mesures appropriée (ci-après désignée
            la « <strong>Force Majeure</strong> »).</p>
        <p>Sont notamment considérée comme Force Majeure, sans que cette liste soit limitative, les évènements suivants
            : guerre (déclarée ou non) ; acte terroriste ; invasion ; rébellion ; blocus ; sabotage ou acte de
            vandalisme ; grève ou conflit social, total ou partiel, externe à chacune des Parties ; intempérie
            (notamment inondations, orages et tempêtes) ; évènement déclaré « catastrophe naturelle » ; incendie ;
            épidémie ; blocage des moyens de transport ou d’approvisionnement (notamment en énergie) ; défaillance dans
            la fourniture de l''énergie électrique, du chauffage, de l''air conditionné, des réseaux de
            télécommunications, du transport des données ; défaillance de satellites.</p>
    </section>
    <section>
        <h3>Renonciation</h3>
        <p>Le fait que l’une ou l’autre des Parties n’exerce pas l’un quelconque de ses droits au titre des présentes ne
            saurait emporter renonciation de sa part à son exercice, une telle renonciation ne pouvant procéder que
            d’une déclaration expresse de la Partie concernée.</p>
    </section>
    <section>
        <h3>Convention de preuve</h3>
        <p>Les registres informatisés seront conservés dans les systèmes informatiques de CALS dans des conditions
            raisonnables de sécurité et seront considérés comme les preuves des échanges intervenus sur la Plateforme
            KLS ou par courrier électronique.</p>
    </section>
    <section>
        <h3>Invalidité partielle</h3>
        <p>Dans l’hypothèse où une ou plusieurs stipulations des Conditions Générales seraient considérées comme non
            valides par une juridiction compétente, les autres clauses conserveront leur portée et effet.</p>
        <p>La stipulation considérée comme invalide sera remplacée par une stipulation dont le sens et la portée seront
            le plus proches possibles de la clause ainsi invalidée, tout en restant conforme à la législation applicable
            et à la commune intention des Parties.</p>
    </section>
</section>
<section id="applicableLaw">
    <h2>Loi applicable - Juridiction competente</h2>
    <p>Les Conditions Générales sont régies par le droit français.</p>
    <p>LES PARTIES ACCEPTENT EXPRESSÉMENT DE SOUMETTRE TOUT LITIGE RELATIF AUX CONDITIONS GÉNÉRALES (EN CE COMPRIS TOUT
        DIFFÉREND CONCERNANT SA NÉGOCIATION, SA CONCLUSION, SON EXÉCUTION, SA RÉSILIATION ET/OU SA CESSATION) ET/OU AUX
        RELATIONS COMMERCIALES ENTRE LES PARTIES AINSI QU’À LEUR RUPTURE ÉVENTUELLE, À LA COMPÉTENCE EXCLUSIVE DES
        TRIBUNAUX DE PARIS, NONOBSTANT PLURALITÉ DE DÉFENDEURS OU APPEL EN GARANTIE, Y COMPRIS POUR LES PROCÉDURES SUR
        REQUÊTE OU EN RÉFÉRÉ.</p>
</section>', '', '', NOW(), UUID());
