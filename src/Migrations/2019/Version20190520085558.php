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

        $this->addSql('CREATE TABLE regional_bank (id INT AUTO_INCREMENT NOT NULL, id_company INT NOT NULL, friendly_group INT NOT NULL, updated DATETIME DEFAULT NULL, added DATETIME NOT NULL, UNIQUE INDEX UNIQ_661B74A49122A03F (id_company), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE regional_bank ADD CONSTRAINT FK_661B74A49122A03F FOREIGN KEY (id_company) REFERENCES companies (id_company)');
        $this->addSql('
            INSERT INTO regional_bank (id_company, friendly_group, added)
            VALUES
                   (4, 4, NOW()),
                   (5, 2, NOW()),
                   (6, 3, NOW()),
                   (7, 4, NOW()),
                   (8, 4, NOW()),
                   (9, 2, NOW()),
                   (10, 1, NOW()),
                   (11, 1, NOW()),
                   (12, 1, NOW()),
                   (13, 4, NOW()),
                   (14, 1, NOW()),
                   (15, 3, NOW()),
                   (16, 3, NOW()),
                   (17, 4, NOW()),
                   (18, 3, NOW()),
                   (19, 2, NOW()),
                   (20, 4, NOW()),
                   (21, 3, NOW()),
                   (22, 2, NOW()),
                   (23, 3, NOW()),
                   (24, 3, NOW()),
                   (25, 4, NOW()),
                   (26, 1, NOW()),
                   (27, 2, NOW()),
                   (28, 3, NOW()),
                   (29, 3, NOW()),
                   (30, 2, NOW()),
                   (31, 2, NOW()),
                   (32, 4, NOW()),
                   (33, 2, NOW()),
                   (34, 2, NOW()),
                   (35, 4, NOW()),
                   (36, 4, NOW()),
                   (37, 4, NOW()),
                   (38, 4, NOW()),
                   (39, 4, NOW()),
                   (40, 4, NOW()),
                   (41, 3, NOW()),
                   (42, 1, NOW());
        ');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE regional_bank');
    }
}
