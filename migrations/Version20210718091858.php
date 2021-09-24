<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20210718091858 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '[Agency] Add DELETE CASCADE to Borrower::id_project ';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE agency_borrower DROP FOREIGN KEY FK_C78A2C4FF12E799E');
        $this->addSql('ALTER TABLE agency_borrower ADD CONSTRAINT FK_C78A2C4FF12E799E FOREIGN KEY (id_project) REFERENCES agency_project (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE agency_borrower DROP FOREIGN KEY FK_C78A2C4FF12E799E');
        $this->addSql('ALTER TABLE agency_borrower ADD CONSTRAINT FK_C78A2C4FF12E799E FOREIGN KEY (id_project) REFERENCES agency_project (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
    }
}
