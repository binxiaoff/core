<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20210504175321 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'CALS-3749 Add missing on delete cascade';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE agency_participation DROP FOREIGN KEY FK_E0ED689EF0C7A460');
        $this->addSql('ALTER TABLE agency_participation ADD CONSTRAINT FK_E0ED689EF0C7A460 FOREIGN KEY (id_participation_pool) REFERENCES agency_participation_pool (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE agency_participation_pool DROP FOREIGN KEY FK_9D542F1FF12E799E');
        $this->addSql('ALTER TABLE agency_participation_pool ADD CONSTRAINT FK_9D542F1FF12E799E FOREIGN KEY (id_project) REFERENCES agency_project (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE agency_participation DROP FOREIGN KEY FK_E0ED689EF0C7A460');
        $this->addSql('ALTER TABLE agency_participation ADD CONSTRAINT FK_E0ED689EF0C7A460 FOREIGN KEY (id_participation_pool) REFERENCES agency_participation_pool (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE agency_participation_pool DROP FOREIGN KEY FK_9D542F1FF12E799E');
        $this->addSql('ALTER TABLE agency_participation_pool ADD CONSTRAINT FK_9D542F1FF12E799E FOREIGN KEY (id_project) REFERENCES agency_project (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
    }
}
