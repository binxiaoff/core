<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190326141146 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'CALS-64 commission';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE project_percent_fee DROP INDEX IDX_F7D17EEF270C44E3, ADD UNIQUE INDEX UNIQ_F7D17EEF270C44E3 (id_percent_fee)');
        $this->addSql('ALTER TABLE project_percent_fee DROP PRIMARY KEY');
        $this->addSql('ALTER TABLE project_percent_fee ADD id INT AUTO_INCREMENT NOT NULL PRIMARY KEY FIRST');
        $this->addSql('ALTER TABLE bid_percent_fee DROP INDEX IDX_CBDCCAB1270C44E3, ADD UNIQUE INDEX UNIQ_CBDCCAB1270C44E3 (id_percent_fee)');
        $this->addSql('ALTER TABLE bid_percent_fee DROP PRIMARY KEY');
        $this->addSql('ALTER TABLE bid_percent_fee ADD id INT AUTO_INCREMENT NOT NULL PRIMARY KEY FIRST');
        $this->addSql('ALTER TABLE loan_percent_fee DROP INDEX IDX_9BDFD650270C44E3, ADD UNIQUE INDEX UNIQ_9BDFD650270C44E3 (id_percent_fee)');
        $this->addSql('ALTER TABLE loan_percent_fee DROP PRIMARY KEY');
        $this->addSql('ALTER TABLE loan_percent_fee ADD id INT AUTO_INCREMENT NOT NULL PRIMARY KEY FIRST');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE bid_percent_fee DROP INDEX UNIQ_CBDCCAB1270C44E3, ADD INDEX IDX_CBDCCAB1270C44E3 (id_percent_fee)');
        $this->addSql('ALTER TABLE bid_percent_fee MODIFY id INT NOT NULL');
        $this->addSql('ALTER TABLE bid_percent_fee DROP PRIMARY KEY');
        $this->addSql('ALTER TABLE bid_percent_fee DROP id');
        $this->addSql('ALTER TABLE bid_percent_fee ADD PRIMARY KEY (id_percent_fee, id_bid)');
        $this->addSql('ALTER TABLE loan_percent_fee DROP INDEX UNIQ_9BDFD650270C44E3, ADD INDEX IDX_9BDFD650270C44E3 (id_percent_fee)');
        $this->addSql('ALTER TABLE loan_percent_fee MODIFY id INT NOT NULL');
        $this->addSql('ALTER TABLE loan_percent_fee DROP PRIMARY KEY');
        $this->addSql('ALTER TABLE loan_percent_fee DROP id');
        $this->addSql('ALTER TABLE loan_percent_fee ADD PRIMARY KEY (id_percent_fee, id_loan)');
        $this->addSql('ALTER TABLE project_percent_fee DROP INDEX UNIQ_F7D17EEF270C44E3, ADD INDEX IDX_F7D17EEF270C44E3 (id_percent_fee)');
        $this->addSql('ALTER TABLE project_percent_fee MODIFY id INT NOT NULL');
        $this->addSql('ALTER TABLE project_percent_fee DROP PRIMARY KEY');
        $this->addSql('ALTER TABLE project_percent_fee DROP id');
        $this->addSql('ALTER TABLE project_percent_fee ADD PRIMARY KEY (id_percent_fee, id_project)');
    }
}
