<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20210403132736 extends AbstractMigration
{
    /**
     * @return string
     */
    public function getDescription() : string
    {
        return 'Replace association target of addedBy field in FileDownload from Staff to User';
    }

    /**
     * @param Schema $schema
     */
    public function up(Schema $schema) : void
    {
        $this->addSql('ALTER TABLE core_file_download DROP FOREIGN KEY FK_41EFE7B2699B6BAF');
        $this->addSql('UPDATE core_file_download cfd INNER JOIN core_staff cs on cfd.added_by = cs.id SET cfd.added_by = cs.id_user WHERE TRUE');
        $this->addSql('ALTER TABLE core_file_download ADD CONSTRAINT FK_41EFE7B2699B6BAF FOREIGN KEY (added_by) REFERENCES core_user (id)');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema) : void
    {
        $this->addSql('ALTER TABLE core_file_download DROP FOREIGN KEY FK_41EFE7B2699B6BAF');
        $this->addSql('UPDATE core_file_download cfd SET cfd.added_by = (SELECT id FROM unilend.core_staff LIMIT 1) WHERE TRUE');
        $this->addSql('ALTER TABLE core_file_download ADD CONSTRAINT FK_41EFE7B2699B6BAF FOREIGN KEY (added_by) REFERENCES core_staff (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
    }
}
