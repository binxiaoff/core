<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Exception;
use Ramsey\Uuid\Uuid;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200320173228 extends AbstractMigration
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
        $this->addSql('CREATE TABLE module_log (id INT AUTO_INCREMENT NOT NULL, id_module INT NOT NULL, added_by INT NOT NULL, activated TINYINT(1) NOT NULL, added DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_634C61312A1393C5 (id_module), INDEX IDX_634C6131699B6BAF (added_by), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE module (id INT AUTO_INCREMENT NOT NULL, id_company INT NOT NULL, updated_by INT DEFAULT NULL, name VARCHAR(255) NOT NULL, activated TINYINT(1) NOT NULL, updated DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', added DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', public_id VARCHAR(36) NOT NULL, UNIQUE INDEX UNIQ_C242628B5B48B91 (public_id), INDEX IDX_C2426289122A03F (id_company), INDEX IDX_C24262816FE72E1 (updated_by), UNIQUE INDEX UNIQ_C2426289122A03F5E237E06 (id_company, name), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE module_log ADD CONSTRAINT FK_634C61312A1393C5 FOREIGN KEY (id_module) REFERENCES module (id)');
        $this->addSql('ALTER TABLE module_log ADD CONSTRAINT FK_634C6131699B6BAF FOREIGN KEY (added_by) REFERENCES staff (id)');
        $this->addSql('ALTER TABLE module ADD CONSTRAINT FK_C2426289122A03F FOREIGN KEY (id_company) REFERENCES company (id)');
        $this->addSql('ALTER TABLE module ADD CONSTRAINT FK_C24262816FE72E1 FOREIGN KEY (updated_by) REFERENCES staff (id)');

        $companies = $this->connection->fetchAll('SELECT DISTINCT id_company FROM company_status');
        $companies = array_column($companies, 'id_company');
        foreach (['arrangement', 'participation', 'agency'] as $module) {
            foreach ($companies as $companyId) {
                $uuid = (Uuid::uuid4())->toString();
                $this->addSql("INSERT INTO module VALUES (NULL, '{$companyId}', NULL, '{$module}', 0, NULL, NOW(), '{$uuid}')");
            }
        }
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE module_log DROP FOREIGN KEY FK_634C61312A1393C5');
        $this->addSql('DROP TABLE module_log');
        $this->addSql('DROP TABLE module');
    }
}
