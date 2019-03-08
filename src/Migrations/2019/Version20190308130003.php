<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190308130003 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'CALS-50 change relation between clients and companies table';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE staff (id INT AUTO_INCREMENT NOT NULL, id_company INT NOT NULL, id_client INT NOT NULL, roles JSON NOT NULL, updated DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime)\', added DATETIME NOT NULL COMMENT \'(DC2Type:datetime)\', INDEX IDX_426EF3929122A03F (id_company), INDEX IDX_426EF392E173B1B8 (id_client), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE staff ADD CONSTRAINT FK_426EF3929122A03F FOREIGN KEY (id_company) REFERENCES companies (id_company)');
        $this->addSql('ALTER TABLE staff ADD CONSTRAINT FK_426EF392E173B1B8 FOREIGN KEY (id_client) REFERENCES clients (id_client)');
        $this->addSql('ALTER TABLE companies DROP FOREIGN KEY FK_8244AA3A4FCC0FB9');
        $this->addSql('DROP INDEX id_client_owner ON companies');
        $this->addSql('ALTER TABLE companies DROP id_client_owner');
        $this->addSql('ALTER TABLE companies RENAME INDEX idx_companies_id_status TO IDX_8244AA3A5D37D0F1');
        $this->addSql('ALTER TABLE companies RENAME INDEX fk_companies_id_parent_company TO IDX_8244AA3A91C00F');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE staff');
        $this->addSql('ALTER TABLE companies ADD id_client_owner INT DEFAULT NULL');
        $this->addSql('ALTER TABLE companies ADD CONSTRAINT FK_8244AA3A4FCC0FB9 FOREIGN KEY (id_client_owner) REFERENCES clients (id_client) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('CREATE INDEX id_client_owner ON companies (id_client_owner)');
        $this->addSql('ALTER TABLE companies RENAME INDEX idx_8244aa3a5d37d0f1 TO idx_companies_id_status');
        $this->addSql('ALTER TABLE companies RENAME INDEX idx_8244aa3a91c00f TO fk_companies_id_parent_company');
    }
}
