<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190520085558 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'CALS-111 create CA regional banks';
    }

    public function up(Schema $schema): void
    {
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE ca_regional_bank (id INT AUTO_INCREMENT NOT NULL, id_company INT NOT NULL, friendly_group INT NOT NULL, UNIQUE INDEX UNIQ_661B74A49122A03F (id_company), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE ca_regional_bank ADD CONSTRAINT FK_661B74A49122A03F FOREIGN KEY (id_company) REFERENCES companies (id_company)');
        $this->addSql('
            INSERT INTO ca_regional_bank (id_company, friendly_group)
            VALUES
                   (4, 4),
                   (5, 2),
                   (6, 3),
                   (7, 4),
                   (8, 4),
                   (9, 2),
                   (10, 1),
                   (11, 1),
                   (12, 1),
                   (13, 4),
                   (14, 1),
                   (15, 3),
                   (16, 3),
                   (17, 4),
                   (18, 3),
                   (19, 2),
                   (20, 4),
                   (21, 3),
                   (22, 2),
                   (23, 3),
                   (24, 3),
                   (25, 4),
                   (26, 1),
                   (27, 2),
                   (28, 3),
                   (29, 3),
                   (30, 2),
                   (31, 2),
                   (32, 4),
                   (33, 2),
                   (34, 2),
                   (35, 4),
                   (36, 4),
                   (37, 4),
                   (38, 4),
                   (39, 4),
                   (40, 4),
                   (41, 3),
                   (42, 1);
        ');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE ca_regional_bank');
    }
}
