<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190712152606 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'CALS-92 Create tables related to Foncaris';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE tranche_attribute (id INT AUTO_INCREMENT NOT NULL, id_tranche INT NOT NULL, attribute_name VARCHAR(191) NOT NULL, attribute_value VARCHAR(191) NOT NULL, updated DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', added DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_D29E7A55B8FAF130 (id_tranche), INDEX IDX_D29E7A555CBDA8E (attribute_name), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE tranche_attribute ADD CONSTRAINT FK_D29E7A55B8FAF130 FOREIGN KEY (id_tranche) REFERENCES tranche (id)');
        $this->addSql('CREATE TABLE foncaris_funding_type (id INT AUTO_INCREMENT NOT NULL, `category` SMALLINT NOT NULL, description VARCHAR(100) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE foncaris_security (id INT AUTO_INCREMENT NOT NULL, category SMALLINT NOT NULL, description VARCHAR(100) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql(
            <<<'FONCARISFUNDINGTYPE'
INSERT INTO foncaris_funding_type (id, category, description)
VALUES (1, 1, 'Garantie à 1ère Demande'),
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
       (31, 3, 'Crédit Bail Immobilier (CBI)')
FONCARISFUNDINGTYPE
);
        $this->addSql(
            <<<'FONCARISSECURITY'
INSERT INTO foncaris_security (id, category, description)
VALUES (1, 1, 'CAUT.SOLID.PARTIEL.P.PHYS.'),
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
       (61, 4, 'GAGE ESPECES')
FONCARISSECURITY
        );
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE tranche_attribute');
        $this->addSql('DROP TABLE foncaris_funding_type');
        $this->addSql('DROP TABLE foncaris_security');
    }
}
