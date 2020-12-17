<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20201215125917 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX IDX_C82E74D1B862B8 ON clients');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_4FBF094FDA33CDFB ON company (email_domain)');
        $this->addSql('ALTER TABLE invitation_reply_version RENAME INDEX idx_ab14feddb99af4da TO IDX_AB14FEDD1BEAFC95');
        $this->addSql('ALTER TABLE message CHANGE broadcasted broadcast TINYINT(1) NOT NULL');
        $this->addSql('ALTER TABLE message_file RENAME INDEX fk_250aadc97bf2a12 TO IDX_250AADC97BF2A12');
        $this->addSql('ALTER TABLE message_status CHANGE id_message id_message INT DEFAULT NULL');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE INDEX IDX_C82E74D1B862B8 ON clients (public_id)');
        $this->addSql('DROP INDEX UNIQ_4FBF094FDA33CDFB ON company');
        $this->addSql('ALTER TABLE invitation_reply_version RENAME INDEX idx_ab14fedd1beafc95 TO IDX_AB14FEDDB99AF4DA');
        $this->addSql('ALTER TABLE message CHANGE broadcast broadcasted TINYINT(1) NOT NULL');
        $this->addSql('ALTER TABLE message_file RENAME INDEX idx_250aadc97bf2a12 TO FK_250AADC97BF2A12');
        $this->addSql('ALTER TABLE message_status CHANGE id_message id_message INT NOT NULL');
    }
}
