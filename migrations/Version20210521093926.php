<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20210521093926 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'CALS-3720 [CreditGuaranty] add archived date field in credit_guaranty_program_choice_option table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE credit_guaranty_program_choice_option ADD archived DATETIME DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE credit_guaranty_program_choice_option DROP archived');
    }
}
