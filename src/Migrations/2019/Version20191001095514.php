<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20191001095514 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'CALS-289 Add contents';
    }

    public function up(Schema $schema): void
    {
        $content = <<<'TWIG'
<p>Bonjour,<p>
<p>Vous avez été invité(e) par {{ inviterName }} à rejoindre le projet {{ project }} sur la plateforme Crédit Agricole Lending Services.</p><p>Cliquez sur le bouton ci-dessous pour vous inscrire</p>
<table border="0" cellpadding="0" cellspacing="0" width="100%">
    <tr>
        <td class="cta" align="center">
            <a href="{{ initAccountUrl }}" class="btn-primary">Créer un compte</a>
        </td>
    </tr>
</table>
TWIG;

        $this->addSql("UPDATE mail_template SET content = '{$content}' , updated = NOW() WHERE name = 'invite-guest';");
        $this->addSql(
            "
INSERT INTO mail_template (name, locale, sender_name, sender_email, subject, content, id_header, id_footer, id_layout, added) 
VALUES ('account-created', 'fr_FR', 'Crédit Agricole Lending Services', 'contact@ca-lendingservices.com', 'Votre compte a bien été créé', '<p>Bonjour {{ firstName }},</p>
<p>Votre compte a bien été créé.</p>', (SELECT id FROM mail_header ORDER BY added LIMIT 1), (SELECT id FROM mail_footer ORDER BY added LIMIT 1), (SELECT id FROM mail_layout ORDER BY added LIMIT 1), NOW());"
        );
    }

    public function down(Schema $schema): void
    {
        $content = <<<'TWIG'
<p>Bonjour,<p>
<p>Vous avez été invité(e) par {{ inviterName }} à rejoindre la plateforme Crédit Agricole Lending Services.</p><p>Cliquez sur le bouton ci-dessous pour vous inscrire</p>
<table border="0" cellpadding="0" cellspacing="0" width="100%">
    <tr>
        <td class="cta" align="center">
            
            <a href="{{ initAccountUrl }}" class="btn-primary">Créer un compte</a>
        </td>
    </tr>
</table>
TWIG;
        $this->addSql("UPDATE mail_template SET content = '{$content}', updated = NULL WHERE name = 'invite-guest';");
        $this->addSql("DELETE FROM mail_template WHERE name = 'account-created'");
    }
}
