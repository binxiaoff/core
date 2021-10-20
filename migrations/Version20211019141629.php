<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20211019141629 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'CALS-4864 remove Google reCaptcha';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE core_user_failed_login DROP recaptcha_score');
        $this->addSql('ALTER TABLE core_user_successful_login DROP recaptcha_score');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE core_user_failed_login ADD recaptcha_score DOUBLE PRECISION DEFAULT NULL');
        $this->addSql('ALTER TABLE core_user_successful_login ADD recaptcha_score DOUBLE PRECISION DEFAULT NULL');
    }
}
