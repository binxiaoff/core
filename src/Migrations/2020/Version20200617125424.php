<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20200617125424 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'CALS-1480 Update project organizer';
    }

    /**
     * @param Schema $schema
     */
    public function up(Schema $schema): void
    {
        $this->addSql('UPDATE project_organizer SET roles = JSON_ARRAY_APPEND(roles, \'$\', \'agent\') WHERE JSON_SEARCH(roles, \'one\', \'security_trustee\') OR JSON_SEARCH(roles, \'one\', \'loan_officer\')');

        foreach (['loan_officer', 'security_trustee'] as $removedRole) {
            $this->addSql("UPDATE project_organizer SET roles = REPLACE(roles, '\"{$removedRole}\",', '')");
            $this->addSql("UPDATE project_organizer SET roles = REPLACE(roles, ', \"{$removedRole}\"', '')");
            $this->addSql("UPDATE project_organizer SET roles = REPLACE(roles, '\"{$removedRole}\"', '')");
        }
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema): void
    {
        // This migration down result in data loss
        $this->addSql('UPDATE project_organizer SET roles = REPLACE(roles, \'agent\', \'security_trustee\')');
    }
}
