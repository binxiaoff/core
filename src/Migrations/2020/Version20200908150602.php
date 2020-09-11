<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20200908150602 extends AbstractMigration
{
    /**
     * @return string
     */
    public function getDescription() : string
    {
        return 'CALS-2151 Add mail when participant replies sent to arranger';
    }

    /**
     * @param Schema $schema
     */
    public function up(Schema $schema) : void
    {
        $content = <<<MJML
<mj-image align="right" padding="0 0 0 0 " width="60px" src="{{ url("front_image", {imageFileName: "emails/book.png"}) }}"/>
<mj-text color="#3F2865" font-size="22px" font-weight="700">Bonjour{{ client.firstName ? " " ~ client.firstName }},</mj-text>
<mj-text color="#3F2865" font-size="14px" font-weight="100" align="justify" line-height="1.5">
    {{ participant.displayName }} a répondu à votre invitation pour le financement du {{ project.title }}  – {{ project.riskGroupName }}.
</mj-text>
<mj-text color="#3F2865" font-size="14px" font-weight="100" align="justify" line-height="1.5">
  Nous vous invitons à consulter le dossier en cliquant sur le bouton ci-dessous :
</mj-text>
<mj-button background-color="#F9B13B" border-radius="4px" font-weight="600" inner-padding="7px 30px" href="{{ url("front_projectForm", {projectPublicId : project.publicId}) }}">
    Consulter le dossier
</mj-button>
MJML;

        $content = $this->connection->quote($content);

        $this->addSql(<<<SQL
            INSERT INTO mail_template(id_header, id_footer, id_layout, name, locale, content, subject, sender_name, sender_email, added)
            VALUES (
                    (SELECT id from mail_header LIMIT 1),
                    (SELECT id from mail_footer LIMIT 1),
                    (SELECT id from mail_layout LIMIT 1),
                    'participant-reply',
                    'fr_FR',
                    $content,
                    'KLS - vous avez reçu une réponse !',
                    'KLS',
                    'support@kls-platform.com',
                    NOW()
            )
SQL
);
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema) : void
    {
        $this->addSql("DELETE FROM mail_template WHERE name = 'participant-reply'");
    }
}
