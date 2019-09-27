<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190924161203 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'TECH-62';
    }

    /**
     * @param Schema $schema
     *
     * @throws DBALException
     */
    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'mysql\'.');

        $templates = $this->connection->fetchAll(
            'SELECT * FROM mail_templates WHERE part NOT IN ("header", "footer")'
        );
        $this->addSql('ALTER TABLE mail_queue CHANGE to_send_at to_send_at DATE DEFAULT NULL COMMENT \'(DC2Type:date_immutable)\', CHANGE sent_at sent_at DATE DEFAULT NULL COMMENT \'(DC2Type:date_immutable)\', CHANGE updated updated DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE added added DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');

        $this->addSql('ALTER TABLE mail_queue DROP FOREIGN KEY fk_mail_queue_id_mail_template');
        $this->addSql('ALTER TABLE mail_templates DROP FOREIGN KEY mail_templates_ibfk_1');
        $this->addSql('ALTER TABLE mail_templates DROP FOREIGN KEY mail_templates_ibfk_2');
        $this->addSql('CREATE TABLE mail_template (id INT AUTO_INCREMENT NOT NULL, id_header INT DEFAULT NULL, id_footer INT DEFAULT NULL, id_layout INT DEFAULT NULL, name VARCHAR(191) NOT NULL, locale VARCHAR(5) NOT NULL, content LONGTEXT DEFAULT NULL, archived DATETIME DEFAULT NULL, subject VARCHAR(191) DEFAULT NULL, sender_name VARCHAR(191) DEFAULT NULL, sender_email VARCHAR(191) DEFAULT NULL, updated DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', added DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_4AB7DECB48451D2C (id_header), INDEX IDX_4AB7DECBC406B0BE (id_footer), INDEX IDX_4AB7DECB1C0DDE0F (id_layout), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE mail_header (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(191) NOT NULL, locale VARCHAR(5) NOT NULL, content LONGTEXT DEFAULT NULL, archived DATETIME DEFAULT NULL, updated DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', added DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE mail_footer (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(191) NOT NULL, locale VARCHAR(5) NOT NULL, content LONGTEXT DEFAULT NULL, archived DATETIME DEFAULT NULL, updated DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', added DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE mail_layout (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(191) NOT NULL, locale VARCHAR(5) NOT NULL, content LONGTEXT DEFAULT NULL, archived DATETIME DEFAULT NULL, updated DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', added DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE mail_template ADD CONSTRAINT FK_4AB7DECB48451D2C FOREIGN KEY (id_header) REFERENCES mail_header (id)');
        $this->addSql('ALTER TABLE mail_template ADD CONSTRAINT FK_4AB7DECBC406B0BE FOREIGN KEY (id_footer) REFERENCES mail_footer (id)');
        $this->addSql('ALTER TABLE mail_template ADD CONSTRAINT FK_4AB7DECB1C0DDE0F FOREIGN KEY (id_layout) REFERENCES mail_layout (id)');
        $this->addSql('DROP TABLE mail_templates');
        $this->addSql('ALTER TABLE mail_queue ADD CONSTRAINT FK_4B3EDD0CF49F0FAE FOREIGN KEY (id_mail_template) REFERENCES mail_template (id)');

        $footer = <<<'HTML'
     <p class="c-t3">Crédit Agricole Lending Services</p>
HTML;
        $this->addSql("INSERT INTO mail_footer(locale, name, content, added, updated) VALUES ('fr_FR', 'footer', {$this->connection->quote($footer)}, NOW(), NOW())");

        $header = <<<'HTML'
        <p>
            <img src="{{ asset('images/logo/logo-and-type-245x52@2x.png', 'gulp') }}" alt="Crédit Agricole Lending Services" width="209" height="44">
        </p>
        <h2>{{ title|default() }}</h2>
HTML;

        $this->addSql("INSERT INTO mail_header(locale, name, content, added, updated) VALUES ('fr_FR', 'header', {$this->connection->quote($header)}, NOW(), NOW())");

        $layout = <<<'HTML'
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="fr" xmlns:v="urn:schemas-microsoft-com:vml" xmlns:o="urn:schemas-microsoft-com:office:office">
<head>
    <!-- Outlook Conditional -->
    <!--[if gte mso 15]>
    <xml>
        <o:OfficeDocumentSettings>
            <o:AllowPNG />
            <o:PixelsPerInch>96</o:PixelsPerInch>
        </o:OfficeDocumentSettings>
    </xml>
    <![endif]-->
    <meta charset="UTF-8">
    <meta http-equiv="x-ua-compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" type="text/css" href="https://fonts.googleapis.com/css?family=Cabin:400,700,400">
    <!--[if mso]>
    <style>
        body,#body,table,td {
            font-family:Arial,Helvetica,sans-serif !important;
        }
    </style>
    <![endif]-->
    <style type="text/css">
        body,#body{
            height:100%;
            margin:0;
            padding:0;
            width:100%;
        }
        p{
            margin-top:18px;
            margin-bottom:18px;
            padding:0;
        }
        table{
            border-collapse:collapse;
            mso-table-lspace:0;
            mso-table-rspace:0;
        }
        h1,h2{
            display:block;
            margin:0;
            padding:0;
        }
        img{
            -ms-interpolation-mode:bicubic;
        }
        img,a img{
            border:0;
            height:auto;
            outline:none;
            text-decoration:none;
            margin:0 !important;
        }
        #outlook a{
            padding:0;
        }
        a{
            color:#2bc9af;
            font-weight:normal;
            text-decoration:none;
        }
        a:hover{
            text-decoration:underline;
        }
        p,a,td{
            -ms-text-size-adjust:100%;
            -webkit-text-size-adjust:100%;
        }
        body,#body{
            background:#f8f6f8;
            color:#302a32;
            font-family:\'\'Cabin\'\',Trebuchet MS,Helvetica,sans-serif;
            font-size:16px;
            line-height:27px;
            -webkit-font-smoothing:antialiased;
            -moz-osx-font-smoothing:grayscale;
        }
        p{
            color:#302a32;
            font-size:16px;
            line-height:27px;
            -webkit-font-smoothing:antialiased;
            -moz-osx-font-smoothing:grayscale;
        }
        h1,h2{
            color:#2bc9af;
            font-weight:normal;
        }
        h1{
            font-size:22px;
            line-height:1.25em;
        }
        h2{
            font-size:20px;
            line-height:1.15em;
        }
        ul li {
            line-height: 27px;
        }
        .text-primary {
            color: #2bc9af;
        }
        .text-left {
            text-align: left;
        }
        .text-right {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
        b, strong {
            font-weight: 500;
        }
        #container{
            width:600px;
            max-width:600px;
        }
        .image{
            height:auto;
            max-width:510px;
        }
        #header,#title,#content,#footer,#under,#above,#motive{
            box-sizing: border-box;
            padding-right:45px;
            padding-left:45px;
        }
        #header,#content{
            background:#fff;
        }
        #header,#header-full{
            border-top:6px solid #2bc9af;
        }
        #header{
            padding-top:36px;
            padding-bottom:12px;
        }
        #header-full{
            background:#fafafa;
        }
        #logo{
            width: 36px;
            padding-right:8px;
        }
        #header-full #logo{
            width:50%;
            padding-left:45px;
        }
        #header-image{
            width:50%;
            text-align:right;
        }
        #title{
            padding-top:36px;
            background:#fff;
        }
        #title hr{
            padding:0;
            margin:18px 0;
            display:block;
            clear:both;
            border:0 none;
            width:60px;
            height:2px;
            background-color:#2bc9af;
        }
        #content{
            padding-bottom:18px;
        }
        .c-p1{
            color:#2bc9af;
        }
        .c-t2{
            color:#787679;
        }
        .c-t3{
            color:#9f9d9f;
        }
        .c-white{
            color: #fff;
        }
        .underline{
            text-decoration:underline;
        }
        #above{
            background:#b9b8bA;
        }
        #above p{
            text-align:center;
        }
        #above p,#under p,.alert p{
            font-size:16px;
            line-height:24px;
        }
        #motive{
            background:#eceaed;
        }
        #motive p{
            font-size:14px !important;
            line-height:20px !important;
            margin:10px 0 !important;
        }
        #motive b{
            float:right;
        }
        #footer{
            padding-top:36px;
            padding-bottom:36px;
            background:#2bc9af;
        }
        #footer .left,#footer .right{
            width:50%;
        }
        #footer .right{
            text-align:right;
        }
        #footer .right p{
            color:#e49dc0;
            font-size:14px;
            margin-bottom:0;
        }
        #footer .right a{
            color:#e49dc0;
        }
        #footer .left a{
            color:#fff;
        }
        #footer .left a:hover{
            text-decoration:underline;
        }
        #footer .right img{
            display:inline-block;
            margin-left:4px;
        }
        #under p{
            text-align:center;
        }
        .cta{
            text-align:center;
            padding-top:18px;
            padding-bottom:18px;
        }
        .btn-primary,.btn-outline{
            mso-hide:all;
            display:inline-block;
            border-radius:4px;
            border-width:2px;
            border-style:solid;
            min-width:120px;
            padding-left:30px;
            padding-right:30px;
            padding-top:11px;
            padding-bottom:11px;
            font-size:18px;
            text-decoration:none;
        }
        .btn-primary{
            border-color:#2bc9af;
            background:#2bc9af;
            color:#fff;
        }
        .btn-primary:hover{
            border-color:#9a1d5a;
            background:#9a1d5a;
            text-decoration:none;
        }
        .btn-primary:active{
            border-color:#85194d;
            background:#85194d;
        }
        .btn-outline{
            border-color:#2bc9af;
            color:#2bc9af;
        }
        .btn-outline:hover{
            border-color:#9a1d5a;
            color:#9a1d5a;
            text-decoration:none;
        }
        .btn-outline:active{
            border-color:#85194d;
            color:#85194d;
        }
        .alert{
            padding-left:20px;
            padding-right:20px;
            margin-top:18px;
            margin-bottom:18px;
            background:#FDF4FA;
        }
        .alert .left{
            padding-top:18px;
            padding-right:20px;
        }
        .table {
            width: 100%;
            margin-bottom: 18px;
        }
        .td {
            padding: 3px;
            font-size: 14px;
        }
        .th {
            padding: 3px;
            font-size: 14px;
            font-weight: 500;
            border-bottom: 1px solid #E3E4E5;
        }
        .tf {
            padding: 3px;
            font-size: 14px;
            border-top: 1px solid #E3E4E5;
        }
        @media only screen and (max-width: 670px){
            .left,.right{
                width:100% !important;
                float:left !important;
            }
            #container{
                width:100% !important;
                max-width:100% !important;
            }
            #header,#title,#content,#footer,#under,#above,#motive{
                padding-right:20px !important;
                padding-left:20px !important;
            }
            #header-full #logo{
                padding-top:25px !important;
                padding-left:20px !important;
            }
            #header-full #logo img{
                width:150px !important;
            }
            #header-image img{
                width:175px !important;
            }
            #title{
                padding-top:18px;
            }
            .alert .left{
                text-align:center !important;
            }
            #footer .right{
                padding-top:18px;
                text-align:left !important;
            }
            h1{
                font-size:22px !important;
            }
            h2,h3{
                font-size:20px !important;
            }
            #footer .right img{
                margin-left:0 !important;
                margin-right:4px;
            }
            #motive b{
                float:none !important;
                display:block;
            }
        }
    </style>
