<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20191028162014 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'CALS-483 (Move projectFee to projectParticipationFee)';
    }

    /**
     * @param Schema $schema
     *
     * @throws DBALException
     */
    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE project_participation_fee (id INT AUTO_INCREMENT NOT NULL, id_project_participation INT NOT NULL, updated DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', added DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', fee_type VARCHAR(50) NOT NULL, fee_comment LONGTEXT DEFAULT NULL, fee_rate NUMERIC(4, 4) NOT NULL, fee_is_recurring TINYINT(1) NOT NULL, INDEX IDX_28BEA4AE73E249 (id_project_participation), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE project_participation_fee ADD CONSTRAINT FK_28BEA4AE73E249 FOREIGN KEY (id_project_participation) REFERENCES project_participation (id)');
        $this->addSql('DROP TABLE project_fee');
    }

    /**
     * @param Schema $schema
     *
     * @throws DBALException
     */
    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE project_fee (id INT AUTO_INCREMENT NOT NULL, id_project INT NOT NULL, fee_type VARCHAR(50) NOT NULL COLLATE utf8mb4_unicode_ci, fee_comment LONGTEXT DEFAULT NULL COLLATE utf8mb4_unicode_ci, fee_rate NUMERIC(4, 4) NOT NULL, fee_is_recurring TINYINT(1) NOT NULL, updated DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', added DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_432BE56F12E799E (id_project), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE project_fee ADD CONSTRAINT FK_432BE56F12E799E FOREIGN KEY (id_project) REFERENCES project (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('DROP TABLE project_participation_fee');
    }
}
