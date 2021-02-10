<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210210154242 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'CALS-3249 Add cascade on deleting on id_program of credit_guaranty_program_status';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE credit_guaranty_program_status DROP FOREIGN KEY FK_CEB64F624C70DEF4');
        $this->addSql('ALTER TABLE credit_guaranty_program_status ADD CONSTRAINT FK_CEB64F624C70DEF4 FOREIGN KEY (id_program) REFERENCES credit_guaranty_program (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE credit_guaranty_program_status DROP FOREIGN KEY FK_CEB64F624C70DEF4');
        $this->addSql('ALTER TABLE credit_guaranty_program_status ADD CONSTRAINT FK_CEB64F624C70DEF4 FOREIGN KEY (id_program) REFERENCES credit_guaranty_program (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
    }
}
