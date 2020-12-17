<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20200605155514 extends AbstractMigration
{
    /**
     * @return string
     */
    public function getDescription(): string
    {
        return 'CALS-1614 Add privileged contact name';
    }

    /**
     * @param Schema $schema
     */
    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE project ADD privileged_contact_first_name VARCHAR(255) NOT NULL, ADD privileged_contact_last_name VARCHAR(255) NOT NULL, ADD privileged_contact_parent_unit VARCHAR(255) NOT NULL, ADD privileged_contact_occupation VARCHAR(255) NOT NULL, ADD privileged_contact_email VARCHAR(255) NOT NULL, ADD privileged_contact_phone VARCHAR(35) NOT NULL');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE project DROP privileged_contact_first_name, DROP privileged_contact_last_name, DROP privileged_contact_parent_unit, DROP privileged_contact_occupation, DROP privileged_contact_email, DROP privileged_contact_phone');
    }
}
