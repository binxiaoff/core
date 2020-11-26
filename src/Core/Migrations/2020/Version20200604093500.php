<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\FetchMode;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Ramsey\Uuid\Uuid;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200604093500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'CALS-1611 Simplify borrowerCompany into a string';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE project DROP FOREIGN KEY FK_2FB3D0EE4C5E290C');
        $this->addSql('DROP INDEX IDX_2FB3D0EE4C5E290C ON project');
        $this->addSql('ALTER TABLE project ADD risk_group_name VARCHAR(255) NOT NULL');
        $this->addSql('UPDATE project SET risk_group_name = (SELECT name from company WHERE project.id_borrower_company = company.id)');
        $this->addSql('DELETE FROM company WHERE id_current_status IS NULL AND id NOT IN (SELECT staff.id_company FROM staff)');
        $this->addSql('ALTER TABLE project DROP id_borrower_company');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE project ADD id_borrower_company INT NOT NULL');

        $result   = $this->connection->executeQuery('SELECT id, risk_group_name FROM project');
        $projects = $result->fetchAll(FetchMode::ASSOCIATIVE);

        foreach ($projects as ['id' => $id, 'risk_group_name' => $riskGroupName]) {
            $uuid = Uuid::uuid4();
            $this->addSql("INSERT INTO company VALUES (NULL, NULL, '{$riskGroupName}', NULL, NULL, NOW(), NULL, NULL, '{$uuid}', NULL)");
            $this->addSql("UPDATE project SET id_borrower_company = (SELECT MAX(id) FROM company) WHERE project.id = {$id}");
        }

        $this->addSql('ALTER TABLE project DROP risk_group_name');
        $this->addSql('ALTER TABLE project ADD CONSTRAINT FK_2FB3D0EE4C5E290C FOREIGN KEY (id_borrower_company) REFERENCES company (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('CREATE INDEX IDX_2FB3D0EE4C5E290C ON project (id_borrower_company)');
    }
}
