<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200921135855 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'CALS-2450 alter acceptations_legal_docs';
    }

    public function up(Schema $schema) : void
    {
        $this->addSql('ALTER TABLE acceptations_legal_docs MODIFY id_acceptation INT NOT NULL');
        $this->addSql('ALTER TABLE acceptations_legal_docs DROP FOREIGN KEY FK_F1D2E432E173B1B8');
        $this->addSql('DROP INDEX IDX_F1D2E432E173B1B8 ON acceptations_legal_docs');
        $this->addSql('ALTER TABLE acceptations_legal_docs DROP PRIMARY KEY');
        $this->addSql('ALTER TABLE acceptations_legal_docs ADD public_id VARCHAR(36) NOT NULL, DROP relative_file_path, DROP updated, CHANGE id_acceptation id INT NOT NULL, CHANGE id_client added_by INT NOT NULL');
        $this->addSql('ALTER TABLE acceptations_legal_docs ADD CONSTRAINT FK_F1D2E432699B6BAF FOREIGN KEY (added_by) REFERENCES staff (id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_F1D2E432B5B48B91 ON acceptations_legal_docs (public_id)');
        $this->addSql('CREATE INDEX IDX_F1D2E432699B6BAF ON acceptations_legal_docs (added_by)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_F1D2E4327F757BBC699B6BAF ON acceptations_legal_docs (id_legal_doc, added_by)');
        $this->addSql('ALTER TABLE acceptations_legal_docs ADD PRIMARY KEY (id)');
        $this->addSql('ALTER TABLE acceptations_legal_docs MODIFY id INT AUTO_INCREMENT NOT NULL');
    }

    public function down(Schema $schema) : void
    {
        $this->addSql('ALTER TABLE acceptations_legal_docs MODIFY id INT NOT NULL');
        $this->addSql('ALTER TABLE acceptations_legal_docs DROP FOREIGN KEY FK_F1D2E432699B6BAF');
        $this->addSql('DROP INDEX UNIQ_F1D2E432B5B48B91 ON acceptations_legal_docs');
        $this->addSql('DROP INDEX IDX_F1D2E432699B6BAF ON acceptations_legal_docs');
        $this->addSql('DROP INDEX UNIQ_F1D2E4327F757BBC699B6BAF ON acceptations_legal_docs');
        $this->addSql('ALTER TABLE acceptations_legal_docs DROP PRIMARY KEY');
        $this->addSql('ALTER TABLE acceptations_legal_docs ADD relative_file_path VARCHAR(191) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, ADD updated DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', DROP public_id, CHANGE id id_acceptation INT NOT NULL, CHANGE added_by id_client INT NOT NULL');
        $this->addSql('ALTER TABLE acceptations_legal_docs ADD CONSTRAINT FK_F1D2E432E173B1B8 FOREIGN KEY (id_client) REFERENCES clients (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('CREATE INDEX IDX_F1D2E432E173B1B8 ON acceptations_legal_docs (id_client)');
        $this->addSql('ALTER TABLE acceptations_legal_docs ADD PRIMARY KEY (id_acceptation)');
        $this->addSql('ALTER TABLE acceptations_legal_docs MODIFY id_acceptation INT AUTO_INCREMENT NOT NULL');
    }
}
