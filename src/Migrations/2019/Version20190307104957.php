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
     * @param Schema $schema
     */
    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on "mysql".');

        $this->addSql(<<<ALTERTABLE
ALTER TABLE users
  CHANGE email email VARCHAR(191) NOT NULL AFTER id_user_type,
  CHANGE phone phone VARCHAR(50),
  CHANGE mobile mobile VARCHAR(50),
  CHANGE slack slack VARCHAR(191),
  CHANGE password_edited password_edited DATETIME,
  CHANGE updated updated DATETIME,
  CHANGE lastlogin lastlogin DATETIME
ALTERTABLE
        );

        $this->addSql(<<<ALTERTABLE
ALTER TABLE projects_comments
  ADD COLUMN id_client INT(11) NOT NULL AFTER id_project,
  ADD CONSTRAINT fk_projects_comments_id_client FOREIGN KEY (id_client) REFERENCES clients (id_client)
ALTERTABLE
        );

        $this->addSql(<<<ALTERTABLE
ALTER TABLE projects_status_history
  CHANGE content content MEDIUMTEXT,
  CHANGE numero_relance numero_relance INT(11),
  CHANGE updated updated DATETIME
ALTERTABLE
        );

        $this->addSql('ALTER TABLE projects DROP COLUMN id_prescripteur');

        $this->addSql('ALTER TABLE temporary_links_login CHANGE accessed accessed DATETIME');
        $this->addSql('ALTER TABLE temporary_links_login CHANGE updated updated DATETIME');
        $this->addSql('ALTER TABLE temporary_links_login DROP KEY id_link');
        $this->addSql('ALTER TABLE temporary_links_login ADD CONSTRAINT fk_temporary_links_login_id_client FOREIGN KEY (id_client) REFERENCES clients (id_client)');

        $this->addSql('DROP TABLE non_migrated_transactions');

        $this->addSql('DROP TABLE prospects');

        $this->addSql('DROP TABLE testimonial');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on "mysql".');

        $this->addSql(<<<ALTERTABLE
ALTER TABLE users
  CHANGE email email VARCHAR(191) NOT NULL AFTER name,
  CHANGE phone phone VARCHAR(50) NOT NULL,
  CHANGE mobile mobile VARCHAR(50) NOT NULL,
  CHANGE slack slack VARCHAR(191) NOT NULL,
  CHANGE password_edited password_edited DATETIME NOT NULL,
  CHANGE updated updated DATETIME NOT NULL,
  CHANGE lastlogin lastlogin DATETIME NOT NULL
ALTERTABLE
        );

        $this->addSql(<<<ALTERTABLE
ALTER TABLE projects_comments
  DROP COLUMN id_client
ALTERTABLE
        );

        $this->addSql(<<<ALTERTABLE
ALTER TABLE projects_status_history
  CHANGE content content MEDIUMTEXT NOT NULL,
  CHANGE numero_relance numero_relance INT(11) NOT NULL,
  CHANGE updated updated DATETIME NOT NULL
ALTERTABLE
        );

        $this->addSql('ALTER TABLE projects ADD COLUMN id_prescripteur INT(11) DEFAULT NULL AFTER id_target_company');
        $this->addSql('ALTER TABLE projects ADD KEY id_prescripteur (id_prescripteur)');

        $this->addSql('ALTER TABLE temporary_links_login CHANGE accessed accessed DATETIME NOT NULL');
        $this->addSql('ALTER TABLE temporary_links_login CHANGE updated updated DATETIME NOT NULL');
        $this->addSql('ALTER TABLE temporary_links_login DROP FOREIGN KEY fk_temporary_links_login_id_client');
        $this->addSql('ALTER TABLE temporary_links_login DROP KEY fk_temporary_links_login_id_client');
        $this->addSql('ALTER TABLE temporary_links_login ADD CONSTRAINT id_link UNIQUE (id_link)');

        $this->addSql(<<<CREATETABLE
CREATE TABLE non_migrated_transactions (
  id_transaction INT(11) NOT NULL,
  status TINYINT(1) NOT NULL COMMENT '0 = to be checked | 1 = abandoned',
  message VARCHAR(120) NOT NULL,
  PRIMARY KEY (id_transaction)
)
CREATETABLE
        );

        $this->addSql(<<<CREATETABLE
CREATE TABLE prospects (
  id_prospect INT(11) NOT NULL AUTO_INCREMENT,
  nom MEDIUMTEXT NOT NULL,
  prenom MEDIUMTEXT NOT NULL,
  email VARCHAR(191) NOT NULL,
  id_langue VARCHAR(3) NOT NULL,
  source VARCHAR(191) NOT NULL,
  source2 VARCHAR(191) NOT NULL,
  source3 VARCHAR(191) NOT NULL,
  slug_origine VARCHAR(191) NOT NULL,
  added DATETIME NOT NULL,
  updated DATETIME NOT NULL,
  PRIMARY KEY (id_prospect),
  KEY idx_prospects_email (email)
)
CREATETABLE
        );

        $this->addSql(<<<CREATETABLE
CREATE TABLE testimonial (
  id_testimonial INT(11) NOT NULL AUTO_INCREMENT,
  type VARCHAR(50) NOT NULL,
  slider_id TINYTEXT NOT NULL,
  id_client INT(11) NOT NULL,
  name VARCHAR(100) DEFAULT NULL,
  location VARCHAR(110) NOT NULL,
  projects VARCHAR(100) NOT NULL,
  quote VARCHAR(255) NOT NULL,
  info VARCHAR(255) NOT NULL,
  testimonial_page_title VARCHAR(120) NOT NULL,
  long_testimonial TEXT NOT NULL,
  video VARCHAR(255) DEFAULT NULL,
  video_caption VARCHAR(255) NOT NULL,
  feature_image VARCHAR(255) DEFAULT NULL,
  battenberg_image VARCHAR(255) DEFAULT NULL,
  status TINYINT(1) NOT NULL COMMENT '1: online, 0: offline',
  added DATETIME NOT NULL,
  updated DATETIME NOT NULL,
  PRIMARY KEY (id_testimonial)
)
CREATETABLE
        );
    }
}
