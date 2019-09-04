<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20190904121701 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'CALS-289 Add email domain name for each company';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE companies DROP forme, DROP legal_form_code, DROP date_creation, ADD email_domain VARCHAR(255) DEFAULT NULL AFTER name;');
        $this->addSql('UPDATE companies SET email_domain = "ca-lendingservices.com" WHERE id_company = 1');
        $this->addSql('UPDATE companies SET email_domain = "ca-cib.com" WHERE id_company = 2');
        $this->addSql('UPDATE companies SET email_domain = "ca-lf.com" WHERE id_company = 3');
        $this->addSql('UPDATE companies SET email_domain = "ca-alpesprovence.fr" WHERE id_company = 4');
        $this->addSql('UPDATE companies SET email_domain = "ca-alsace-vosges.fr" WHERE id_company = 5');
        $this->addSql('UPDATE companies SET email_domain = "ca-anjou-maine.fr" WHERE id_company = 6');
        $this->addSql('UPDATE companies SET email_domain = "ca-aquitaine.fr" WHERE id_company = 7');
        $this->addSql('UPDATE companies SET email_domain = "ca-atlantique-vendee.fr" WHERE id_company = 8');
        $this->addSql('UPDATE companies SET email_domain = "ca-briepicardie.fr" WHERE id_company = 9');
        $this->addSql('UPDATE companies SET email_domain = "ca-centrest.fr" WHERE id_company = 10');
        $this->addSql('UPDATE companies SET email_domain = "ca-centrefrance.fr" WHERE id_company = 11');
        $this->addSql('UPDATE companies SET email_domain = "ca-centreloire.fr" WHERE id_company = 12');
        $this->addSql('UPDATE companies SET email_domain = "ca-centreouest.fr" WHERE id_company = 13');
        $this->addSql('UPDATE companies SET email_domain = "ca-cb.fr" WHERE id_company = 14');
        $this->addSql('UPDATE companies SET email_domain = "ca-cmds.fr" WHERE id_company = 15');
        $this->addSql('UPDATE companies SET email_domain = "ca-charente-perigord.fr" WHERE id_company = 16');
        $this->addSql('UPDATE companies SET email_domain = "ca-corse.fr" WHERE id_company = 17');
        $this->addSql('UPDATE companies SET email_domain = "ca-cotesdarmor.fr" WHERE id_company = 18');
        $this->addSql('UPDATE companies SET email_domain = "ca-normandie.fr" WHERE id_company = 19');
        $this->addSql('UPDATE companies SET email_domain = "ca-des-savoie.fr" WHERE id_company = 20');
        $this->addSql('UPDATE companies SET email_domain = "ca-finistere.fr" WHERE id_company = 21');
        $this->addSql('UPDATE companies SET email_domain = "ca-franchecomte.fr" WHERE id_company = 22');
        $this->addSql('UPDATE companies SET email_domain = "ca-guadeloupe.fr" WHERE id_company = 23');
        $this->addSql('UPDATE companies SET email_domain = "ca-illeetvilaine.fr" WHERE id_company = 24');
        $this->addSql('UPDATE companies SET email_domain = "ca-languedoc.fr" WHERE id_company = 25');
        $this->addSql('UPDATE companies SET email_domain = "ca-loirehauteloire.fr" WHERE id_company = 26');
        $this->addSql('UPDATE companies SET email_domain = "ca-lorraine.fr" WHERE id_company = 27');
        $this->addSql('UPDATE companies SET email_domain = "ca-martinique.fr" WHERE id_company = 28');
        $this->addSql('UPDATE companies SET email_domain = "ca-morbihan.fr" WHERE id_company = 29');
        $this->addSql('UPDATE companies SET email_domain = "ca-norddefrance.fr" WHERE id_company = 30');
        $this->addSql('UPDATE companies SET email_domain = "ca-nord-est.fr" WHERE id_company = 31');
        $this->addSql('UPDATE companies SET email_domain = "ca-nmp.fr" WHERE id_company = 32');
        $this->addSql('UPDATE companies SET email_domain = "ca-normandie-seine.fr" WHERE id_company = 33');
        $this->addSql('UPDATE companies SET email_domain = "ca-paris.fr" WHERE id_company = 34');
        $this->addSql('UPDATE companies SET email_domain = "ca-pca.fr" WHERE id_company = 35');
        $this->addSql('UPDATE companies SET email_domain = "lefil.com" WHERE id_company = 36');
        $this->addSql('UPDATE companies SET email_domain = "ca-reunion.fr" WHERE id_company = 37');
        $this->addSql('UPDATE companies SET email_domain = "ca-sudrhonealpes.fr" WHERE id_company = 38');
        $this->addSql('UPDATE companies SET email_domain = "ca-sudmed.fr" WHERE id_company = 39');
        $this->addSql('UPDATE companies SET email_domain = "ca-toulouse31.fr" WHERE id_company = 40');
        $this->addSql('UPDATE companies SET email_domain = "ca-tourainepoitou.fr" WHERE id_company = 41');
        $this->addSql('UPDATE companies SET email_domain = "ca-valdefrance.fr" WHERE id_company = 42');
        $this->addSql('UPDATE companies SET email_domain = "lcl.fr" WHERE id_company = 43');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE companies ADD forme VARCHAR(191) DEFAULT NULL AFTER name, ADD legal_form_code VARCHAR(10) DEFAULT NULL AFTER forme, ADD date_creation DATE DEFAULT NULL AFTER siret, DROP email_domain;');
    }
}
