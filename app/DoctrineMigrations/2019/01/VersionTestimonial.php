<?php declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class VersionTestimonial extends AbstractMigration
{
    /**
     * @param Schema $schema
     *
     * @throws \Doctrine\DBAL\DBALException
     * @throws \Doctrine\DBAL\Migrations\AbortMigrationException
     */
    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on "mysql".');

        $this->addSql('DROP TABLE testimonial');
    }

    /**
     * @param Schema $schema
     *
     * @throws \Doctrine\DBAL\DBALException
     * @throws \Doctrine\DBAL\Migrations\AbortMigrationException
     */
    public function down(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on "mysql".');

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
