<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20211206131952 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '[CreditGuaranty] CALS-5285 create credit_guaranty_department table + hydrate table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE credit_guaranty_department (id INT AUTO_INCREMENT NOT NULL, code VARCHAR(5) NOT NULL, name VARCHAR(255) NOT NULL, region VARCHAR(255) NOT NULL, UNIQUE INDEX UNIQ_87EA4A4E77153098 (code), UNIQUE INDEX UNIQ_87EA4A4E771530985E237E06 (code, name), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql(<<<'SQL'
            INSERT INTO `credit_guaranty_department`
                (code, name, region)
            VALUES
                ('01', "Ain", "Auvergne-Rhône-Alpes"),
                ('02', "Aisne", "Hauts-de-France"),
                ('03', "Allier", "Auvergne-Rhône-Alpes"),
                ('04', "Alpes-de-Haute-Provence", "Provence-Alpes-Côte d'Azur"),
                ('05', "Hautes-Alpes", "Provence-Alpes-Côte d'Azur"),
                ('06', "Alpes-Maritimes", "Provence-Alpes-Côte d'Azur"),
                ('07', "Ardèche", "Auvergne-Rhône-Alpes"),
                ('08', "Ardennes", "Grand Est"),
                ('09', "Ariège", "Occitanie"),
                ('10', "Aube", "Grand Est"),
                ('11', "Aude", "Occitanie"),
                ('12', "Aveyron", "Occitanie"),
                ('13', "Bouches-du-Rhône", "Provence-Alpes-Côte d'Azur"),
                ('14', "Calvados", "Normandie"),
                ('15', "Cantal", "Auvergne-Rhône-Alpes"),
                ('16', "Charente", "Nouvelle-Aquitaine"),
                ('17', "Charente-Maritime", "Nouvelle-Aquitaine"),
                ('18', "Cher", "Centre-Val de Loire"),
                ('19', "Corrèze", "Nouvelle-Aquitaine"),
                ('2A', "Corse-du-Sud", "Corse"),
                ('2B', "Haute-Corse", "Corse"),
                ('21', "Côte-d'Or", "Bourgogne-Franche-Comté"),
                ('22', "Côtes-d'Armor", "Bretagne"),
                ('23', "Creuse", "Nouvelle-Aquitaine"),
                ('24', "Dordogne", "Nouvelle-Aquitaine"),
                ('25', "Doubs", "Bourgogne-Franche-Comté"),
                ('26', "Drôme", "Auvergne-Rhône-Alpes"),
                ('27', "Eure", "Normandie"),
                ('28', "Eure-et-Loir", "Centre-Val de Loire"),
                ('29', "Finistère", "Bretagne"),
                ('30', "Gard", "Occitanie"),
                ('31', "Haute-Garonne", "Occitanie"),
                ('32', "Gers", "Occitanie"),
                ('33', "Gironde", "Nouvelle-Aquitaine"),
                ('34', "Hérault", "Occitanie"),
                ('35', "Ille-et-Vilaine", "Bretagne"),
                ('36', "Indre", "Centre-Val de Loire"),
                ('37', "Indre-et-Loire", "Centre-Val de Loire"),
                ('38', "Isère", "Auvergne-Rhône-Alpes"),
                ('39', "Jura", "Bourgogne-Franche-Comté"),
                ('40', "Landes", "Nouvelle-Aquitaine"),
                ('41', "Loir-et-Cher", "Centre-Val de Loire"),
                ('42', "Loire", "Auvergne-Rhône-Alpes"),
                ('43', "Haute-Loire", "Auvergne-Rhône-Alpes"),
                ('44', "Loire-Atlantique", "Pays de la Loire"),
                ('45', "Loiret", "Centre-Val de Loire"),
                ('46', "Lot", "Occitanie"),
                ('47', "Lot-et-Garonne", "Nouvelle-Aquitaine"),
                ('48', "Lozère", "Occitanie"),
                ('49', "Maine-et-Loire", "Pays de la Loire"),
                ('50', "Manche", "Normandie"),
                ('51', "Marne", "Grand Est"),
                ('52', "Haute-Marne", "Grand Est"),
                ('53', "Mayenne", "Pays de la Loire"),
                ('54', "Meurthe-et-Moselle", "Grand Est"),
                ('55', "Meuse", "Grand Est"),
                ('56', "Morbihan", "Bretagne"),
                ('57', "Moselle", "Grand Est"),
                ('58', "Nièvre", "Bourgogne-Franche-Comté"),
                ('59', "Nord", "Hauts-de-France"),
                ('60', "Oise", "Hauts-de-France"),
                ('61', "Orne", "Normandie"),
                ('62', "Pas-de-Calais", "Hauts-de-France"),
                ('63', "Puy-de-Dôme", "Auvergne-Rhône-Alpes"),
                ('64', "Pyrénées-Atlantiques", "Nouvelle-Aquitaine"),
                ('65', "Hautes-Pyrénées", "Occitanie"),
                ('66', "Pyrénées-Orientales", "Occitanie"),
                ('67', "Bas-Rhin", "Grand Est"),
                ('68', "Haut-Rhin", "Grand Est"),
                ('69', "Rhône", "Auvergne-Rhône-Alpes"),
                ('70', "Haute-Saône", "Bourgogne-Franche-Comté"),
                ('71', "Saône-et-Loire", "Bourgogne-Franche-Comté"),
                ('72', "Sarthe", "Pays de la Loire"),
                ('73', "Savoie", "Auvergne-Rhône-Alpes"),
                ('74', "Haute-Savoie", "Auvergne-Rhône-Alpes"),
                ('75', "Paris", "Ile-de-France"),
                ('76', "Seine-Maritime", "Normandie"),
                ('77', "Seine-et-Marne", "Ile-de-France"),
                ('78', "Yvelines", "Ile-de-France"),
                ('79', "Deux-Sèvres", "Nouvelle-Aquitaine"),
                ('80', "Somme", "Hauts-de-France"),
                ('81', "Tarn", "Occitanie"),
                ('82', "Tarn-et-Garonne", "Occitanie"),
                ('83', "Var", "Provence-Alpes-Côte d'Azur"),
                ('84', "Vaucluse", "Provence-Alpes-Côte d'Azur"),
                ('85', "Vendée", "Pays de la Loire"),
                ('86', "Vienne", "Nouvelle-Aquitaine"),
                ('87', "Haute-Vienne", "Nouvelle-Aquitaine"),
                ('88', "Vosges", "Grand Est"),
                ('89', "Yonne", "Bourgogne-Franche-Comté"),
                ('90', "Territoire de Belfort", "Bourgogne-Franche-Comté"),
                ('91', "Essonne", "Ile-de-France"),
                ('92', "Hauts-de-Seine", "Ile-de-France"),
                ('93', "Seine-St-Denis", "Ile-de-France"),
                ('94', "Val-de-Marne", "Ile-de-France"),
                ('95', "Val-D'Oise", "Ile-de-France"),
                ('971', "Guadeloupe", "Guadeloupe"),
                ('972', "Martinique", "Martinique"),
                ('973', "Guyane", "Guyane"),
                ('974', "La Réunion", "La Réunion"),
                ('976', "Mayotte", "Mayotte")
            SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE credit_guaranty_department');
    }
}
