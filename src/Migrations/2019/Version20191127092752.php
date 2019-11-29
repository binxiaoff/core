<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20191127092752 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'CALS-508 update the attachement type';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('TRUNCATE TABLE project_attachment');
        $this->addSql('DROP TABLE project_attachment_type');
        $this->addSql('DROP TABLE project_attachment_type_category');
        $this->addSql('ALTER TABLE attachment DROP FOREIGN KEY FK_795FD9BB7FE4B2B');
        $this->addSql('DROP INDEX IDX_795FD9BB7FE4B2B ON attachment');
        $this->addSql('DROP TABLE attachment_type');
        $this->addSql('SET FOREIGN_KEY_CHECKS=0');
        $this->addSql('TRUNCATE TABLE attachment');
        $this->addSql('SET FOREIGN_KEY_CHECKS=1');
        $this->addSql('ALTER TABLE attachment ADD type VARCHAR(60) NOT NULL, DROP id_type');
        $this->addSql('ALTER TABLE attachment ADD public_id VARCHAR(36) NOT NULL AFTER id');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_795FD9BBB5B48B91 ON attachment (public_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('CREATE TABLE attachment_type (id INT AUTO_INCREMENT NOT NULL, label VARCHAR(100) NOT NULL, downloadable TINYINT(1) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE project_attachment_type_category (id INT AUTO_INCREMENT NOT NULL, label VARCHAR(191) NOT NULL, name VARCHAR(191) DEFAULT NULL, `rank` SMALLINT NOT NULL, UNIQUE INDEX UNIQ_72F02865EA750E8 (label), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE project_attachment_type (id INT AUTO_INCREMENT NOT NULL, id_category INT DEFAULT NULL, id_type INT NOT NULL, `rank` SMALLINT NOT NULL, max_items SMALLINT DEFAULT NULL, name VARCHAR(191) DEFAULT NULL, INDEX IDX_4C9C36E65697F554 (id_category), UNIQUE INDEX UNIQ_4C9C36E67FE4B2B (id_type), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE project_attachment_type ADD CONSTRAINT FK_4C9C36E65697F554 FOREIGN KEY (id_category) REFERENCES project_attachment_type_category (id)');
        $this->addSql('ALTER TABLE project_attachment_type ADD CONSTRAINT FK_4C9C36E67FE4B2B FOREIGN KEY (id_type) REFERENCES attachment_type (id)');
        $this->addSql('ALTER TABLE attachment ADD id_type INT DEFAULT NULL, DROP type');
        $this->addSql('ALTER TABLE attachment ADD CONSTRAINT FK_795FD9BB7FE4B2B FOREIGN KEY (id_type) REFERENCES attachment_type (id)');
        $this->addSql('CREATE INDEX IDX_795FD9BB7FE4B2B ON attachment (id_type)');
        $this->addSql('DROP INDEX UNIQ_795FD9BBB5B48B91 ON attachment');
        $this->addSql('ALTER TABLE attachment DROP public_id');
    }
}
