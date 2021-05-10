<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210510135157 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'CALS-3413 [Core] add naf/nace';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE core_naf_nace (id INT AUTO_INCREMENT NOT NULL, naf_code VARCHAR(5) NOT NULL, nace_code VARCHAR(7) NOT NULL, naf_title VARCHAR(255) NOT NULL, nace_title VARCHAR(255) NOT NULL, UNIQUE INDEX UNIQ_B74A64DBBF3D7168609C91B2 (naf_code, nace_code), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE core_naf_nace');
    }
}
