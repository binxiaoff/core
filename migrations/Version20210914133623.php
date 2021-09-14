<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20210914133623 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '[CreditGuaranty] CALS-4435 create credit_guaranty_reporting_template table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE credit_guaranty_reporting_template (id INT AUTO_INCREMENT NOT NULL, id_program INT NOT NULL, name VARCHAR(255) NOT NULL, public_id VARCHAR(36) NOT NULL, updated DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', added DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', UNIQUE INDEX UNIQ_EE2FC26AB5B48B91 (public_id), INDEX IDX_EE2FC26A4C70DEF4 (id_program), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE credit_guaranty_reporting_template ADD CONSTRAINT FK_EE2FC26A4C70DEF4 FOREIGN KEY (id_program) REFERENCES credit_guaranty_program (id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE credit_guaranty_reporting_template');
    }
}
