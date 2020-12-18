<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20201203144543 extends AbstractMigration
{
    /**
     * @return string
     */
    public function getDescription() : string
    {
        return 'CALS-2846 Update on V2 messaging to add broadcast property';
    }

    /**
     * @param Schema $schema
     */
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE core_message ADD broadcasted TINYINT(1) NOT NULL');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE core_message DROP broadcasted');
    }
}
