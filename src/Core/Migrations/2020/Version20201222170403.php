<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20201222170403 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'CALS-1595 : change broadcast field from int to varchar';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE core_message CHANGE broadcast broadcast VARCHAR(36) DEFAULT NULL');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE core_message CHANGE broadcast broadcast TINYINT(1) NOT NULL');
    }
}
