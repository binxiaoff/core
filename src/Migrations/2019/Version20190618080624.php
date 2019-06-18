<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20190618080624 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'CALSTECH-54 Rename the role constants';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('UPDATE staff SET roles = REPLACE(roles, \'STAFF_ROLE_OWNER\', \'ROLE_COMPANY_OWNER\')');
        $this->addSql('UPDATE project_participant SET roles = REPLACE(roles, \'COMPANY_ROLE_\', \'ROLE_PROJECT_\')');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('UPDATE staff SET roles = REPLACE(roles, \'ROLE_COMPANY_OWNER\', \'STAFF_ROLE_OWNER\')');
        $this->addSql('UPDATE project_participant SET roles = REPLACE(roles, \'ROLE_PROJECT_\', \'COMPANY_ROLE_\')');
    }
}
