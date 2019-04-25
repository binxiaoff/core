<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20190424151727 extends AbstractMigration
{
    /**
     * @return string
     */
    public function getDescription(): string
    {
        return 'Clean clients table';
    }

    /**
     * @param Schema $schema
     *
     * @throws DBALException
     */
    public function up(Schema $schema): void
    {
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on "mysql".');

        $this->addSql('DROP INDEX idx_client_nom ON clients');
        $this->addSql(
            <<<'ALTER'
ALTER TABLE clients
  CHANGE id_langue id_language VARCHAR(2) NOT NULL,
  CHANGE civilite title VARCHAR(255) DEFAULT NULL,
  CHANGE nom last_name VARCHAR(191) DEFAULT NULL,
  CHANGE nom_usage preferred_name VARCHAR(191) DEFAULT NULL,
  CHANGE prenom first_name VARCHAR(191) DEFAULT NULL,
  CHANGE naissance date_of_birth DATE DEFAULT NULL, 
  CHANGE id_pays_naissance id_birth_country INT DEFAULT NULL,
  CHANGE ville_naissance birth_city VARCHAR(191) DEFAULT NULL,
  CHANGE id_nationalite id_nationaliy INT DEFAULT NULL,
  CHANGE telephone phone VARCHAR(191) DEFAULT NULL,
  CHANGE secrete_question security_question VARCHAR(191) DEFAULT NULL,
  CHANGE secrete_reponse security_answer VARCHAR(191) DEFAULT NULL,
  DROP fonction,
  DROP us_person,
  DROP funds_origin,
  DROP funds_origin_detail,
  DROP etape_inscription_preteur,
  DROP status_inscription_preteur,
  DROP sponsor_code,
  DROP source,
  DROP source2,
  DROP source3,
  DROP slug_origine,
  DROP origine,
  DROP optin1, 
  DROP optin2, 
  CHANGE lastlogin last_login DATETIME DEFAULT NULL
ALTER
        );
        $this->addSql('CREATE INDEX IDX_C82E74C808BA5A ON clients (last_name)');
        $this->addSql('ALTER TABLE clients RENAME INDEX hash TO IDX_C82E74D1B862B8');
        $this->addSql('ALTER TABLE clients RENAME INDEX email TO IDX_C82E74E7927C74');
        $this->addSql('ALTER TABLE clients RENAME INDEX idx_clients_id_client_status_history TO IDX_C82E74DF1ED241');
        $this->addSql('ALTER TABLE clients_history DROP type');
    }

    /**
     * @param Schema $schema
     *
     * @throws DBALException
     */
    public function down(Schema $schema): void
    {
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on "mysql".');

        $this->addSql('DROP INDEX IDX_C82E74C808BA5A ON clients');
        $this->addSql(
            <<<'ALTER'
ALTER TABLE clients
  CHANGE id_language id_langue VARCHAR(5) NOT NULL COLLATE utf8mb4_unicode_ci,
  CHANGE title civilite VARCHAR(255) DEFAULT NULL COLLATE utf8mb4_unicode_ci,
  CHANGE last_name nom VARCHAR(191) DEFAULT NULL COLLATE utf8mb4_unicode_ci,
  CHANGE preferred_name nom_usage VARCHAR(191) DEFAULT NULL COLLATE utf8mb4_unicode_ci,
  CHANGE first_name prenom VARCHAR(191) DEFAULT NULL COLLATE utf8mb4_unicode_ci,
  ADD fonction VARCHAR(191) DEFAULT NULL COLLATE utf8mb4_unicode_ci,
  CHANGE date_of_birth naissance DATE DEFAULT NULL,
  CHANGE id_birth_country id_pays_naissance INT DEFAULT NULL,
  CHANGE birth_city ville_naissance VARCHAR(191) DEFAULT NULL COLLATE utf8mb4_unicode_ci,
  CHANGE id_nationaliy id_nationalite INT DEFAULT NULL,
  ADD us_person TINYINT(1) DEFAULT NULL,
  CHANGE phone telephone VARCHAR(191) DEFAULT NULL COLLATE utf8mb4_unicode_ci,
  CHANGE security_question secrete_question VARCHAR(191) DEFAULT NULL COLLATE utf8mb4_unicode_ci,
  CHANGE security_answer secrete_reponse VARCHAR(191) DEFAULT NULL COLLATE utf8mb4_unicode_ci,
  ADD funds_origin SMALLINT DEFAULT NULL,
  ADD funds_origin_detail VARCHAR(255) DEFAULT NULL COLLATE utf8mb4_unicode_ci,
  ADD etape_inscription_preteur SMALLINT DEFAULT NULL,
  ADD status_inscription_preteur SMALLINT DEFAULT NULL,
  ADD sponsor_code VARCHAR(50) DEFAULT NULL COLLATE utf8mb4_unicode_ci,
  ADD source VARCHAR(191) DEFAULT NULL COLLATE utf8mb4_unicode_ci,
  ADD source2 VARCHAR(191) DEFAULT NULL COLLATE utf8mb4_unicode_ci,
  ADD source3 VARCHAR(191) DEFAULT NULL COLLATE utf8mb4_unicode_ci,
  ADD slug_origine VARCHAR(191) DEFAULT NULL COLLATE utf8mb4_unicode_ci,
  ADD origine SMALLINT DEFAULT NULL,
  ADD optin1 SMALLINT DEFAULT NULL,
  ADD optin2 SMALLINT DEFAULT NULL,
  CHANGE last_login lastlogin DATETIME DEFAULT NULL
ALTER
);
        $this->addSql('CREATE INDEX idx_client_nom ON clients (nom)');
        $this->addSql('ALTER TABLE clients RENAME INDEX idx_c82e74d1b862b8 TO hash');
        $this->addSql('ALTER TABLE clients RENAME INDEX idx_c82e74df1ed241 TO idx_clients_id_client_status_history');
        $this->addSql('ALTER TABLE clients RENAME INDEX idx_c82e74e7927c74 TO email');
        $this->addSql('ALTER TABLE clients_history ADD type SMALLINT NOT NULL');
    }
}
