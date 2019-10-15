<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Unilend\Migrations\ContainerAwareMigration;
use Unilend\Migrations\Traits\FlushTranslationCacheTrait;

final class Version20191003125017 extends ContainerAwareMigration
{
    use FlushTranslationCacheTrait;

    public function getDescription(): string
    {
        return '
            CALS-371 Delete security question & answer and its traduction,
            CALS-374 Create translation for password reset errors.
        ';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE clients DROP security_question, DROP security_answer');
        $this->addSql('DELETE FROM translations WHERE section = "common-validator" AND name = "secret-answer-invalid"');
        $this->addSql("INSERT INTO translations (locale, section, name, translation, added) VALUES ('fr_FR', 'forgotten-password', 'incorrect-password', 'Le mot de passe doit contenir au moins 6 caractères et doit contenir au moins une minuscule et une majuscule.', NOW())");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE clients ADD security_question VARCHAR(191) DEFAULT NULL COLLATE utf8mb4_unicode_ci, ADD security_answer VARCHAR(191) DEFAULT NULL COLLATE utf8mb4_unicode_ci');
        $this->addSql('INSERT INTO translations (locale, section, name, translation, added) VALUES (\'fr_FR\', \'common-validator\', \'secret-answer-invalid\', \'Réponse secrète invalide.\', \'2019-08-23 15:52:37\')');
        $this->addSql('DELETE FROM translations WHERE section = "forgotten-password" and name = "incorrect-password"');
    }
}
