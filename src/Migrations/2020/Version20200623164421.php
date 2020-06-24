<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20200623164421 extends AbstractMigration
{
    /**
     * @return string
     */
    public function getDescription(): string
    {
        return 'CALS-1837 Add participationSpecific NDA';
    }

    /**
     * @param Schema $schema
     */
    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE project_participation ADD nda_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE project_participation ADD CONSTRAINT FK_7FC47549F5149240 FOREIGN KEY (nda_id) REFERENCES file (id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_7FC47549F5149240 ON project_participation (nda_id)');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE project_participation DROP FOREIGN KEY FK_7FC47549F5149240');
        $this->addSql('DROP INDEX UNIQ_7FC47549F5149240 ON project_participation');
        $this->addSql('ALTER TABLE project_participation DROP nda_id');
    }
}
