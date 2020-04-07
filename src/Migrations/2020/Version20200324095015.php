<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Exception;
use Ramsey\Uuid\Uuid;

final class Version20200324095015 extends AbstractMigration
{
    /**
     * @return string
     */
    public function getDescription(): string
    {
        return 'CALS-1304 Add modules';
    }

    /**
     * @param Schema $schema
     *
     * @throws Exception
     */
    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE company_module (id INT AUTO_INCREMENT NOT NULL, id_company INT NOT NULL, updated_by INT DEFAULT NULL, name VARCHAR(255) NOT NULL, activated TINYINT(1) NOT NULL, updated DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', added DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', public_id VARCHAR(36) NOT NULL, UNIQUE INDEX UNIQ_31BB425DB5B48B91 (public_id), INDEX IDX_31BB425D9122A03F (id_company), INDEX IDX_31BB425D16FE72E1 (updated_by), UNIQUE INDEX UNIQ_31BB425D9122A03F5E237E06 (id_company, name), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE company_module_log (id INT AUTO_INCREMENT NOT NULL, id_module INT NOT NULL, added_by INT NOT NULL, activated TINYINT(1) NOT NULL, added DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_DEC288A72A1393C5 (id_module), INDEX IDX_DEC288A7699B6BAF (added_by), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE company_module ADD CONSTRAINT FK_31BB425D9122A03F FOREIGN KEY (id_company) REFERENCES company (id)');
        $this->addSql('ALTER TABLE company_module ADD CONSTRAINT FK_31BB425D16FE72E1 FOREIGN KEY (updated_by) REFERENCES staff (id)');
        $this->addSql('ALTER TABLE company_module_log ADD CONSTRAINT FK_DEC288A72A1393C5 FOREIGN KEY (id_module) REFERENCES company_module (id)');
        $this->addSql('ALTER TABLE company_module_log ADD CONSTRAINT FK_DEC288A7699B6BAF FOREIGN KEY (added_by) REFERENCES staff (id)');

        $companies = $this->connection->fetchAll('SELECT DISTINCT id_company FROM company_status');
        $companies = array_column($companies, 'id_company');
        foreach (['arrangement', 'participation', 'agency'] as $module) {
            foreach ($companies as $companyId) {
                $uuid = (Uuid::uuid4())->toString();
                $this->addSql("INSERT INTO company_module VALUES (NULL, '{$companyId}', NULL, '{$module}', 0, NULL, NOW(), '{$uuid}')");
            }
        }

        $this->addSql('DROP INDEX UNIQ_31BB425D9122A03F5E237E06 ON company_module');
        $this->addSql('ALTER TABLE company_module CHANGE name label VARCHAR(255) NOT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_31BB425D9122A03FEA750E8 ON company_module (id_company, label)');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX UNIQ_31BB425D9122A03FEA750E8 ON company_module');
        $this->addSql('ALTER TABLE company_module CHANGE label name VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_31BB425D9122A03F5E237E06 ON company_module (id_company, name)');

        $this->addSql('ALTER TABLE company_module_log DROP FOREIGN KEY FK_DEC288A72A1393C5');
        $this->addSql('DROP TABLE company_module');
        $this->addSql('DROP TABLE company_module_log');
    }
}
