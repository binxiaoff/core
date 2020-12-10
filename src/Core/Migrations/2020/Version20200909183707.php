<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20200909183707 extends AbstractMigration
{

    public function getDescription(): string
    {
        return 'CALS-2152 CALS-2153 Update route for user initialisation';
    }

    public function up(Schema $schema): void
    {
        $content = <<<CONTENT
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
  href="{{ temporaryToken.token ? url("front_initialAccount", {temporaryTokenPublicId : temporaryToken.token, clientPublicId: client.publicId, projectParticipationPublicId: projectParticipation.publicId}) : url("front_viewParticipation", {projectParticipationPublicId: projectParticipation.publicId}) }}"
>
    {% if temporaryToken.token %} Créer mon compte sur KLS {% else %} Consulter l’invitation {% endif %}
</mj-button>
CONTENT;
        $this->addSql("UPDATE mail_template SET content = '$content' WHERE name = 'arranger-invitation-external-bank'");
    }

    public function down(Schema $schema): void
    {
        $content = <<<CONTENT
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
CONTENT;
        $this->addSql("UPDATE mail_template SET content = '$content' WHERE name = 'arranger-invitation-external-bank'");
    }
}
