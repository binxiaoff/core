<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20201207104249 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'CALS-2846 updates on messaging V2 model';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE UNIQUE INDEX UNIQ_250AADC97BF2A12 ON message_file (id_file)');
        $this->addSql('ALTER TABLE message_status ADD public_id VARCHAR(36) NOT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_4C27F813B5B48B91 ON message_status (public_id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX UNIQ_250AADC97BF2A12 ON message_file');
        $this->addSql('DROP INDEX UNIQ_4C27F813B5B48B91 ON message_status');
        $this->addSql('ALTER TABLE message_status DROP public_id');
    }
}
