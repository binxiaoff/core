<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20211222142911 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '[CreditGuaranty] CALS-5313 add transcode property in credit_guaranty_program_choice_option table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE credit_guaranty_program_choice_option ADD transcode VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE credit_guaranty_program_choice_option DROP transcode');
    }
}
