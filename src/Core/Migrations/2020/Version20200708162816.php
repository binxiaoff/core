<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20200708162816 extends AbstractMigration
{
    /**
     * @return string
     */
    public function getDescription(): string
    {
        return 'CALS-1929 Change bic into bankCode for company';
    }

    /**
     * @param Schema $schema
     */
    public function up(Schema $schema): void
    {
        $this->addSql('DROP INDEX UNIQ_4FBF094FD4962650 ON company');
        $this->addSql('ALTER TABLE company ADD bank_code VARCHAR(5) NOT NULL, DROP bic');
        $this->addSql('UPDATE company SET bank_code = id WHERE 1 = 1');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_4FBF094FDD756216 ON company (bank_code)');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX UNIQ_4FBF094FDD756216 ON company');
        $this->addSql('ALTER TABLE company ADD bic VARCHAR(12) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, DROP bank_code');
        $this->addSql("UPDATE company SET bic = CONCAT('ABCDFR', SUBSTRING(CONCAT('000000', id), -5, 5)) WHERE 1 = 1");
        $this->addSql('CREATE UNIQUE INDEX UNIQ_4FBF094FD4962650 ON company (bic)');
    }
}
