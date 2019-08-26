<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20190826123456 extends AbstractMigration
{
    /**
     * @return string
     */
    public function getDescription(): string
    {
        return 'TECH-82 Remove old data tables';
    }

    /**
     * @param Schema $schema
     *
     * @throws DBALException
     */
    public function up(Schema $schema): void
    {
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE insee');
        $this->addSql('DROP TABLE insee_pays');
        $this->addSql('DROP TABLE nationalites');
        $this->addSql('DROP TABLE nationalites_v2');
        $this->addSql('DROP TABLE pays');
        $this->addSql('DROP TABLE villes');
        $this->addSql('ALTER TABLE clients DROP insee_birth');
    }

    /**
     * @param Schema $schema
     *
     * @throws DBALException
     */
    public function down(Schema $schema): void
    {
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE insee (id_insee INT AUTO_INCREMENT NOT NULL, CDC SMALLINT DEFAULT NULL, CHEFLIEU SMALLINT DEFAULT NULL, REG SMALLINT DEFAULT NULL, DEP SMALLINT DEFAULT NULL, COM SMALLINT DEFAULT NULL, AR SMALLINT DEFAULT NULL, CT SMALLINT DEFAULT NULL, TNCC SMALLINT DEFAULT NULL, ARTMAJ VARCHAR(4) DEFAULT NULL COLLATE utf8mb4_unicode_ci, NCC VARCHAR(50) DEFAULT NULL COLLATE utf8mb4_unicode_ci, ARTMIN VARCHAR(4) DEFAULT NULL COLLATE utf8mb4_unicode_ci, NCCENR VARCHAR(50) DEFAULT NULL COLLATE utf8mb4_unicode_ci, INDEX NCCENR (NCCENR), INDEX NCC (NCC), PRIMARY KEY(id_insee)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE insee_pays (id_insee_pays INT AUTO_INCREMENT NOT NULL, CODEISO2 VARCHAR(2) NOT NULL COLLATE utf8mb4_unicode_ci, COG VARCHAR(5) DEFAULT NULL COLLATE utf8mb4_unicode_ci, ACTUAL INT DEFAULT NULL, CAPAY VARCHAR(5) DEFAULT NULL COLLATE utf8mb4_unicode_ci, CRPAY INT DEFAULT NULL, ANI INT DEFAULT NULL, LIBCOG VARCHAR(44) DEFAULT NULL COLLATE utf8mb4_unicode_ci, LIBENR VARCHAR(54) DEFAULT NULL COLLATE utf8mb4_unicode_ci, ANCNOM VARCHAR(20) DEFAULT NULL COLLATE utf8mb4_unicode_ci, INDEX COG (COG), INDEX LIBCOG (LIBCOG), INDEX codeiso2 (CODEISO2), PRIMARY KEY(id_insee_pays)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE nationalites (id_nationalite INT AUTO_INCREMENT NOT NULL, code_pays VARCHAR(9) NOT NULL COLLATE utf8mb4_unicode_ci, etat VARCHAR(52) DEFAULT NULL COLLATE utf8mb4_unicode_ci, fr_m VARCHAR(50) DEFAULT NULL COLLATE utf8mb4_unicode_ci, fr_f VARCHAR(50) DEFAULT NULL COLLATE utf8mb4_unicode_ci, UNIQUE INDEX UNIQ_ADF626E1274566F (code_pays), PRIMARY KEY(id_nationalite)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE nationalites_v2 (id_nationalite INT AUTO_INCREMENT NOT NULL, fr_f VARCHAR(191) DEFAULT NULL COLLATE utf8mb4_unicode_ci, ordre INT NOT NULL, PRIMARY KEY(id_nationalite)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE pays (id_pays INT AUTO_INCREMENT NOT NULL, fr VARCHAR(191) DEFAULT NULL COLLATE utf8mb4_unicode_ci, iso VARCHAR(2) NOT NULL COLLATE utf8mb4_unicode_ci, ordre INT NOT NULL, vigilance_status SMALLINT NOT NULL, PRIMARY KEY(id_pays)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE villes (id_ville INT AUTO_INCREMENT NOT NULL, ville VARCHAR(191) NOT NULL COLLATE utf8mb4_unicode_ci, insee VARCHAR(16) DEFAULT NULL COLLATE utf8mb4_unicode_ci, cp VARCHAR(16) DEFAULT NULL COLLATE utf8mb4_unicode_ci, num_departement VARCHAR(16) DEFAULT NULL COLLATE utf8mb4_unicode_ci, active TINYINT(1) DEFAULT \'1\' NOT NULL, added DATETIME NOT NULL, updated DATETIME NOT NULL, INDEX idx_villes_ville_cp (ville, cp), UNIQUE INDEX uq_ville_insee_cp (ville, insee, cp), INDEX idx_villes_cp (cp), PRIMARY KEY(id_ville)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE clients ADD insee_birth VARCHAR(16) DEFAULT NULL COLLATE utf8mb4_unicode_ci');
    }
}
