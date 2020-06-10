<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200609130553 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE notification DROP FOREIGN KEY FK_BF5476CAF12E799E');
        $this->addSql('ALTER TABLE notification ADD CONSTRAINT FK_BF5476CAF12E799E FOREIGN KEY (id_project) REFERENCES project (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE project DROP FOREIGN KEY FK_2FB3D0EEB0D1B111');
        $this->addSql('ALTER TABLE project ADD CONSTRAINT FK_2FB3D0EE41AF0274 FOREIGN KEY (id_current_status) REFERENCES project_status (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE project_file DROP FOREIGN KEY FK_B50EFE08F12E799E');
        $this->addSql('ALTER TABLE project_file ADD CONSTRAINT FK_B50EFE08F12E799E FOREIGN KEY (id_project) REFERENCES project (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE project_participation DROP FOREIGN KEY FK_7FC47549F12E799E');
        $this->addSql('ALTER TABLE project_participation ADD CONSTRAINT FK_7FC47549F12E799E FOREIGN KEY (id_project) REFERENCES project (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE project_status DROP FOREIGN KEY FK_C6DD336C166D1F9C');
        $this->addSql('ALTER TABLE project_status ADD CONSTRAINT FK_6CA48E56F12E799E FOREIGN KEY (id_project) REFERENCES project (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE project_organizer DROP FOREIGN KEY FK_88E834A4F12E799E');
        $this->addSql('ALTER TABLE project_organizer ADD CONSTRAINT FK_88E834A4F12E799E FOREIGN KEY (id_project) REFERENCES project (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE project_comment DROP FOREIGN KEY FK_26A5E09F12E799E');
        $this->addSql('ALTER TABLE project_comment ADD CONSTRAINT FK_26A5E09F12E799E FOREIGN KEY (id_project) REFERENCES project (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tranche DROP FOREIGN KEY FK_66675840F12E799E');
        $this->addSql('ALTER TABLE tranche ADD CONSTRAINT FK_66675840F12E799E FOREIGN KEY (id_project) REFERENCES project (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE notification DROP FOREIGN KEY FK_BF5476CAF12E799E');
        $this->addSql('ALTER TABLE notification ADD CONSTRAINT FK_BF5476CAF12E799E FOREIGN KEY (id_project) REFERENCES project (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE project DROP FOREIGN KEY FK_2FB3D0EE41AF0274');
        $this->addSql('ALTER TABLE project ADD CONSTRAINT FK_2FB3D0EEB0D1B111 FOREIGN KEY (id_current_status) REFERENCES project_status (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE project_comment DROP FOREIGN KEY FK_26A5E09F12E799E');
        $this->addSql('ALTER TABLE project_comment ADD CONSTRAINT FK_26A5E09F12E799E FOREIGN KEY (id_project) REFERENCES project (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE project_file DROP FOREIGN KEY FK_B50EFE08F12E799E');
        $this->addSql('ALTER TABLE project_file ADD CONSTRAINT FK_B50EFE08F12E799E FOREIGN KEY (id_project) REFERENCES project (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE project_organizer DROP FOREIGN KEY FK_88E834A4F12E799E');
        $this->addSql('ALTER TABLE project_organizer ADD CONSTRAINT FK_88E834A4F12E799E FOREIGN KEY (id_project) REFERENCES project (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE project_participation DROP FOREIGN KEY FK_7FC47549F12E799E');
        $this->addSql('ALTER TABLE project_participation ADD CONSTRAINT FK_7FC47549F12E799E FOREIGN KEY (id_project) REFERENCES project (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE project_status DROP FOREIGN KEY FK_6CA48E56F12E799E');
        $this->addSql('ALTER TABLE project_status ADD CONSTRAINT FK_C6DD336C166D1F9C FOREIGN KEY (id_project) REFERENCES project (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE tranche DROP FOREIGN KEY FK_66675840F12E799E');
        $this->addSql('ALTER TABLE tranche ADD CONSTRAINT FK_66675840F12E799E FOREIGN KEY (id_project) REFERENCES project (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
    }
}
