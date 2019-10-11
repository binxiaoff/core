<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20191010153544 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'CALS-364 (Send instruction even when email not found in database if emailDomain is known)';
    }

    public function up(Schema $schema): void
    {
        $content = <<<'TWIG'
<p>Bonjour,</p>
<p>Une demande de réintialisation de mot de passe nous à été addressée pour l'email {{ email }}.</p>
<p>Vous n'avez pas de compte chez nous ou il n'est pas encore initialisé.</p>
<p>Nous pensons que vous faite parti de {{ companyName }}. N'hesitez pas à contacter votre manager pour qu'il vous crée un compte.</p>
TWIG;

        $content = $this->connection->quote($content);
        $this->addSql(
            <<<SQL
INSERT INTO mail_template (id_header, id_footer, id_layout, name, locale, content, subject, sender_name, sender_email, added) 
VALUES (
  (SELECT id FROM mail_header ORDER BY mail_header.added LIMIT 1),
  (SELECT id FROM mail_footer ORDER BY mail_footer.added LIMIT 1),
  (SELECT id FROM mail_layout ORDER BY mail_layout.added LIMIT 1),
  'forgotten-password-missing-client',
  'fr_FR',
  {$content},
  'Une demande de réintialisation de mot de passe nous a été addressée',
  'CALS',
  'noreply@ca-lendingservices.com',
  NOW()
)
SQL
        );
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema): void
    {
        $this->addSql("DELETE FROM mail_template WHERE name = 'forgotten-password-missing-client' AND locale = 'fr_FR' ");
    }
}
