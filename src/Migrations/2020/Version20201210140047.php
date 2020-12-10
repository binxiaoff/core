<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20201210140047 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'CALS-2846 updates on messaging V2 message_file to remove UNIQUE INDEX';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE message_file DROP INDEX UNIQ_250AADC97BF2A12, ADD INDEX IDX_250AADC97BF2A12 (id_file)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE message_file DROP INDEX IDX_250AADC97BF2A12, ADD UNIQUE INDEX UNIQ_250AADC97BF2A12 (id_file)');
    }
}