</head>
<body>
<table id="body" align="center" border="0" cellpadding="0" cellspacing="0" width="100%">
    <tr>
        <td align="center" valign="top">
            <!-- BEGIN TEMPLATE -->
            <table id="container" border="0" cellpadding="0" cellspacing="0" width="600">
                <!-- BEGIN HEADER -->
                <tr>
                    <td id="header" valign="top">
                        {% block header %} {% endblock %}
                    </td>
                </tr>
                <!-- END HEADER -->
                <!-- BEGIN CONTENT -->
                <tr>
                    <td id="content">
                        {% block body %} {% endblock %}
                    </td>
                </tr>
<!-- END CONTENT -->
<!-- BEGIN FOOTER -->
<tr>
    <td id="under" align="center">
        {% block footer %} {% endblock %}
    </td>
</tr>
<!-- END FOOTER -->
</table>
<!-- END TEMPLATE -->
</td>
</tr>
</table>
</body>
</html>
HTML;
        $this->addSql("INSERT INTO mail_layout(locale, name, content, added, updated) VALUES ('fr_FR', 'layout', {$this->connection->quote($layout)}, NOW(), NOW())");

        foreach ($templates as $template) {
            $values = [
                $template['id_mail_template'],
                '(SELECT id FROM mail_header ORDER BY added LIMIT 1)',
                '(SELECT id FROM mail_footer ORDER BY added LIMIT 1)',
                '(SELECT id FROM mail_layout ORDER BY added LIMIT 1)',
                $this->connection->quote($template['type']),
                $this->connection->quote($template['locale']),
                $this->connection->quote(str_replace(['[EMV DYN]', '[EMV /DYN]'], ['{{ ', ' }}'], $template['content'])),
                'NULL',
                $this->connection->quote(str_replace(['[EMV DYN]', '[EMV /DYN]'], ['{{ ', ' }}'], $template['subject'])),
                $this->connection->quote($template['sender_name']),
                $this->connection->quote($template['sender_email']),
                'NOW()',
                'NOW()',
            ];
            $values = implode(', ', $values);
            $this->addSql("INSERT INTO mail_template VALUES ({$values})");
        }
    }

    /**
     * @param Schema $schema
     *
     * @throws DBALException
     */
    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'mysql\'.');
        $this->addSql('ALTER TABLE mail_queue CHANGE to_send_at to_send_at DATETIME DEFAULT NULL, CHANGE sent_at sent_at DATETIME DEFAULT NULL, CHANGE updated updated DATETIME DEFAULT NULL, CHANGE added added DATETIME NOT NULL');
        $this->addSql('ALTER TABLE mail_queue DROP FOREIGN KEY FK_4B3EDD0CF49F0FAE');
        $this->addSql('ALTER TABLE mail_template DROP FOREIGN KEY FK_4AB7DECB48451D2C');
        $this->addSql('ALTER TABLE mail_template DROP FOREIGN KEY FK_4AB7DECBC406B0BE');
        $this->addSql('ALTER TABLE mail_template DROP FOREIGN KEY FK_4AB7DECB1C0DDE0F');
        $this->addSql('CREATE TABLE mail_templates (id_mail_template INT AUTO_INCREMENT NOT NULL, id_header INT DEFAULT NULL, id_footer INT DEFAULT NULL, type VARCHAR(191) NOT NULL COLLATE utf8mb4_unicode_ci, locale VARCHAR(5) NOT NULL COLLATE utf8mb4_unicode_ci, part VARCHAR(30) NOT NULL COLLATE utf8mb4_unicode_ci, recipient_type VARCHAR(30) DEFAULT NULL COLLATE utf8mb4_unicode_ci, sender_name VARCHAR(191) DEFAULT NULL COLLATE utf8mb4_unicode_ci, sender_email VARCHAR(191) DEFAULT NULL COLLATE utf8mb4_unicode_ci, subject VARCHAR(191) DEFAULT NULL COLLATE utf8mb4_unicode_ci, content LONGTEXT DEFAULT NULL COLLATE utf8mb4_unicode_ci, compiled_content LONGTEXT DEFAULT NULL COLLATE utf8mb4_unicode_ci, status SMALLINT NOT NULL, added DATETIME NOT NULL, updated DATETIME DEFAULT NULL, INDEX fk_mail_templates_header (id_header), INDEX type (type, locale, status, part), INDEX fk_mail_templates_footer (id_footer), PRIMARY KEY(id_mail_template)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE mail_templates ADD CONSTRAINT mail_templates_ibfk_1 FOREIGN KEY (id_footer) REFERENCES mail_templates (id_mail_template) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE mail_templates ADD CONSTRAINT mail_templates_ibfk_2 FOREIGN KEY (id_header) REFERENCES mail_templates (id_mail_template) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('INSERT INTO mail_templates SELECT id, NULL, NULL, name, locale, "content", "external", sender_name, sender_email, subject, REPLACE(REPLACE(content, " }}", "[EMV /DYN]"), "{{ ", "[EMV DYN]"), NULL, 0, added, updated FROM mail_template');
        $this->addSql('INSERT INTO mail_templates(type, part, locale, status, added, updated) VALUES ("header", "header", "fr_FR", 1, NOW(), NOW())');
        $this->addSql('INSERT INTO mail_templates(type, part, locale, status, added, updated) VALUES ("footer", "footer", "fr_FR", 1, NOW(), NOW())');
        $this->addSql('DROP TABLE mail_template');

        $this->addSql('DROP TABLE mail_header');
        $this->addSql('DROP TABLE mail_footer');
        $this->addSql('DROP TABLE mail_layout');
        $this->addSql('ALTER TABLE mail_queue ADD CONSTRAINT fk_mail_queue_id_mail_template FOREIGN KEY (id_mail_template) REFERENCES mail_templates (id_mail_template) ON UPDATE NO ACTION ON DELETE NO ACTION');
    }
}
