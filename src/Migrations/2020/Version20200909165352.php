<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200909165352 extends AbstractMigration
{
    public const MAILS = [
        'arranger-invitation-external-bank' => [
            'name' => 'arranger-invitation-external-bank',
            'content' => <<<CONTENT
<mj-text color="#3F2865" font-size="22px" font-weight="700">Bonjour{{ client.firstName ? " " ~ client.firstName : "" }},</mj-text>
<mj-text color="#3F2865" font-size="14px" align="justify" line-height="1.5">
{{ arranger.displayName }} vous invite à participer au financement du dossier ({{ project.title }} – {{ project.riskGroupName }}){% if temporaryToken.token %} sur la plateforme KLS <a href="https://www.kls-platform.com">www.kls-platform.com</a> l’outil d’aide à la syndication {% endif %}.
</mj-text>
<mj-text color="#3F2865" font-size="14px" align="justify" line-height="1.5">
{% if temporaryToken.token %}
Votre compte n’étant pas encore créé sur la plateforme d’aide à la syndication, KLS, nous vous invitons à créer votre compte en cliquant sur le bouton ci-dessous. Vous aurez ensuite accès au dossier.
{% else %}
Nous vous invitons à consulter l’invitation en cliquant sur le bouton ci-dessous.
{% endif %}
</mj-text>
<mj-button 
  background-color="#F9B13B"
  border-radius="4px" 
  font-weight="500"
  inner-padding="7px 30px"
  href="{{ temporaryToken.token ? url("front_initialAccount", {temporaryTokenPublicId : temporaryToken.token, clientPublicId: client.publicId, projectPublicId: project.publicId}) : url("front_viewParticipation", {projectParticipationPublicId: projectParticipation.publicId}) }}"
>
    {% if temporaryToken.token %} Créer mon compte sur KLS {% else %} Consulter l’invitation {% endif %}
</mj-button>
CONTENT
,
            'subject' => 'KLS - vous avez reçu une invitation !'
        ]
    ];

    public function getDescription() : string
    {
        return 'CALS-2152 CALS-2153 Add external bank invitation emails';
    }

    public function up(Schema $schema) : void
    {
        foreach (static::MAILS as $mail) {
            $this->addSql(<<<SQL
INSERT INTO mail_template(id_header, id_footer, id_layout, name, locale, content, subject, sender_name, sender_email, added)  
VALUES ((SELECT id FROM mail_header LIMIT 1),
        (SELECT id FROM mail_footer LIMIT 1), 
        (SELECT id FROM mail_layout LIMIT 1),
        '{$mail['name']}',
        'fr_FR',
        '{$mail['content']}',
        '{$mail['subject']}',
        'KLS',
        'support@kls-platform.com',
        NOW())
SQL
);
        }
    }

    public function down(Schema $schema) : void
    {
        foreach (static::MAILS as $mail) {
            $this->addSql("DELETE FROM mail_template WHERE name = '{$mail['name']}'");
        }
    }
}
