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
        $this->addSql('UPDATE companies SET short_code = "CALS" WHERE id = 1');
        $this->addSql('UPDATE companies SET short_code = "CIB" WHERE id = 2');
        $this->addSql('UPDATE companies SET short_code = "UNFG" WHERE id = 3');
        $this->addSql('UPDATE companies SET short_code = "CAPR" WHERE id = 4');
        $this->addSql('UPDATE companies SET short_code = "ALVO" WHERE id = 5');
        $this->addSql('UPDATE companies SET short_code = "ANMA" WHERE id = 6');
        $this->addSql('UPDATE companies SET short_code = "AQTN" WHERE id = 7');
        $this->addSql('UPDATE companies SET short_code = "ATVD" WHERE id = 8');
        $this->addSql('UPDATE companies SET short_code = "BRPI" WHERE id = 9');
        $this->addSql('UPDATE companies SET short_code = "CEST" WHERE id = 10');
        $this->addSql('UPDATE companies SET short_code = "CENF" WHERE id = 11');
        $this->addSql('UPDATE companies SET short_code = "CENL" WHERE id = 12');
        $this->addSql('UPDATE companies SET short_code = "COUE" WHERE id = 13');
        $this->addSql('UPDATE companies SET short_code = "CHBO" WHERE id = 14');
        $this->addSql('UPDATE companies SET short_code = "CMSE" WHERE id = 15');
        $this->addSql('UPDATE companies SET short_code = "CHPE" WHERE id = 16');
        $this->addSql('UPDATE companies SET short_code = "CORS" WHERE id = 17');
        $this->addSql('UPDATE companies SET short_code = "CODA" WHERE id = 18');
        $this->addSql('UPDATE companies SET short_code = "NORM" WHERE id = 19');
        $this->addSql('UPDATE companies SET short_code = "SAVO" WHERE id = 20');
        $this->addSql('UPDATE companies SET short_code = "FINI" WHERE id = 21');
        $this->addSql('UPDATE companies SET short_code = "FRAC" WHERE id = 22');
        $this->addSql('UPDATE companies SET short_code = "GUAD" WHERE id = 23');
        $this->addSql('UPDATE companies SET short_code = "ILVI" WHERE id = 24');
        $this->addSql('UPDATE companies SET short_code = "LANG" WHERE id = 25');
        $this->addSql('UPDATE companies SET short_code = "L&HL" WHERE id = 26');
        $this->addSql('UPDATE companies SET short_code = "LORR" WHERE id = 27');
        $this->addSql('UPDATE companies SET short_code = "MART" WHERE id = 28');
        $this->addSql('UPDATE companies SET short_code = "MORB" WHERE id = 29');
        $this->addSql('UPDATE companies SET short_code = "NORF" WHERE id = 30');
        $this->addSql('UPDATE companies SET short_code = "NEST" WHERE id = 31');
        $this->addSql('UPDATE companies SET short_code = "NMPY" WHERE id = 32');
        $this->addSql('UPDATE companies SET short_code = "NORS" WHERE id = 33');
        $this->addSql('UPDATE companies SET short_code = "IDFR" WHERE id = 34');
        $this->addSql('UPDATE companies SET short_code = "PRCA" WHERE id = 35');
        $this->addSql('UPDATE companies SET short_code = "PYGA" WHERE id = 36');
        $this->addSql('UPDATE companies SET short_code = "REUN" WHERE id = 37');
        $this->addSql('UPDATE companies SET short_code = "SRAL" WHERE id = 38');
        $this->addSql('UPDATE companies SET short_code = "SMED" WHERE id = 39');
        $this->addSql('UPDATE companies SET short_code = "TOUL" WHERE id = 40');
        $this->addSql('UPDATE companies SET short_code = "TPOI" WHERE id = 41');
        $this->addSql('UPDATE companies SET short_code = "VALF" WHERE id = 42');
        $this->addSql('UPDATE companies SET short_code = "LCL" WHERE id = 43');
        $this->addSql('UPDATE companies SET short_code = "FNCA" WHERE id = 44');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8244AA3A17D2FE0D ON companies (short_code)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX UNIQ_8244AA3A17D2FE0D ON companies');
        $this->addSql('ALTER TABLE companies DROP short_code');
    }
}
