<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20200317165955 extends AbstractMigration
{
    /**
     * @return string
     */
    public function getDescription(): string
    {
        return 'CALS-1309 Update security check command';
    }

    /**
     * @param Schema $schema
     */
    public function up(Schema $schema): void
    {
        $layout = <<<'TWIG'
      <mjml>
        <mj-style>
        html {
          color: black;
        }
</mj-style>
        <mj-body>
            <mj-section>
                {% block body %}{% endblock %}
            </mj-section>
        </mj-body>
    </mjml>
TWIG;
        $this->addSql("INSERT INTO mail_layout (name, locale, content, added) VALUES ('internal', 'fr_FR', '{$layout}',NOW()) ");

        $template = <<<'TWIG'
  <mj-column>
    <mj-text><h1>{{ message }}</h1></mj-text>
    {% if datetime is defined %} 
    <mj-text color="gray">{{ datetime|date(\'c\')}}</mj-text>
    {% endif %}
    {% if context is defined and context is not empty %}
    <mj-text><h2>Context</h2></mj-text>
      {% for key, value in context %}
        <mj-text><strong>{{ key }}</strong></mj-text>
        <mj-text><pre>{{ value }}</pre></mj-text>
        {% if loop.last is same as(false) %}
        <mj-divider border-width="1px" border-style="dashed" border-color="lightgrey"/>
        {% endif %}
      {% endfor %}
    {% endif %}
    <mj-divider border-width="3px" border-style="solid" border-color="black" />
    {% if extra is defined and extra is not empty %}
    <mj-text><h2>Extra</h2></mj-text>
      {% for key, value in extra %}
        <mj-text><strong>{{ key }}</strong></mj-text>
        <mj-text><pre>{{ value }}</pre></mj-text>
        {% if loop.last is same as(false) %}
        <mj-divider border-width="1px" border-style="dashed" border-color="lightgrey"/>
        {% endif %}
      {% endfor %}
    {% endif %}
  </mj-column>
TWIG;

        $this->addSql(
            <<<SQL
INSERT INTO mail_template (name, locale, content, added, id_layout, id_footer, id_header, subject, sender_name, sender_email) VALUES
('log', 'fr_FR', '{$template}', NOW(),(SELECT id FROM mail_layout WHERE name = 'internal' AND locale = 'fr_FR' LIMIT 1),
(SELECT id FROM mail_footer WHERE name = 'footer' AND locale = 'fr_FR' LIMIT 1), (SELECT id FROM mail_header WHERE name = 'internal' AND locale = 'fr_FR'),
 '{{ level_name }} on KLS {{ environment|default(false) ? \\'in \\' ~ environment }}', 'KLS', 'noreply@kls-platform.com')
SQL
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql("DELETE FROM mail_template WHERE name = 'log'");
        $this->addSql("DELETE FROM mail_layout WHERE name = 'internal'");
    }
}
