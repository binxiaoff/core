<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20191209084858 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add possibility to set fee to 100%';
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

        $this->addSql('ALTER TABLE project_participation_fee CHANGE fee_rate fee_rate NUMERIC(5, 4) NOT NULL');
        $this->addSql('ALTER TABLE tranche_offer_fee CHANGE fee_rate fee_rate NUMERIC(5, 4) NOT NULL');
        $this->addSql('ALTER TABLE tranche_fee CHANGE fee_rate fee_rate NUMERIC(5, 4) NOT NULL');
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

        $this->addSql('ALTER TABLE project_participation_fee CHANGE fee_rate fee_rate NUMERIC(4, 4) NOT NULL');
        $this->addSql('ALTER TABLE tranche_fee CHANGE fee_rate fee_rate NUMERIC(4, 4) NOT NULL');
        $this->addSql('ALTER TABLE tranche_offer_fee CHANGE fee_rate fee_rate NUMERIC(4, 4) NOT NULL');
    }
}
