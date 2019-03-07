<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190307104957 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'Remove prospects table';
    }

    /**
     * @inheritdoc
     */
    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on "mysql".');

        $this->addSql('DROP TABLE prospects');
        $this->addSql('DROP TABLE testimonial');
        $this->addSql('ALTER TABLE projects_comments ADD id_client INT NOT NULL');
        $this->addSql('ALTER TABLE projects_comments ADD CONSTRAINT FK_350375C3E173B1B8 FOREIGN KEY (id_client) REFERENCES clients (id_client)');
        $this->addSql('CREATE INDEX IDX_350375C3E173B1B8 ON projects_comments (id_client)');
        $this->addSql('ALTER TABLE projects_status_history CHANGE content content MEDIUMTEXT DEFAULT NULL, CHANGE numero_relance numero_relance INT DEFAULT NULL');
        $this->addSql('ALTER TABLE users CHANGE slack slack VARCHAR(191) DEFAULT NULL');
    }

    /**
     * @inheritdoc
     */
    public function down(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on "mysql".');

        $this->addSql('CREATE TABLE prospects (id_prospect INT AUTO_INCREMENT NOT NULL, nom MEDIUMTEXT NOT NULL COLLATE utf8mb4_unicode_ci, prenom MEDIUMTEXT NOT NULL COLLATE utf8mb4_unicode_ci, email VARCHAR(191) NOT NULL COLLATE utf8mb4_unicode_ci, id_langue VARCHAR(3) NOT NULL COLLATE utf8mb4_unicode_ci, source VARCHAR(191) NOT NULL COLLATE utf8mb4_unicode_ci, source2 VARCHAR(191) NOT NULL COLLATE utf8mb4_unicode_ci, source3 VARCHAR(191) NOT NULL COLLATE utf8mb4_unicode_ci, slug_origine VARCHAR(191) NOT NULL COLLATE utf8mb4_unicode_ci, added DATETIME NOT NULL COMMENT \'(DC2Type:datetime)\', updated DATETIME NOT NULL COMMENT \'(DC2Type:datetime)\', INDEX idx_prospects_email (email), PRIMARY KEY(id_prospect)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE testimonial (id_testimonial INT AUTO_INCREMENT NOT NULL, type VARCHAR(50) NOT NULL COLLATE utf8mb4_unicode_ci, slider_id TINYTEXT NOT NULL COLLATE utf8mb4_unicode_ci, id_client INT NOT NULL, name VARCHAR(100) DEFAULT NULL COLLATE utf8mb4_unicode_ci, location VARCHAR(110) NOT NULL COLLATE utf8mb4_unicode_ci, projects VARCHAR(100) NOT NULL COLLATE utf8mb4_unicode_ci, quote VARCHAR(255) NOT NULL COLLATE utf8mb4_unicode_ci, info VARCHAR(255) NOT NULL COLLATE utf8mb4_unicode_ci, testimonial_page_title VARCHAR(120) NOT NULL COLLATE utf8mb4_unicode_ci, long_testimonial TEXT NOT NULL COLLATE utf8mb4_unicode_ci, video VARCHAR(255) DEFAULT NULL COLLATE utf8mb4_unicode_ci, video_caption VARCHAR(255) NOT NULL COLLATE utf8mb4_unicode_ci, feature_image VARCHAR(255) DEFAULT NULL COLLATE utf8mb4_unicode_ci, battenberg_image VARCHAR(255) DEFAULT NULL COLLATE utf8mb4_unicode_ci, status TINYINT(1) NOT NULL, added DATETIME NOT NULL COMMENT \'(DC2Type:datetime)\', updated DATETIME NOT NULL COMMENT \'(DC2Type:datetime)\', PRIMARY KEY(id_testimonial)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE projects_comments DROP FOREIGN KEY FK_350375C3E173B1B8');
        $this->addSql('DROP INDEX IDX_350375C3E173B1B8 ON projects_comments');
        $this->addSql('ALTER TABLE projects_comments DROP id_client');
        $this->addSql('ALTER TABLE projects_status_history CHANGE content content MEDIUMTEXT NOT NULL COLLATE utf8mb4_unicode_ci, CHANGE numero_relance numero_relance INT NOT NULL');
        $this->addSql('ALTER TABLE users CHANGE slack slack VARCHAR(191) NOT NULL COLLATE utf8mb4_unicode_ci');
    }
}
