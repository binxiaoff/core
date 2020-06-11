<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20200611083408 extends AbstractMigration
{
    /**
     * @return string
     */
    public function getDescription(): string
    {
        return 'CALS-1362 Rename hash column to public_id (PublicizeIdentity)';
    }

    /**
     * @param Schema $schema
     */
    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE project RENAME COLUMN hash TO public_id');
        $this->addSql('ALTER TABLE project DROP INDEX hash, ADD UNIQUE INDEX UNIQ_2FB3D0EEB5B48B91 (public_id)');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE project RENAME COLUMN public_id TO hash');
        $this->addSql('ALTER TABLE project DROP INDEX UNIQ_2FB3D0EEB5B48B91, ADD INDEX hash (public_id)');
    }
}
