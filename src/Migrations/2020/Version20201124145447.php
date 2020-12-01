<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20201124145447 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'CALS-2691 Remove unused fields';
    }

    public function up(Schema $schema) : void
    {
        $this->addSql('ALTER TABLE client_status DROP content');
        $this->addSql('ALTER TABLE clients DROP id_language, DROP title, DROP slug, DROP mobile');
        $this->addSql('ALTER TABLE file DROP description');
        $this->addSql('ALTER TABLE legal_document DROP first_time_instruction, DROP differential_instruction, DROP updated');
        $this->addSql('ALTER TABLE client_failed_login CHANGE retour error VARCHAR(191) DEFAULT NULL, CHANGE IP ip VARCHAR(191) DEFAULT NULL');
    }

    public function down(Schema $schema) : void
    {
        $this->addSql('ALTER TABLE client_status ADD content MEDIUMTEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE clients ADD id_language VARCHAR(2) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, ADD title VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, ADD slug VARCHAR(191) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, ADD mobile VARCHAR(35) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE file ADD description VARCHAR(191) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE legal_document ADD first_time_instruction MEDIUMTEXT CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, ADD differential_instruction MEDIUMTEXT CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, ADD updated DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE client_failed_login CHANGE error retour VARCHAR(191) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`');
    }
}
