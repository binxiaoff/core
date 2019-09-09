<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Symfony\Contracts\Translation\TranslatorTrait;

final class Version20190909075124 extends AbstractMigration
{
    use TranslatorTrait;

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return 'CALS-332';
    }

    /**
     * @param Schema $schema
     */
    public function up(Schema $schema): void
    {
        $this->addSql("INSERT INTO clients_status (id, label) VALUES (10, 'created') ON DUPLICATE KEY UPDATE label = 'created';");
        $this->addSql("INSERT INTO clients_status (id, label) VALUES (20, 'validated') ON DUPLICATE KEY UPDATE label = 'validated';");
        $this->addSql("INSERT INTO clients_status (id, label) VALUES (30, 'blocked') ON DUPLICATE KEY UPDATE label = 'blocked';");
        $this->addSql("INSERT INTO clients_status (id, label) VALUES (100, 'closed') ON DUPLICATE KEY UPDATE label = 'closed';");

        $this->addSql('UPDATE clients_status_history SET id_status = 10 WHERE id_status IN (5, 50)');
        $this->addSql('UPDATE clients_status_history SET id_status = 20 WHERE id_status IN (30, 40, 60)');
        $this->addSql('UPDATE clients_status_history SET id_status = 30 WHERE id_status IN (65, 70)');
        $this->addSql('UPDATE clients_status_history SET id_status = 100 WHERE id_status IN (80, 90)');

        $this->addSql('INSERT INTO translations (locale, section, name, translation, added) VALUES ("fr_FR", "client-status", "created", "Création", NOW())');
        $this->addSql('INSERT INTO translations (locale, section, name, translation, added) VALUES ("fr_FR", "client-status", "validated", "Validé", NOW())');
        $this->addSql('INSERT INTO translations (locale, section, name, translation, added) VALUES ("fr_FR", "client-status", "blocked", "Bloqué", NOW())');
        $this->addSql('INSERT INTO translations (locale, section, name, translation, added) VALUES ("fr_FR", "client-status", "closed", "Définitivement fermé", NOW())');

        $this->addSql('DELETE FROM clients_status WHERE id NOT IN (10, 20, 30, 100)');

        $this->addSql('DROP INDEX UNIQ_7ED7B1FBEA750E8 ON clients_status');
        $this->addSql('ALTER TABLE clients_status_history DROP FOREIGN KEY FK_3E28AF075D37D0F1');
        $this->addSql('ALTER TABLE clients_status CHANGE id id INT NOT NULL, CHANGE label code VARCHAR(191) NOT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_7ED7B1FB77153098 ON clients_status (code)');
        $this->addSql('ALTER TABLE clients_status_history ADD CONSTRAINT FK_3E28AF075D37D0F1 FOREIGN KEY (id_status) REFERENCES clients_status (id)');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE clients_status_history DROP FOREIGN KEY FK_3E28AF075D37D0F1');
        $this->addSql('DROP INDEX UNIQ_7ED7B1FB77153098 ON clients_status');
        $this->addSql('ALTER TABLE clients_status CHANGE id id INT AUTO_INCREMENT NOT NULL, CHANGE code label VARCHAR(191) NOT NULL COLLATE utf8mb4_unicode_ci');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_7ED7B1FBEA750E8 ON clients_status (label)');
        $this->addSql('ALTER TABLE clients_status_history ADD CONSTRAINT FK_3E28AF075D37D0F1 FOREIGN KEY (id_status) REFERENCES clients_status (id)');

        $this->addSql("INSERT INTO clients_status (id, label) VALUES (10, 'A contrôler') ON DUPLICATE KEY UPDATE label = 'A contrôler'; ");
        $this->addSql("INSERT INTO clients_status (id, label) VALUES (80, 'Clôturé (demande du prêteur)') ON DUPLICATE KEY UPDATE label = 'Clôturé (demande du prêteur)';");
        $this->addSql("INSERT INTO clients_status (id, label) VALUES (90, 'Clôturé (Unilend)') ON DUPLICATE KEY UPDATE label = 'Clôturé (Unilend)';");
        $this->addSql("INSERT INTO clients_status (id, label) VALUES (20, 'Complétude') ON DUPLICATE KEY UPDATE label = 'Complétude';");
        $this->addSql("INSERT INTO clients_status (id, label) VALUES (30, 'Complétude (Relance)') ON DUPLICATE KEY UPDATE label = 'Complétude (Relance)';");
        $this->addSql("INSERT INTO clients_status (id, label) VALUES (40, 'Complétude (Réponse)') ON DUPLICATE KEY UPDATE label = 'Complétude (Réponse)';");
        $this->addSql("INSERT INTO clients_status (id, label) VALUES (100, 'Compte soldé et définitivement fermé') ON DUPLICATE KEY UPDATE label = 'Compte soldé et définitivement fermé';");
        $this->addSql("INSERT INTO clients_status (id, label) VALUES (5, 'Création') ON DUPLICATE KEY UPDATE label = 'Création';");
        $this->addSql("INSERT INTO clients_status (id, label) VALUES (70, 'Désactivé') ON DUPLICATE KEY UPDATE label = 'Désactivé';");
        $this->addSql("INSERT INTO clients_status (id, label) VALUES (50, 'Modification') ON DUPLICATE KEY UPDATE label = 'Modification';");
        $this->addSql("INSERT INTO clients_status (id, label) VALUES (65, 'Suspendu') ON DUPLICATE KEY UPDATE label = 'Suspendu';");
        $this->addSql("INSERT INTO clients_status (id, label) VALUES (60, 'Valide') ON DUPLICATE KEY UPDATE label = 'Valide';");

        $this->addSql('UPDATE clients_status_history SET id_status = 10 WHERE id_status = 20');
        $this->addSql('UPDATE clients_status_history SET id_status = 70 WHERE id_status = 30');

        $this->addSql('DELETE FROM translations WHERE section = "client-status" AND name IN ("created", "validated", "blocked", "closed")');
    }
}
