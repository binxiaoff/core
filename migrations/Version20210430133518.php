<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210430133518 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $uuid = "LOWER(
            CONCAT(
                HEX(RANDOM_BYTES(4)), '-',
                HEX(RANDOM_BYTES(2)), '-', 
                '4', SUBSTR(HEX(RANDOM_BYTES(2)), 2, 3), '-', 
                CONCAT(HEX(FLOOR(ASCII(RANDOM_BYTES(1)) / 64)+8), SUBSTR(HEX(RANDOM_BYTES(2)), 2, 3)), '-',
                HEX(RANDOM_BYTES(6))
            )
        )";

        $this->addSql('CREATE TABLE core_drive_file (drive_id INT NOT NULL, file_id INT NOT NULL, INDEX IDX_D622D2B086E5E0C4 (drive_id), INDEX IDX_D622D2B093CB796C (file_id), PRIMARY KEY(drive_id, file_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE core_drive_file ADD CONSTRAINT FK_D622D2B086E5E0C4 FOREIGN KEY (drive_id) REFERENCES core_drive (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE core_drive_file ADD CONSTRAINT FK_D622D2B093CB796C FOREIGN KEY (file_id) REFERENCES core_file (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE core_drive ADD public_id VARCHAR(36) NOT NULL');
        $this->addSql("UPDATE core_drive SET public_id = {$uuid}");
        $this->addSql('CREATE UNIQUE INDEX UNIQ_3E9CD46FB5B48B91 ON core_drive (public_id)');
        $this->addSql('ALTER TABLE core_folder_file DROP INDEX UNIQ_9500100593CB796C, ADD INDEX IDX_42318E6F93CB796C (file_id)');
        $this->addSql('ALTER TABLE core_folder_file DROP FOREIGN KEY FK_9500100593CB796C');
        $this->addSql('ALTER TABLE core_folder_file ADD CONSTRAINT FK_42318E6F93CB796C FOREIGN KEY (file_id) REFERENCES core_file (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE core_folder_file RENAME INDEX idx_95001005162cb942 TO IDX_42318E6F162CB942');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE core_drive_file');
        $this->addSql('DROP INDEX UNIQ_3E9CD46FB5B48B91 ON core_drive');
        $this->addSql('ALTER TABLE core_drive DROP public_id');
        $this->addSql('ALTER TABLE core_folder_file DROP INDEX IDX_42318E6F93CB796C, ADD UNIQUE INDEX UNIQ_9500100593CB796C (file_id)');
        $this->addSql('ALTER TABLE core_folder_file DROP FOREIGN KEY FK_42318E6F93CB796C');
        $this->addSql('ALTER TABLE core_folder_file ADD CONSTRAINT FK_9500100593CB796C FOREIGN KEY (file_id) REFERENCES core_file (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE core_folder_file RENAME INDEX idx_42318e6f162cb942 TO IDX_95001005162CB942');
    }
}
