<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210512125150 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'CALS-3636 [CreditGuaranty] replace naf_code by id_naf_nace FK';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE credit_guaranty_project ADD id_naf_nace INT NOT NULL, DROP naf_code');
        $this->addSql('ALTER TABLE credit_guaranty_project ADD CONSTRAINT FK_A452D0255853FEED FOREIGN KEY (id_naf_nace) REFERENCES core_naf_nace (id)');
        $this->addSql('CREATE INDEX IDX_A452D0255853FEED ON credit_guaranty_project (id_naf_nace)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE credit_guaranty_project DROP FOREIGN KEY FK_A452D0255853FEED');
        $this->addSql('DROP INDEX IDX_A452D0255853FEED ON credit_guaranty_project');
        $this->addSql('ALTER TABLE credit_guaranty_project ADD naf_code VARCHAR(5) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, DROP id_naf_nace');
    }
}
