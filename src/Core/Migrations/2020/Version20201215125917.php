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
    /**
     * @return string
     */
    public function getDescription() : string
    {
        return 'CALS-1595 : change messages tables';
    }

    /**
     * @param Schema $schema
     */
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE core_message CHANGE broadcasted broadcast TINYINT(1) NOT NULL');
        $this->addSql('ALTER TABLE core_message_file RENAME INDEX fk_250aadc97bf2a12 TO IDX_250AADC97BF2A12');
        $this->addSql('ALTER TABLE core_message_status CHANGE id_message id_message INT DEFAULT NULL');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE core_message CHANGE broadcast broadcasted TINYINT(1) NOT NULL');
        $this->addSql('ALTER TABLE core_message_file RENAME INDEX idx_250aadc97bf2a12 TO FK_250AADC97BF2A12');
        $this->addSql('ALTER TABLE core_message_status CHANGE id_message id_message INT NOT NULL');
    }
}
