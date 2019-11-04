<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20191031104346 extends AbstractMigration
{
    /**
     * @return string
     */
    public function getDescription(): string
    {
        return 'CALS-483 (Modify fee type)';
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

        $this->addSql('ALTER TABLE project_participation_fee CHANGE fee_is_recurring fee_recurring TINYINT(1) NOT NULL');
        $this->addSql('ALTER TABLE tranche_offer_fee CHANGE fee_is_recurring fee_recurring TINYINT(1) NOT NULL');
        $this->addSql('ALTER TABLE tranche_fee CHANGE fee_is_recurring fee_recurring TINYINT(1) NOT NULL');
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

        $this->addSql('ALTER TABLE project_participation_fee CHANGE fee_recurring fee_is_recurring TINYINT(1) NOT NULL');
        $this->addSql('ALTER TABLE tranche_fee CHANGE fee_recurring fee_is_recurring TINYINT(1) NOT NULL');
        $this->addSql('ALTER TABLE tranche_offer_fee CHANGE fee_recurring fee_is_recurring TINYINT(1) NOT NULL');
    }
}
