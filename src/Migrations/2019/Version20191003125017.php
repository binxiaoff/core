<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Unilend\Migrations\Traits\FlushTranslationCacheTrait;

final class Version20191003125017 extends AbstractMigration
{
    use FlushTranslationCacheTrait;

    public function getDescription(): string
    {
        return 'CALS-371 Delete security question & answer and its traduction.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE clients DROP security_question, DROP security_answer');
        $this->addSql('DELETE FROM translations WHERE id_translation = 33');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE clients ADD security_question VARCHAR(191) DEFAULT NULL COLLATE utf8mb4_unicode_ci, ADD security_answer VARCHAR(191) DEFAULT NULL COLLATE utf8mb4_unicode_ci');
        $this->addSql('INSERT INTO translations (id_translation, locale, section, name, translation, added, updated) VALUES (33, \'fr_FR\', \'common-validator\', \'secret-answer-invalid\', \'Réponse secrète invalide.\', \'2019-08-23 15:52:37\', null)');
    }
}
