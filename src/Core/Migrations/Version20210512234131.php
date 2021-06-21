<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210512234131 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'CALS-3769 Add column to manage the grant permissions';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE credit_guaranty_staff_permission DROP INDEX IDX_ED295656ACEBB2A2, ADD UNIQUE INDEX UNIQ_ED295656ACEBB2A2 (id_staff)');
        $this->addSql('ALTER TABLE credit_guaranty_staff_permission ADD grant_permissions INT NOT NULL COMMENT \'(DC2Type:bitmask)\'');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE credit_guaranty_staff_permission DROP INDEX UNIQ_ED295656ACEBB2A2, ADD INDEX IDX_ED295656ACEBB2A2 (id_staff)');
        $this->addSql('ALTER TABLE credit_guaranty_staff_permission DROP grant_permissions');
    }
}
