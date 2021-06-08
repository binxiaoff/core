<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20210517235100 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '[Agency] Update member model';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE agency_agent ADD corporate_name VARCHAR(255) DEFAULT NULL, ADD added DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE agency_agent_member DROP FOREIGN KEY FK_925D3B7B699B6BAF');
        $this->addSql('ALTER TABLE agency_agent_member DROP FOREIGN KEY FK_925D3B7B6B3CA4B');
        $this->addSql('DROP INDEX IDX_925D3B7B699B6BAF ON agency_agent_member');
        $this->addSql('ALTER TABLE agency_agent_member ADD referent TINYINT(1) NOT NULL, ADD signatory TINYINT(1) NOT NULL, ADD project_function VARCHAR(200) DEFAULT NULL, ADD added DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', DROP added_by');
        $this->addSql('ALTER TABLE agency_agent_member ADD CONSTRAINT FK_925D3B7B6B3CA4B FOREIGN KEY (id_user) REFERENCES core_user (id)');
        $this->addSql('ALTER TABLE agency_borrower DROP FOREIGN KEY FK_C78A2C4F2B0DC78F');
        $this->addSql('ALTER TABLE agency_borrower DROP FOREIGN KEY FK_C78A2C4F699B6BAF');
        $this->addSql('ALTER TABLE agency_borrower DROP FOREIGN KEY FK_C78A2C4FAE4140F9');
        $this->addSql('ALTER TABLE agency_borrower DROP FOREIGN KEY FK_C78A2C4FF12E799E');
        $this->addSql('DROP INDEX IDX_C78A2C4F699B6BAF ON agency_borrower');
        $this->addSql('DROP INDEX UNIQ_C78A2C4F2B0DC78F ON agency_borrower');
        $this->addSql('DROP INDEX UNIQ_C78A2C4FAE4140F9 ON agency_borrower');
        $this->addSql('ALTER TABLE agency_borrower ADD head_office VARCHAR(255) DEFAULT NULL, ADD bank_institution VARCHAR(255) DEFAULT NULL, ADD bic VARCHAR(11) DEFAULT NULL, ADD iban VARCHAR(34) DEFAULT NULL, ADD added DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', DROP added_by, DROP id_signatory, DROP id_referent, DROP headquarter_address, CHANGE corporate_name corporate_name VARCHAR(255) DEFAULT NULL, CHANGE legal_form legal_form VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE agency_borrower ADD CONSTRAINT FK_C78A2C4FF12E799E FOREIGN KEY (id_project) REFERENCES agency_project (id)');
        $this->addSql('ALTER TABLE agency_borrower_member DROP FOREIGN KEY FK_5B36A3AA6B3CA4B');
        $this->addSql('ALTER TABLE agency_borrower_member ADD referent TINYINT(1) NOT NULL, ADD signatory TINYINT(1) NOT NULL, CHANGE id_user id_user INT NOT NULL');
        $this->addSql('ALTER TABLE agency_borrower_member ADD CONSTRAINT FK_5B36A3AA6B3CA4B FOREIGN KEY (id_user) REFERENCES core_user (id)');
        $this->addSql('ALTER TABLE agency_participation DROP FOREIGN KEY FK_E0ED689EAE4140F9');
        $this->addSql('DROP INDEX UNIQ_E0ED689EAE4140F9 ON agency_participation');
        $this->addSql('ALTER TABLE agency_participation ADD corporate_name VARCHAR(255) DEFAULT NULL, ADD legal_form VARCHAR(255) DEFAULT NULL, ADD head_office VARCHAR(255) DEFAULT NULL, ADD bank_institution VARCHAR(255) DEFAULT NULL, ADD bic VARCHAR(11) DEFAULT NULL, ADD iban VARCHAR(34) DEFAULT NULL, ADD added DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', DROP id_referent');
        $this->addSql('ALTER TABLE agency_participation_member ADD referent TINYINT(1) NOT NULL, ADD signatory TINYINT(1) NOT NULL, DROP type, CHANGE id_user id_user INT NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE agency_agent DROP corporate_name, DROP added');
        $this->addSql('ALTER TABLE agency_agent_member DROP FOREIGN KEY FK_925D3B7B6B3CA4B');
        $this->addSql('ALTER TABLE agency_agent_member ADD added_by INT NOT NULL, DROP referent, DROP signatory, DROP project_function, DROP added');
        $this->addSql('ALTER TABLE agency_agent_member ADD CONSTRAINT FK_925D3B7B699B6BAF FOREIGN KEY (added_by) REFERENCES core_user (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE agency_agent_member ADD CONSTRAINT FK_925D3B7B6B3CA4B FOREIGN KEY (id_user) REFERENCES core_user (id) ON UPDATE NO ACTION ON DELETE CASCADE');
        $this->addSql('CREATE INDEX IDX_925D3B7B699B6BAF ON agency_agent_member (added_by)');
        $this->addSql('ALTER TABLE agency_borrower DROP FOREIGN KEY FK_C78A2C4FF12E799E');
        $this->addSql('ALTER TABLE agency_borrower ADD added_by INT NOT NULL, ADD id_signatory INT DEFAULT NULL, ADD id_referent INT DEFAULT NULL, ADD headquarter_address VARCHAR(100) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, DROP head_office, DROP bank_institution, DROP bic, DROP iban, DROP added, CHANGE corporate_name corporate_name VARCHAR(100) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE legal_form legal_form VARCHAR(100) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE agency_borrower ADD CONSTRAINT FK_C78A2C4F2B0DC78F FOREIGN KEY (id_signatory) REFERENCES agency_borrower_member (id) ON UPDATE NO ACTION ON DELETE SET NULL');
        $this->addSql('ALTER TABLE agency_borrower ADD CONSTRAINT FK_C78A2C4F699B6BAF FOREIGN KEY (added_by) REFERENCES core_staff (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE agency_borrower ADD CONSTRAINT FK_C78A2C4FAE4140F9 FOREIGN KEY (id_referent) REFERENCES agency_borrower_member (id) ON UPDATE NO ACTION ON DELETE SET NULL');
        $this->addSql('ALTER TABLE agency_borrower ADD CONSTRAINT FK_C78A2C4FF12E799E FOREIGN KEY (id_project) REFERENCES agency_project (id) ON UPDATE NO ACTION ON DELETE CASCADE');
        $this->addSql('CREATE INDEX IDX_C78A2C4F699B6BAF ON agency_borrower (added_by)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_C78A2C4F2B0DC78F ON agency_borrower (id_signatory)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_C78A2C4FAE4140F9 ON agency_borrower (id_referent)');
        $this->addSql('ALTER TABLE agency_borrower_member DROP referent, DROP signatory, CHANGE id_user id_user INT DEFAULT NULL');
        $this->addSql('ALTER TABLE agency_participation ADD id_referent INT DEFAULT NULL, DROP corporate_name, DROP legal_form, DROP head_office, DROP bank_institution, DROP bic, DROP iban, DROP added');
        $this->addSql('ALTER TABLE agency_participation ADD CONSTRAINT FK_E0ED689EAE4140F9 FOREIGN KEY (id_referent) REFERENCES agency_participation_member (id) ON UPDATE NO ACTION ON DELETE SET NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_E0ED689EAE4140F9 ON agency_participation (id_referent)');
        $this->addSql('ALTER TABLE agency_participation_member ADD type VARCHAR(40) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, DROP referent, DROP signatory, CHANGE id_user id_user INT DEFAULT NULL');
    }
}
