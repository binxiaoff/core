<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200923131155 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'CALS-2457 Make shortcode non nullable';
    }

    public function up(Schema $schema) : void
    {
        $this->addSql("UPDATE company SET short_code = 'CALF' WHERE display_name = 'CALF / Unifergie';");
        $this->addSql("UPDATE company SET short_code = 'CHAL' WHERE display_name = 'Banque Chalus';");
        $this->addSql("UPDATE company SET short_code = 'FONC' WHERE display_name = 'Foncaris';");
        $this->addSql("UPDATE company SET short_code = 'LIXX' WHERE display_name = 'LixxBail';");
        $this->addSql('ALTER TABLE company CHANGE short_code short_code VARCHAR(10) NOT NULL');
    }

    public function down(Schema $schema) : void
    {
        $this->addSql('ALTER TABLE company CHANGE short_code short_code VARCHAR(10) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`');
    }
}
