<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20210403230031 extends AbstractMigration
{
    /**
     * @return string
     */
    public function getDescription(): string
    {
        return 'Replace foreign key targeting Staff to targeting User for FileVersion';
    }

    /**
     * @param Schema $schema
     */
    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE core_file_version DROP FOREIGN KEY FK_49CAD320699B6BAF');
        $this->addSql('UPDATE core_file_version cfv INNER JOIN core_staff cs ON cfv.added_by = cs.id SET cfv.added_by = cs.id_user WHERE TRUE');
        $this->addSql('ALTER TABLE core_file_version ADD CONSTRAINT FK_49CAD320699B6BAF FOREIGN KEY (added_by) REFERENCES core_user (id)');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE core_file_version DROP FOREIGN KEY FK_49CAD320699B6BAF');
        $this->addSql('UPDATE core_file_version cfv SET cfv.added_by = (SELECT id FROM core_staff LIMIT 1) WHERE TRUE');
        $this->addSql('ALTER TABLE core_file_version ADD CONSTRAINT FK_49CAD320699B6BAF FOREIGN KEY (added_by) REFERENCES core_staff (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
    }
}
