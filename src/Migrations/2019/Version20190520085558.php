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
    }

    public function down(Schema $schema): void
    {
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE ca_regional_bank');
    }
}
