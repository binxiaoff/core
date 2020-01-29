<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20200129132051 extends AbstractMigration
{
    /**
     * @return string
     */
    public function getDescription(): string
    {
        return 'CALS-773 Drop previous table';
    }

    /**
     * @param Schema $schema
     */
    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE companies DROP FOREIGN KEY FK_8244AA3A5D37D0F1');
        $this->addSql('ALTER TABLE company_status_history DROP FOREIGN KEY FK_1A2286D5D37D0F1');
        $this->addSql('DROP TABLE company_status');
        $this->addSql('DROP TABLE company_status_history');
        $this->addSql('DROP INDEX IDX_8244AA3A5D37D0F1 ON companies');
        $this->addSql('ALTER TABLE companies DROP id_status');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema): void
    {
        $this->addSql('CREATE TABLE company_status (id SMALLINT AUTO_INCREMENT NOT NULL, label VARCHAR(191) NOT NULL COLLATE utf8mb4_unicode_ci, UNIQUE INDEX UNIQ_469F0169EA750E8 (label), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE company_status_history (id INT AUTO_INCREMENT NOT NULL, id_company INT NOT NULL, id_status SMALLINT NOT NULL, changed_on DATE DEFAULT NULL, receiver MEDIUMTEXT DEFAULT NULL COLLATE utf8mb4_unicode_ci, mail_content MEDIUMTEXT DEFAULT NULL COLLATE utf8mb4_unicode_ci, site_content MEDIUMTEXT DEFAULT NULL COLLATE utf8mb4_unicode_ci, added DATETIME NOT NULL, INDEX idx_company_status_history_id_company (id_company), INDEX idx_company_status_history_id_status (id_status), INDEX idx_company_status_history_changed_on (changed_on), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE company_status_history ADD CONSTRAINT FK_1A2286D5D37D0F1 FOREIGN KEY (id_status) REFERENCES company_status (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE company_status_history ADD CONSTRAINT FK_1A2286D9122A03F FOREIGN KEY (id_company) REFERENCES companies (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE companies ADD id_status SMALLINT DEFAULT NULL');
        $this->addSql('ALTER TABLE companies ADD CONSTRAINT FK_8244AA3A5D37D0F1 FOREIGN KEY (id_status) REFERENCES company_status (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('CREATE INDEX IDX_8244AA3A5D37D0F1 ON companies (id_status)');
    }
}
