<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20201021214344 extends AbstractMigration
{
    /**
     * @return string
     */
    public function getDescription(): string
    {
        return 'CALS-1951 Add recaptcha score to client failed and client successful login';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE client_failed_login ADD recaptcha_score DOUBLE PRECISION DEFAULT NULL');
        $this->addSql('ALTER TABLE client_successful_login ADD recaptcha_score DOUBLE PRECISION DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE client_failed_login DROP recaptcha_score');
        $this->addSql('ALTER TABLE client_successful_login DROP recaptcha_score');
    }
}
