<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200924160550 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'CALS-2450 Replace staff by client for acceptations_legal_docs';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE acceptations_legal_docs DROP FOREIGN KEY FK_F1D2E432699B6BAF');
        $this->addSql('DROP INDEX IDX_F1D2E432699B6BAF ON acceptations_legal_docs');
        $this->addSql('DROP INDEX UNIQ_F1D2E4327F757BBC699B6BAF ON acceptations_legal_docs');
        $this->addSql('ALTER TABLE acceptations_legal_docs CHANGE added_by accepted_by INT NOT NULL');
        $this->addSql('UPDATE acceptations_legal_docs ald INNER JOIN staff s ON s.id = accepted_by SET accepted_by = s.id_client');
        $this->addSql('ALTER TABLE acceptations_legal_docs ADD CONSTRAINT FK_F1D2E432BD57FA7C FOREIGN KEY (accepted_by) REFERENCES clients (id)');
        $this->addSql('CREATE INDEX IDX_F1D2E432BD57FA7C ON acceptations_legal_docs (accepted_by)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_F1D2E4327F757BBCBD57FA7C ON acceptations_legal_docs (id_legal_doc, accepted_by)');
        $this->addSql('DROP INDEX IDX_C82E74B5B48B91 ON clients');
        $this->addSql('DROP INDEX IDX_C82E74E7927C74 ON clients');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE acceptations_legal_docs DROP FOREIGN KEY FK_F1D2E432BD57FA7C');
        $this->addSql('DROP INDEX IDX_F1D2E432BD57FA7C ON acceptations_legal_docs');
        $this->addSql('DROP INDEX UNIQ_F1D2E4327F757BBCBD57FA7C ON acceptations_legal_docs');
        $this->addSql('ALTER TABLE acceptations_legal_docs CHANGE accepted_by added_by INT NOT NULL');
        $this->addSql('UPDATE acceptations_legal_docs ald INNER JOIN staff s ON s.id_client = added_by SET accepted_by = s.id');
        $this->addSql('ALTER TABLE acceptations_legal_docs ADD CONSTRAINT FK_F1D2E432699B6BAF FOREIGN KEY (added_by) REFERENCES staff (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('CREATE INDEX IDX_F1D2E432699B6BAF ON acceptations_legal_docs (added_by)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_F1D2E4327F757BBC699B6BAF ON acceptations_legal_docs (id_legal_doc, added_by)');
        $this->addSql('CREATE INDEX IDX_C82E74B5B48B91 ON clients (public_id)');
        $this->addSql('CREATE INDEX IDX_C82E74E7927C74 ON clients (email)');
    }
}
