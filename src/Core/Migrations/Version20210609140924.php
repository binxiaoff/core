<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20210609140924 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'CALS-3920 Change the project naf code type and add naf code for borrower business activity';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE credit_guaranty_borrower_business_activity ADD id_naf_code INT DEFAULT NULL');
        $this->addSql('ALTER TABLE credit_guaranty_borrower_business_activity ADD CONSTRAINT FK_6008FDC0EFE69DFD FOREIGN KEY (id_naf_code) REFERENCES credit_guaranty_program_choice_option (id)');
        $this->addSql('CREATE INDEX IDX_6008FDC0EFE69DFD ON credit_guaranty_borrower_business_activity (id_naf_code)');
        $this->addSql('ALTER TABLE credit_guaranty_project DROP FOREIGN KEY FK_A452D0255853FEED');
        $this->addSql('DROP INDEX IDX_A452D0255853FEED ON credit_guaranty_project');
        $this->addSql('ALTER TABLE credit_guaranty_project CHANGE id_naf_nace id_naf_code INT NOT NULL');
        $this->addSql('ALTER TABLE credit_guaranty_project ADD CONSTRAINT FK_A452D025EFE69DFD FOREIGN KEY (id_naf_code) REFERENCES credit_guaranty_program_choice_option (id)');
        $this->addSql('CREATE INDEX IDX_A452D025EFE69DFD ON credit_guaranty_project (id_naf_code)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE credit_guaranty_borrower_business_activity DROP FOREIGN KEY FK_6008FDC0EFE69DFD');
        $this->addSql('DROP INDEX IDX_6008FDC0EFE69DFD ON credit_guaranty_borrower_business_activity');
        $this->addSql('ALTER TABLE credit_guaranty_borrower_business_activity DROP id_naf_code');
        $this->addSql('ALTER TABLE credit_guaranty_project DROP FOREIGN KEY FK_A452D025EFE69DFD');
        $this->addSql('DROP INDEX IDX_A452D025EFE69DFD ON credit_guaranty_project');
        $this->addSql('ALTER TABLE credit_guaranty_project CHANGE id_naf_code id_naf_nace INT NOT NULL');
        $this->addSql('ALTER TABLE credit_guaranty_project ADD CONSTRAINT FK_A452D0255853FEED FOREIGN KEY (id_naf_nace) REFERENCES core_naf_nace (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('CREATE INDEX IDX_A452D0255853FEED ON credit_guaranty_project (id_naf_nace)');
    }
}
