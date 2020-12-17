<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20200327144434 extends AbstractMigration
{
    /**
     * @return string
     */
    public function getDescription(): string
    {
        return 'CALS-1335 Replace phone fields column type with simple string';
    }

    /**
     * @param Schema $schema
     */
    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE clients CHANGE phone phone VARCHAR(35) DEFAULT NULL, CHANGE mobile mobile VARCHAR(35) DEFAULT NULL');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE clients CHANGE phone phone VARCHAR(35) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci` COMMENT \'(DC2Type:phone_number)\', CHANGE mobile mobile VARCHAR(35) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci` COMMENT \'(DC2Type:phone_number)\'');
    }
}
