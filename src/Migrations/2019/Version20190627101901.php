<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20190627101901 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'CALS-169 Add/modify tables to log bid/loan changes';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE zz_versioned_bid (id INT AUTO_INCREMENT NOT NULL, action VARCHAR(8) NOT NULL, logged_at DATETIME NOT NULL, object_id VARCHAR(64) DEFAULT NULL, object_class VARCHAR(255) NOT NULL, version INT NOT NULL, data LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:array)\', username VARCHAR(255) DEFAULT NULL, INDEX IDX_F6A67F78A78D87A7 (logged_at), INDEX IDX_F6A67F78F85E0677 (username), INDEX IDX_F6A67F78232D562B69684D7DBF1CD3C3 (object_id, object_class, version), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB ROW_FORMAT = DYNAMIC');
        $this->addSql('CREATE TABLE zz_versioned_loan (id INT AUTO_INCREMENT NOT NULL, action VARCHAR(8) NOT NULL, logged_at DATETIME NOT NULL, object_id VARCHAR(64) DEFAULT NULL, object_class VARCHAR(255) NOT NULL, version INT NOT NULL, data LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:array)\', username VARCHAR(255) DEFAULT NULL, INDEX IDX_BF050367A78D87A7 (logged_at), INDEX IDX_BF050367F85E0677 (username), INDEX IDX_BF050367232D562B69684D7DBF1CD3C3 (object_id, object_class, version), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB ROW_FORMAT = DYNAMIC');
        $this->addSql('ALTER TABLE accepted_bids ADD added_by INT NOT NULL');
        $this->addSql('UPDATE accepted_bids set added_by = 1'); // To avoid FK constraint fails
        $this->addSql('ALTER TABLE accepted_bids ADD CONSTRAINT FK_4B80AF05699B6BAF FOREIGN KEY (added_by) REFERENCES clients (id_client)');
        $this->addSql('CREATE INDEX IDX_4B80AF05699B6BAF ON accepted_bids (added_by)');
        $this->addSql('CREATE INDEX IDX_A6233EEBA78D87A7 ON zz_versioned_project_comment (logged_at)');
        $this->addSql('CREATE INDEX IDX_A6233EEBF85E0677 ON zz_versioned_project_comment (username)');
        $this->addSql('CREATE INDEX IDX_A6233EEB232D562B69684D7DBF1CD3C3 ON zz_versioned_project_comment (object_id, object_class, version)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE zz_versioned_bid');
        $this->addSql('DROP TABLE zz_versioned_loan');
        $this->addSql('ALTER TABLE accepted_bids DROP FOREIGN KEY FK_4B80AF05699B6BAF');
        $this->addSql('DROP INDEX IDX_4B80AF05699B6BAF ON accepted_bids');
        $this->addSql('ALTER TABLE accepted_bids DROP added_by');
        $this->addSql('DROP INDEX IDX_A6233EEBA78D87A7 ON zz_versioned_project_comment');
        $this->addSql('DROP INDEX IDX_A6233EEBF85E0677 ON zz_versioned_project_comment');
        $this->addSql('DROP INDEX IDX_A6233EEB232D562B69684D7DBF1CD3C3 ON zz_versioned_project_comment');
    }
}
