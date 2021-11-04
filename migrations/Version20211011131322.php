<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20211011131322 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '[CreditGuaranty] CALS-4815 change address_department properties type to ProgramChoiceOption + update credit_guaranty_field table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE credit_guaranty_borrower ADD id_address_department INT DEFAULT NULL, DROP address_department');
        $this->addSql('ALTER TABLE credit_guaranty_borrower ADD CONSTRAINT FK_D7ADB78C36A2DBBB FOREIGN KEY (id_address_department) REFERENCES credit_guaranty_program_choice_option (id)');
        $this->addSql('CREATE INDEX IDX_D7ADB78C36A2DBBB ON credit_guaranty_borrower (id_address_department)');
        $this->addSql('ALTER TABLE credit_guaranty_project ADD id_address_department INT DEFAULT NULL, DROP address_department');
        $this->addSql('ALTER TABLE credit_guaranty_project ADD CONSTRAINT FK_A452D02536A2DBBB FOREIGN KEY (id_address_department) REFERENCES credit_guaranty_program_choice_option (id)');
        $this->addSql('CREATE INDEX IDX_A452D02536A2DBBB ON credit_guaranty_project (id_address_department)');
        // fields
        $this->addSql("UPDATE credit_guaranty_field SET type = 'list', property_type = 'ProgramChoiceOption' WHERE field_alias IN ('activity_department', 'investment_department')");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE credit_guaranty_borrower DROP FOREIGN KEY FK_D7ADB78C36A2DBBB');
        $this->addSql('DROP INDEX IDX_D7ADB78C36A2DBBB ON credit_guaranty_borrower');
        $this->addSql('ALTER TABLE credit_guaranty_borrower ADD address_department VARCHAR(30) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, DROP id_address_department');
        $this->addSql('ALTER TABLE credit_guaranty_project DROP FOREIGN KEY FK_A452D02536A2DBBB');
        $this->addSql('DROP INDEX IDX_A452D02536A2DBBB ON credit_guaranty_project');
        $this->addSql('ALTER TABLE credit_guaranty_project ADD address_department VARCHAR(30) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, DROP id_address_department');
    }
}
