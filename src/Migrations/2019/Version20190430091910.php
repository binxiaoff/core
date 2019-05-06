<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190430091910 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'CALS-104 creat new table tranche';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE tranche (id INT AUTO_INCREMENT NOT NULL, id_project INT NOT NULL, name VARCHAR(191) NOT NULL, repayment_type VARCHAR(30) NOT NULL, duration SMALLINT NOT NULL, capital_periodicity SMALLINT NOT NULL, interest_periodicity SMALLINT NOT NULL, expected_releasing_date DATE NOT NULL COMMENT \'(DC2Type:date_immutable)\', expected_starting_date DATE NOT NULL COMMENT \'(DC2Type:date_immutable)\', money_amount NUMERIC(10, 2) NOT NULL, money_currency VARCHAR(255) NOT NULL, rate_index_type VARCHAR(20) DEFAULT NULL, rate_margin NUMERIC(4, 2) DEFAULT NULL, updated DATETIME DEFAULT NULL, added DATETIME NOT NULL, INDEX IDX_66675840F12E799E (id_project), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE tranche_percent_fee (id INT AUTO_INCREMENT NOT NULL, id_percent_fee INT NOT NULL, id_tranche INT NOT NULL, UNIQUE INDEX UNIQ_8FFF575C270C44E3 (id_percent_fee), INDEX IDX_8FFF575CB8FAF130 (id_tranche), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE tranche ADD CONSTRAINT FK_66675840F12E799E FOREIGN KEY (id_project) REFERENCES project (id)');
        $this->addSql('ALTER TABLE tranche_percent_fee ADD CONSTRAINT FK_8FFF575C270C44E3 FOREIGN KEY (id_percent_fee) REFERENCES percent_fee (id)');
        $this->addSql('ALTER TABLE tranche_percent_fee ADD CONSTRAINT FK_8FFF575CB8FAF130 FOREIGN KEY (id_tranche) REFERENCES tranche (id)');
        $this->addSql('ALTER TABLE project ADD foncaris_guarantee SMALLINT NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE tranche_percent_fee DROP FOREIGN KEY FK_8FFF575CB8FAF130');
        $this->addSql('DROP TABLE tranche');
        $this->addSql('DROP TABLE tranche_percent_fee');
        $this->addSql('ALTER TABLE project DROP foncaris_guarantee');
    }
}
