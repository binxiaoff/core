<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20191107100404 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'CALS-490 Add shortcodes';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE companies ADD short_code VARCHAR(4) DEFAULT NULL');
        $this->addSql('UPDATE companies SET short_code = "CALS" WHERE id_company = 1');
        $this->addSql('UPDATE companies SET short_code = "CIB" WHERE id_company = 2');
        $this->addSql('UPDATE companies SET short_code = "UNFG" WHERE id_company = 3');
        $this->addSql('UPDATE companies SET short_code = "CAPR" WHERE id_company = 4');
        $this->addSql('UPDATE companies SET short_code = "ALVO" WHERE id_company = 5');
        $this->addSql('UPDATE companies SET short_code = "ANMA" WHERE id_company = 6');
        $this->addSql('UPDATE companies SET short_code = "AQTN" WHERE id_company = 7');
        $this->addSql('UPDATE companies SET short_code = "ATVD" WHERE id_company = 8');
        $this->addSql('UPDATE companies SET short_code = "BRPI" WHERE id_company = 9');
        $this->addSql('UPDATE companies SET short_code = "CEST" WHERE id_company = 10');
        $this->addSql('UPDATE companies SET short_code = "CENF" WHERE id_company = 11');
        $this->addSql('UPDATE companies SET short_code = "CENL" WHERE id_company = 12');
        $this->addSql('UPDATE companies SET short_code = "COUE" WHERE id_company = 13');
        $this->addSql('UPDATE companies SET short_code = "CHBO" WHERE id_company = 14');
        $this->addSql('UPDATE companies SET short_code = "CMSE" WHERE id_company = 15');
        $this->addSql('UPDATE companies SET short_code = "CHPE" WHERE id_company = 16');
        $this->addSql('UPDATE companies SET short_code = "CORS" WHERE id_company = 17');
        $this->addSql('UPDATE companies SET short_code = "CODA" WHERE id_company = 18');
        $this->addSql('UPDATE companies SET short_code = "NORM" WHERE id_company = 19');
        $this->addSql('UPDATE companies SET short_code = "SAVO" WHERE id_company = 20');
        $this->addSql('UPDATE companies SET short_code = "FINI" WHERE id_company = 21');
        $this->addSql('UPDATE companies SET short_code = "FRAC" WHERE id_company = 22');
        $this->addSql('UPDATE companies SET short_code = "GUAD" WHERE id_company = 23');
        $this->addSql('UPDATE companies SET short_code = "ILVI" WHERE id_company = 24');
        $this->addSql('UPDATE companies SET short_code = "LANG" WHERE id_company = 25');
        $this->addSql('UPDATE companies SET short_code = "L&HL" WHERE id_company = 26');
        $this->addSql('UPDATE companies SET short_code = "LORR" WHERE id_company = 27');
        $this->addSql('UPDATE companies SET short_code = "MART" WHERE id_company = 28');
        $this->addSql('UPDATE companies SET short_code = "MORB" WHERE id_company = 29');
        $this->addSql('UPDATE companies SET short_code = "NORF" WHERE id_company = 30');
        $this->addSql('UPDATE companies SET short_code = "NEST" WHERE id_company = 31');
        $this->addSql('UPDATE companies SET short_code = "NMPY" WHERE id_company = 32');
        $this->addSql('UPDATE companies SET short_code = "NORS" WHERE id_company = 33');
        $this->addSql('UPDATE companies SET short_code = "IDFR" WHERE id_company = 34');
        $this->addSql('UPDATE companies SET short_code = "PRCA" WHERE id_company = 35');
        $this->addSql('UPDATE companies SET short_code = "PYGA" WHERE id_company = 36');
        $this->addSql('UPDATE companies SET short_code = "REUN" WHERE id_company = 37');
        $this->addSql('UPDATE companies SET short_code = "SRAL" WHERE id_company = 38');
        $this->addSql('UPDATE companies SET short_code = "SMED" WHERE id_company = 39');
        $this->addSql('UPDATE companies SET short_code = "TOUL" WHERE id_company = 40');
        $this->addSql('UPDATE companies SET short_code = "TPOI" WHERE id_company = 41');
        $this->addSql('UPDATE companies SET short_code = "VALF" WHERE id_company = 42');
        $this->addSql('UPDATE companies SET short_code = "LCL" WHERE id_company = 43');
        $this->addSql('UPDATE companies SET short_code = "FNCA" WHERE id_company = 44');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8244AA3A17D2FE0D ON companies (short_code)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX UNIQ_8244AA3A17D2FE0D ON companies');
        $this->addSql('ALTER TABLE companies DROP short_code');
    }
}
