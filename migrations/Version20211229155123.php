<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20211229155123 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '[Agency] CALS-5401 Record multiple bank account for agent';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE agency_agent_bank_account (id INT AUTO_INCREMENT NOT NULL, id_agent INT NOT NULL, public_id VARCHAR(36) NOT NULL, bank_account_label VARCHAR(255) DEFAULT NULL, bank_account_institution_name VARCHAR(255) DEFAULT NULL, bank_account_institution_address VARCHAR(255) DEFAULT NULL, bank_account_bic VARCHAR(11) DEFAULT NULL, bank_account_iban VARCHAR(34) DEFAULT NULL, UNIQUE INDEX UNIQ_CB5F8811B5B48B91 (public_id), INDEX IDX_CB5F8811C80EDDAD (id_agent), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE agency_agent_bank_account ADD CONSTRAINT FK_CB5F8811C80EDDAD FOREIGN KEY (id_agent) REFERENCES agency_agent (id) ON DELETE CASCADE');
        $this->addSql('INSERT INTO agency_agent_bank_account(id_agent, public_id, bank_account_label, bank_account_institution_name, bank_account_institution_address, bank_account_bic, bank_account_iban) SELECT id, public_id, bank_account_label, bank_account_institution_name, bank_account_institution_address, bank_account_bic, bank_account_iban FROM agency_agent');
        $this->addSql('ALTER TABLE agency_agent DROP bank_account_institution_address, DROP bank_account_bic, DROP bank_account_iban, DROP bank_account_institution_name, DROP bank_account_label');
        $this->addSql('ALTER TABLE agency_borrower ADD id_agent_bank_account INT DEFAULT NULL');
        $this->addSql('ALTER TABLE agency_borrower ADD CONSTRAINT FK_C78A2C4FA6CC036E FOREIGN KEY (id_agent_bank_account) REFERENCES agency_agent_bank_account (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_C78A2C4FA6CC036E ON agency_borrower (id_agent_bank_account)');
        $this->addSql('ALTER TABLE agency_participation ADD id_agent_bank_account INT DEFAULT NULL');
        $this->addSql('ALTER TABLE agency_participation ADD CONSTRAINT FK_E0ED689EA6CC036E FOREIGN KEY (id_agent_bank_account) REFERENCES agency_agent_bank_account (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_E0ED689EA6CC036E ON agency_participation (id_agent_bank_account)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE agency_borrower DROP FOREIGN KEY FK_C78A2C4FA6CC036E');
        $this->addSql('ALTER TABLE agency_participation DROP FOREIGN KEY FK_E0ED689EA6CC036E');
        $this->addSql('ALTER TABLE agency_agent ADD bank_account_institution_name VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, ADD bank_account_bic VARCHAR(11) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, ADD bank_account_iban VARCHAR(34) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, ADD bank_account_institution_address VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, ADD bank_account_label VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`');
        // This line below is to get some data from the to be delete table agency_agent_bank_account however it will take the last inserted agent bank account
        $this->addSql('UPDATE agency_agent a JOIN agency_agent_bank_account aaba ON a.id = aaba.id_agent SET a.bank_account_label = aaba.bank_account_label, a.bank_account_institution_name = aaba.bank_account_institution_name, a.bank_account_institution_address = aaba.bank_account_institution_address, a.bank_account_bic = aaba.bank_account_bic, a.bank_account_iban = aaba.bank_account_iban WHERE 1=1');
        $this->addSql('DROP TABLE agency_agent_bank_account');
        $this->addSql('DROP INDEX IDX_C78A2C4FA6CC036E ON agency_borrower');
        $this->addSql('ALTER TABLE agency_borrower DROP id_agent_bank_account');
        $this->addSql('DROP INDEX IDX_E0ED689EA6CC036E ON agency_participation');
        $this->addSql('ALTER TABLE agency_participation DROP id_agent_bank_account');
    }
}
